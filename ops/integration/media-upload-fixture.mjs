import { deflateSync } from 'node:zlib';

function chunk(type, payload) {
  const body = Buffer.concat([Buffer.from(type), payload]);
  let crc = 0xffffffff;
  for (const byte of body) {
    crc ^= byte;
    for (let bit = 0; bit < 8; bit += 1) {
      crc = (crc >>> 1) ^ ((crc & 1) ? 0xedb88320 : 0);
    }
  }
  const length = Buffer.alloc(4);
  length.writeUInt32BE(payload.length);
  const checksum = Buffer.alloc(4);
  checksum.writeUInt32BE((crc ^ 0xffffffff) >>> 0);
  return Buffer.concat([length, body, checksum]);
}

// A decoded 3000px image with legal ancillary padding. Tests transport limits,
// not photo entropy, image quality or representative compression savings.
export function uploadFixture(padded = true) {
  const width = 3000;
  const stride = width * 3 + 1;
  const pixels = Buffer.alloc(stride * width, padded ? 64 : 192);
  for (let row = 0; row < width; row += 1) pixels[row * stride] = 0;
  const header = Buffer.alloc(13);
  header.writeUInt32BE(width, 0);
  header.writeUInt32BE(width, 4);
  header[8] = 8;
  header[9] = 2;
  const chunks = [
    Buffer.from('89504e470d0a1a0a', 'hex'),
    chunk('IHDR', header),
    chunk('IDAT', deflateSync(pixels)),
  ];
  const end = chunk('IEND', Buffer.alloc(0));
  if (padded) {
    const bytes = 50 * 1024 * 1024 - Buffer.concat(chunks).length - end.length - 12;
    // Private ancillary chunk avoids decoder text-length restrictions.
    chunks.push(chunk('hsTa', Buffer.alloc(bytes, 65)));
  }
  return Buffer.concat([...chunks, end]);
}
