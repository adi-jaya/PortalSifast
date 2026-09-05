<?php

/**
 * receiveberkaspegawai.php — HTTP file receiver untuk Berkas Kepegawaian → webapps2
 *
 * Deploy ke server webapps, mis.: /var/www/html/uplodtes/receiveberkaspegawai.php
 * Pastikan folder target writable oleh user PHP/web server.
 *
 * Kontrak:
 *   POST multipart: field dokumen|file|image + target=berkaspegawai
 *   POST action=delete + filename (basename) + target=berkaspegawai
 *   Header opsional: X-Webapps-Token (jika WEBAPPS_RECEIVER_SECRET diset di server)
 *   Response JSON: { success, filename, target, path, message? }
 *
 * Env server (opsional, via putenv atau include conf):
 *   WEBAPPS_RECEIVER_SECRET=shared-secret
 *   WEBAPPS_RECEIVER_LOG=/var/log/receiveberkaspegawai.log
 */
header('Content-Type: application/json; charset=utf-8');

$allowedOrigin = getenv('WEBAPPS_RECEIVER_CORS_ORIGIN') ?: '*';
header('Access-Control-Allow-Origin: '.$allowedOrigin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Webapps-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function receiveberkas_log(string $message): void
{
    $path = getenv('WEBAPPS_RECEIVER_LOG');
    if ($path === false || $path === '') {
        return;
    }
    $line = date('Y-m-d H:i:s').' '.$message.PHP_EOL;
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

function receiveberkas_fail(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    receiveberkas_fail(405, 'Method not allowed');
}

$secret = getenv('WEBAPPS_RECEIVER_SECRET') ?: '';
if ($secret !== '') {
    $token = isset($_SERVER['HTTP_X_WEBAPPS_TOKEN'])
        ? trim((string) $_SERVER['HTTP_X_WEBAPPS_TOKEN'])
        : '';
    if ($token === '' || ! hash_equals($secret, $token)) {
        receiveberkas_log('AUTH_FAIL ip='.($_SERVER['REMOTE_ADDR'] ?? '?'));
        receiveberkas_fail(401, 'Unauthorized');
    }
}

$targets = [
    'berkaspegawai' => '/var/www/html/webapps2/penggajian/pages/berkaspegawai/berkas',
];

$allowedExt = ['pdf', 'jpg', 'jpeg'];
$allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg'];

$targetKey = isset($_POST['target']) ? trim((string) $_POST['target']) : '';
if ($targetKey === '' || ! isset($targets[$targetKey])) {
    receiveberkas_fail(400, 'Invalid or missing target. Allowed: '.implode(', ', array_keys($targets)));
}

$destDir = rtrim($targets[$targetKey], '/\\');
$action = isset($_POST['action']) ? strtolower(trim((string) $_POST['action'])) : 'upload';

if ($action === 'delete') {
    $filename = isset($_POST['filename']) ? basename((string) $_POST['filename']) : '';
    if ($filename === '' || $filename === '.' || $filename === '..') {
        receiveberkas_fail(400, 'Invalid filename');
    }

    $path = $destDir.DIRECTORY_SEPARATOR.$filename;
    if (is_file($path)) {
        if (! @unlink($path)) {
            receiveberkas_fail(500, 'Failed to delete file');
        }
        receiveberkas_log("DELETE ok target={$targetKey} file={$filename}");
    } else {
        receiveberkas_log("DELETE absent target={$targetKey} file={$filename}");
    }

    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'target' => $targetKey,
        'path' => $path,
        'message' => 'Deleted (or already absent)',
    ]);
    exit;
}

if (! is_dir($destDir) && ! @mkdir($destDir, 0755, true)) {
    receiveberkas_fail(500, 'Destination directory missing and could not be created');
}

$fileKey = null;
foreach (['dokumen', 'file', 'image'] as $key) {
    if (isset($_FILES[$key]) && is_array($_FILES[$key]) && (int) ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $fileKey = $key;
        break;
    }
}

if ($fileKey === null) {
    receiveberkas_fail(400, 'No upload field (dokumen|file|image)');
}

$upload = $_FILES[$fileKey];
if ((int) $upload['error'] !== UPLOAD_ERR_OK) {
    receiveberkas_fail(400, 'Upload error code: '.$upload['error']);
}

$requestedName = isset($_POST['filename']) ? basename((string) $_POST['filename']) : '';
$originalName = basename((string) $upload['name']);
$filename = $requestedName !== '' ? $requestedName : $originalName;
$filename = preg_replace('/[^A-Za-z0-9._-]/', '_', str_replace(' ', '_', $filename));
if ($filename === '' || $filename === '.' || $filename === '..') {
    receiveberkas_fail(400, 'Invalid filename');
}

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (! in_array($ext, $allowedExt, true)) {
    receiveberkas_fail(400, 'File extension not allowed for target '.$targetKey);
}

if (is_uploaded_file($upload['tmp_name'])) {
    $mime = @mime_content_type($upload['tmp_name']);
    if ($mime !== false && $mime !== '' && ! in_array($mime, $allowedMimes, true)) {
        receiveberkas_fail(400, 'MIME type not allowed: '.$mime);
    }
}

$destPath = $destDir.DIRECTORY_SEPARATOR.$filename;
if (! @move_uploaded_file($upload['tmp_name'], $destPath)) {
    receiveberkas_fail(500, 'Failed to move uploaded file');
}

@chmod($destPath, 0644);
receiveberkas_log("UPLOAD ok target={$targetKey} file={$filename}");

echo json_encode([
    'success' => true,
    'filename' => $filename,
    'target' => $targetKey,
    'path' => $destPath,
    'message' => 'Uploaded',
]);
