import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const EXTENSION_DIR = path.resolve(__dirname, '..');
const PROJECT_ROOT = path.resolve(EXTENSION_DIR, '..');
const DOWNLOADS_DIR = path.join(PROJECT_ROOT, 'public', 'downloads');
const OUTPUT_ZIP_PATH = path.join(DOWNLOADS_DIR, 'sifast-autofill-extension.zip');

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

function getDosDateTime(date = new Date()) {
    const time =
        (date.getHours() << 11) |
        (date.getMinutes() << 5) |
        (date.getSeconds() >> 1);
    const d =
        ((date.getFullYear() - 1980) << 9) |
        ((date.getMonth() + 1) << 5) |
        date.getDate();
    return { time, date: d };
}

/**
 * Creates a valid PKZIP Buffer from an array of { name: string, data: Buffer }.
 */
export function createZipArchive(files) {
    const localHeaders = [];
    const centralHeaders = [];
    let offset = 0;
    const { time, date } = getDosDateTime();

    for (const file of files) {
        const filenameBuf = Buffer.from(file.name.replace(/\\/g, '/'), 'utf8');
        const fileData = file.data;
        const crc = crc32(fileData);
        const compressedData = zlib.deflateRawSync(fileData);

        // Local Header (30 bytes + name + data)
        const localHeader = Buffer.alloc(30);
        localHeader.writeUInt32LE(0x04034b50, 0); // Local header signature
        localHeader.writeUInt16LE(20, 4); // Version needed (2.0)
        localHeader.writeUInt16LE(0x0800, 6); // Bit flag (UTF-8 enabled)
        localHeader.writeUInt16LE(8, 8); // Compression method (Deflate)
        localHeader.writeUInt16LE(time, 10);
        localHeader.writeUInt16LE(date, 12);
        localHeader.writeUInt32LE(crc, 14);
        localHeader.writeUInt32LE(compressedData.length, 18);
        localHeader.writeUInt32LE(fileData.length, 22);
        localHeader.writeUInt16LE(filenameBuf.length, 26);
        localHeader.writeUInt16LE(0, 28); // Extra field length

        localHeaders.push(localHeader, filenameBuf, compressedData);

        // Central Directory Header (46 bytes + name)
        const centralHeader = Buffer.alloc(46);
        centralHeader.writeUInt32LE(0x02014b50, 0); // Central header signature
        centralHeader.writeUInt16LE(20, 4); // Version made by
        centralHeader.writeUInt16LE(20, 6); // Version needed
        centralHeader.writeUInt16LE(0x0800, 8); // Bit flag (UTF-8)
        centralHeader.writeUInt16LE(8, 10); // Compression method
        centralHeader.writeUInt16LE(time, 12);
        centralHeader.writeUInt16LE(date, 14);
        centralHeader.writeUInt32LE(crc, 16);
        centralHeader.writeUInt32LE(compressedData.length, 20);
        centralHeader.writeUInt32LE(fileData.length, 24);
        centralHeader.writeUInt16LE(filenameBuf.length, 28);
        centralHeader.writeUInt16LE(0, 30); // Extra field length
        centralHeader.writeUInt16LE(0, 32); // File comment length
        centralHeader.writeUInt16LE(0, 34); // Disk number start
        centralHeader.writeUInt16LE(0, 36); // Internal file attributes
        centralHeader.writeUInt32LE(0, 38); // External file attributes
        centralHeader.writeUInt32LE(offset, 42); // Relative offset of local header

        centralHeaders.push(centralHeader, filenameBuf);

        offset += localHeader.length + filenameBuf.length + compressedData.length;
    }

    const centralDirOffset = offset;
    const centralDirBuffer = Buffer.concat(centralHeaders);
    const centralDirSize = centralDirBuffer.length;

    // End of Central Directory (EOCD) Record (22 bytes)
    const eocd = Buffer.alloc(22);
    eocd.writeUInt32LE(0x06054b50, 0);
    eocd.writeUInt16LE(0, 4); // Disk number
    eocd.writeUInt16LE(0, 6); // Start disk
    eocd.writeUInt16LE(files.length, 8); // Disk entries
    eocd.writeUInt16LE(files.length, 10); // Total entries
    eocd.writeUInt32LE(centralDirSize, 12);
    eocd.writeUInt32LE(centralDirOffset, 16);
    eocd.writeUInt16LE(0, 20); // Comment length

    return Buffer.concat([...localHeaders, centralDirBuffer, eocd]);
}

/**
 * Collects all distributable files from rs-extension/ (excluding tests and scripts).
 */
export function collectExtensionFiles(dir = EXTENSION_DIR, prefix = '') {
    const results = [];
    const entries = fs.readdirSync(dir, { withFileTypes: true });

    for (const entry of entries) {
        if (entry.name.startsWith('.') || entry.name === 'node_modules') {
            continue;
        }

        const relativePath = prefix ? `${prefix}/${entry.name}` : entry.name;
        const fullPath = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            if (entry.name === 'tests' || entry.name === 'scripts' || entry.name === 'dist') {
                continue;
            }
            results.push(...collectExtensionFiles(fullPath, relativePath));
        } else {
            results.push({
                name: relativePath,
                data: fs.readFileSync(fullPath),
            });
        }
    }

    return results;
}

export function packageExtension() {
    console.log('[Packaging] Collecting files for SIFAST extension...');
    const files = collectExtensionFiles();
    console.log(`[Packaging] Found ${files.length} distributable files:`);
    for (const f of files) {
        console.log(`  - ${f.name} (${f.data.length} bytes)`);
    }

    if (!fs.existsSync(DOWNLOADS_DIR)) {
        fs.mkdirSync(DOWNLOADS_DIR, { recursive: true });
    }

    const zipBuffer = createZipArchive(files);
    fs.writeFileSync(OUTPUT_ZIP_PATH, zipBuffer);
    console.log(`[Packaging] Successfully packaged extension archive (${zipBuffer.length} bytes):`);
    console.log(`  => ${OUTPUT_ZIP_PATH}`);
}

// Run when executed directly
if (process.argv[1] === fileURLToPath(import.meta.url)) {
    packageExtension();
}
