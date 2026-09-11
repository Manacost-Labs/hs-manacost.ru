import sharp from 'sharp';

export class AvatarValidationError extends Error {
  constructor(message) { super(message); this.name = 'AvatarValidationError'; }
}

export class AvatarBusyError extends Error {
  constructor(message = 'avatar processing busy') { super(message); this.name = 'AvatarBusyError'; }
}

const MAX_INPUT_BYTES = 4 * 1024 * 1024;
const MAX_OUTPUT_BYTES = 128 * 1024;
const MAX_COMMENT_OUTPUT_BYTES = 1024 * 1024;
const MAX_PIXELS = 16_000_000;
const MAX_COMMENT_EDGE = 1600;
const INPUT_FORMATS = new Map([['image/jpeg', 'jpeg'], ['image/png', 'png'], ['image/webp', 'webp']]);
let active = 0;

function suppliedFormat(contentType) {
  if (typeof contentType !== 'string') throw new AvatarValidationError('supported image content type required');
  const format = INPUT_FORMATS.get(contentType.split(';', 1)[0].trim().toLowerCase());
  if (!format) throw new AvatarValidationError('unsupported avatar content type');
  return format;
}

function hasChunk(bytes, signature, chunk) {
  if (!bytes.subarray(0, signature.length).equals(signature)) return false;
  for (let offset = signature.length; offset + 8 <= bytes.length;) {
    const length = bytes.readUInt32BE(offset);
    const type = bytes.subarray(offset + 4, offset + 8).toString('ascii');
    if (type === chunk) return true;
    offset += 12 + length;
    if (offset > bytes.length) return false;
  }
  return false;
}

function hasWebpAnimation(bytes) {
  if (bytes.length < 12 || !bytes.subarray(0, 4).equals(Buffer.from('RIFF')) || !bytes.subarray(8, 12).equals(Buffer.from('WEBP'))) return false;
  for (let offset = 12; offset + 8 <= bytes.length;) {
    const type = bytes.subarray(offset, offset + 4).toString('ascii');
    const size = bytes.readUInt32LE(offset + 4);
    const next = offset + 8 + size + (size & 1);
    if (next > bytes.length) return false;
    if (type === 'ANIM' || type === 'ANMF') return true;
    offset = next;
  }
  return false;
}

function pipeline(bytes) {
  return sharp(bytes, { failOn: 'warning', limitInputPixels: MAX_PIXELS, animated: true }).timeout({ seconds: 3 });
}

export async function normalizeAvatar(bytes, contentType) {
  if (!Buffer.isBuffer(bytes) || bytes.length === 0 || bytes.length > MAX_INPUT_BYTES) throw new AvatarValidationError('avatar bytes invalid');
  const requestedFormat = suppliedFormat(contentType);
  if (hasChunk(bytes, Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]), 'acTL')
    || hasWebpAnimation(bytes)) {
    throw new AvatarValidationError('animated avatars are not supported');
  }
  if (active >= 1) throw new AvatarBusyError();
  active += 1;
  try {
    const inspected = pipeline(bytes);
    const metadata = await inspected.metadata();
    if (metadata.format !== requestedFormat) throw new AvatarValidationError('avatar content type does not match decoded image');
    if ((metadata.pages ?? 1) > 1 || (Array.isArray(metadata.delay) && metadata.delay.length > 1)) {
      throw new AvatarValidationError('animated avatars are not supported');
    }
    const converted = pipeline(bytes).rotate().resize(256, 256, { fit: 'cover', position: 'centre' }).webp({ quality: 82 });
    const output = await converted.toBuffer();
    if (output.length > MAX_OUTPUT_BYTES) throw new AvatarValidationError('normalized avatar too large');
    return output;
  } catch (error) {
    if (error instanceof AvatarValidationError || error instanceof AvatarBusyError) throw error;
    throw new AvatarValidationError('invalid avatar image');
  } finally {
    active -= 1;
  }
}

/**
 * Decode a single static comment image into a bounded, metadata-free WebP.
 *
 * Comment screenshots must retain their aspect ratio, unlike square avatars.
 * The shared decode slot prevents simultaneous image work from exhausting the
 * small reader service.
 */
export async function normalizeCommentImage(bytes, contentType) {
  if (!Buffer.isBuffer(bytes) || bytes.length === 0 || bytes.length > MAX_INPUT_BYTES) throw new AvatarValidationError('comment image bytes invalid');
  const requestedFormat = suppliedFormat(contentType);
  if (hasChunk(bytes, Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]), 'acTL')
    || hasWebpAnimation(bytes)) {
    throw new AvatarValidationError('animated comment images are not supported');
  }
  if (active >= 1) throw new AvatarBusyError('comment image processing busy');
  active += 1;
  try {
    const inspected = pipeline(bytes);
    const metadata = await inspected.metadata();
    if (metadata.format !== requestedFormat) throw new AvatarValidationError('comment image content type does not match decoded image');
    if ((metadata.pages ?? 1) > 1 || (Array.isArray(metadata.delay) && metadata.delay.length > 1)) {
      throw new AvatarValidationError('animated comment images are not supported');
    }
    const encode = quality => pipeline(bytes).rotate().resize({
      width: MAX_COMMENT_EDGE,
      height: MAX_COMMENT_EDGE,
      fit: 'inside',
      withoutEnlargement: true,
    }).webp({ quality }).toBuffer({ resolveWithObject: true });
    let output = await encode(82);
    if (output.data.length > MAX_COMMENT_OUTPUT_BYTES) output = await encode(68);
    if (output.data.length > MAX_COMMENT_OUTPUT_BYTES || !Number.isSafeInteger(output.info.width) || !Number.isSafeInteger(output.info.height)) {
      throw new AvatarValidationError('normalized comment image too large');
    }
    return { bytes: output.data, width: output.info.width, height: output.info.height };
  } catch (error) {
    if (error instanceof AvatarValidationError || error instanceof AvatarBusyError) throw error;
    throw new AvatarValidationError('invalid comment image');
  } finally {
    active -= 1;
  }
}
