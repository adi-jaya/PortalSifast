import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const iconsDir = path.resolve(__dirname, '../icons');

if (!fs.existsSync(iconsDir)) {
    fs.mkdirSync(iconsDir, { recursive: true });
}

function crc32(buf) {
    let crc = 0xffffffff;
    for (let i = 0; i < buf.length; i++) {
        crc ^= buf[i];
        for (let j = 0; j < 8; j++) {
            crc = (crc >>> 1) ^ (crc & 1 ? 0xedb88320 : 0);
        }
    }
    return (crc ^ 0xffffffff) >>> 0;
}

function createPngChunk(type, data) {
    const len = Buffer.alloc(4);
    len.writeUInt32BE(data.length, 0);

    const typeAndData = Buffer.concat([Buffer.from(type, 'ascii'), data]);
    const crc = Buffer.alloc(4);
    crc.writeUInt32BE(crc32(typeAndData), 0);

    return Buffer.concat([len, typeAndData, crc]);
}

function generatePng(width, height, r = 5, g = 150, b = 105) {
    // Signature
    const signature = Buffer.from([
        0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a,
    ]);

    // IHDR
    const ihdrData = Buffer.alloc(13);
    ihdrData.writeUInt32BE(width, 0);
    ihdrData.writeUInt32BE(height, 4);
    ihdrData.writeUInt8(8, 8); // 8-bit depth
    ihdrData.writeUInt8(6, 9); // RGBA color
    ihdrData.writeUInt8(0, 10); // compression
    ihdrData.writeUInt8(0, 11); // filter
    ihdrData.writeUInt8(0, 12); // interlace
    const ihdrChunk = createPngChunk('IHDR', ihdrData);

    // Scanlines (RGBA)
    const rowBytes = width * 4;
    const rawData = Buffer.alloc(height * (rowBytes + 1));
    let offset = 0;

    for (let y = 0; y < height; y++) {
        rawData.writeUInt8(0, offset++); // Filter byte for row: 0 (None)
        for (let x = 0; x < width; x++) {
            // Draw a rounded shield badge with center highlight
            const cx = width / 2;
            const cy = height / 2;
            const dist = Math.hypot(x - cx, y - cy);
            const radius = width * 0.45;

            if (dist <= radius) {
                // Inner white cross/symbol inside green circle
                const isCross =
                    (Math.abs(x - cx) <= width * 0.12 &&
                        Math.abs(y - cy) <= height * 0.3) ||
                    (Math.abs(y - cy) <= height * 0.12 &&
                        Math.abs(x - cx) <= width * 0.3);
                if (isCross) {
                    rawData.writeUInt8(255, offset++); // R
                    rawData.writeUInt8(255, offset++); // G
                    rawData.writeUInt8(255, offset++); // B
                    rawData.writeUInt8(255, offset++); // A
                } else {
                    rawData.writeUInt8(r, offset++); // R (emerald 600: #059669)
                    rawData.writeUInt8(g, offset++); // G
                    rawData.writeUInt8(b, offset++); // B
                    rawData.writeUInt8(255, offset++); // A
                }
            } else {
                // Transparent outside circle
                rawData.writeUInt8(0, offset++);
                rawData.writeUInt8(0, offset++);
                rawData.writeUInt8(0, offset++);
                rawData.writeUInt8(0, offset++);
            }
        }
    }

    const idatChunk = createPngChunk('IDAT', zlib.deflateSync(rawData));
    const iendChunk = createPngChunk('IEND', Buffer.alloc(0));

    return Buffer.concat([signature, ihdrChunk, idatChunk, iendChunk]);
}

const sizes = [16, 48, 128];
for (const size of sizes) {
    const filePath = path.join(iconsDir, `icon-${size}.png`);
    fs.writeFileSync(filePath, generatePng(size, size));
    console.log(`Generated ${filePath}`);
}
