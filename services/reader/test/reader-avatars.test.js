import assert from 'node:assert/strict';
import test from 'node:test';
import sharp from 'sharp';
import { AvatarBusyError, AvatarValidationError, normalizeAvatar } from '../avatars.js';

async function png(width = 32, height = 48) {
  return sharp({ create: { width, height, channels: 3, background: { r: 30, g: 60, b: 90 } } }).png().toBuffer();
}

function crc32(bytes) {
  let crc = 0xffffffff;
  for (const byte of bytes) {
    crc ^= byte;
    for (let bit = 0; bit < 8; bit += 1) crc = (crc >>> 1) ^ (crc & 1 ? 0xedb88320 : 0);
  }
  return (crc ^ 0xffffffff) >>> 0;
}

function chunk(type, data) {
  const length = Buffer.alloc(4); length.writeUInt32BE(data.length);
  const name = Buffer.from(type);
  const checksum = Buffer.alloc(4); checksum.writeUInt32BE(crc32(Buffer.concat([name, data])));
  return Buffer.concat([length, name, data, checksum]);
}

function pngChunks(source) {
  const result = [];
  for (let offset = 8; offset < source.length;) {
    const length = source.readUInt32BE(offset);
    result.push({ type: source.subarray(offset + 4, offset + 8).toString('ascii'), data: source.subarray(offset + 8, offset + 8 + length) });
    offset += length + 12;
  }
  return result;
}

function frameControl(sequence, width, height) {
  const data = Buffer.alloc(26);
  data.writeUInt32BE(sequence, 0); data.writeUInt32BE(width, 4); data.writeUInt32BE(height, 8);
  data.writeUInt16BE(1, 20); data.writeUInt16BE(10, 22); // 100 ms frame duration.
  return data;
}

function animatedPng(first, second) {
  const firstChunks = pngChunks(first); const secondChunks = pngChunks(second);
  const ihdr = firstChunks.find(item => item.type === 'IHDR').data;
  const width = ihdr.readUInt32BE(0); const height = ihdr.readUInt32BE(4);
  const firstFrame = Buffer.concat(firstChunks.filter(item => item.type === 'IDAT').map(item => item.data));
  const secondFrame = Buffer.concat(secondChunks.filter(item => item.type === 'IDAT').map(item => item.data));
  const animationControl = Buffer.alloc(8); animationControl.writeUInt32BE(2, 0); // Two frames, infinite loop.
  const frameSequence = Buffer.alloc(4); frameSequence.writeUInt32BE(2, 0);
  return Buffer.concat([
    first.subarray(0, 8), chunk('IHDR', ihdr), chunk('acTL', animationControl),
    chunk('fcTL', frameControl(0, width, height)), chunk('IDAT', firstFrame),
    chunk('fcTL', frameControl(1, width, height)), chunk('fdAT', Buffer.concat([frameSequence, secondFrame])), chunk('IEND', Buffer.alloc(0)),
  ]);
}

async function singleFrameAnimatedWebp() {
  const raw = Buffer.concat([Buffer.alloc(16 * 16 * 3, 20), Buffer.alloc(16 * 16 * 3, 200)]);
  const twoFrames = await sharp(raw, { raw: { width: 16, height: 32, channels: 3, pageHeight: 16 } })
    .webp({ loop: 0, delay: [100, 100] }).toBuffer();
  let offset = 12;
  while (offset + 8 <= twoFrames.length) {
    const size = twoFrames.readUInt32LE(offset + 4);
    const type = twoFrames.subarray(offset, offset + 4).toString('ascii');
    offset += 8 + size + (size & 1);
    if (type === 'ANMF') break;
  }
  const oneFrame = Buffer.from(twoFrames.subarray(0, offset));
  oneFrame.writeUInt32LE(oneFrame.length - 8, 4);
  return oneFrame;
}

test('normalizes decoded JPEG/PNG/WebP avatars to a metadata-free 256px WebP', async () => {
  const source = await sharp({ create: { width: 20, height: 40, channels: 3, background: { r: 1, g: 2, b: 3 } } })
    .jpeg().withMetadata({ exif: { IFD0: { Artist: 'private' } } }).toBuffer();
  const output = await normalizeAvatar(source, 'image/jpeg; charset=binary');
  const metadata = await sharp(output).metadata();
  assert.equal(metadata.format, 'webp');
  assert.equal(metadata.width, 256);
  assert.equal(metadata.height, 256);
  assert.equal(metadata.exif, undefined);
  assert.ok(output.length <= 128 * 1024);
});

test('rejects oversized, corrupt, unsupported and MIME-mismatched image inputs', async () => {
  const image = await png();
  await assert.rejects(() => normalizeAvatar(Buffer.alloc(4 * 1024 * 1024 + 1), 'image/png'), AvatarValidationError);
  await assert.rejects(() => normalizeAvatar(Buffer.from('not-an-image'), 'image/png'), AvatarValidationError);
  await assert.rejects(() => normalizeAvatar(image, 'image/jpeg'), AvatarValidationError);
  await assert.rejects(() => normalizeAvatar(Buffer.from('<svg/>'), 'image/svg+xml'), AvatarValidationError);
});

test('rejects an APNG animation before producing an avatar', async () => {
  const animated = animatedPng(
    await sharp({ create: { width: 16, height: 16, channels: 3, background: 'red' } }).png().toBuffer(),
    await sharp({ create: { width: 16, height: 16, channels: 3, background: 'blue' } }).png().toBuffer(),
  );
  assert.equal((await sharp(animated, { animated: true }).metadata()).format, 'png'); // libvips flattens this valid APNG's metadata.
  await assert.rejects(() => normalizeAvatar(animated, 'image/png'), AvatarValidationError);
});

test('rejects a valid one-frame animated WebP even when sharp metadata reports one page', async () => {
  const animated = await singleFrameAnimatedWebp();
  const metadata = await sharp(animated, { animated: true }).metadata();
  assert.deepEqual({ format: metadata.format, pages: metadata.pages, delay: metadata.delay }, { format: 'webp', pages: 1, delay: [100] });
  await assert.rejects(() => normalizeAvatar(animated, 'image/webp'), AvatarValidationError);
});

test('allows only one concurrent decode without queuing excess avatar work', async () => {
  const image = await png(2_000, 2_000);
  const one = normalizeAvatar(image, 'image/png');
  await assert.rejects(() => normalizeAvatar(image, 'image/png'), AvatarBusyError);
  await one;
  await normalizeAvatar(image, 'image/png'); // The slot is released after completion.
});
