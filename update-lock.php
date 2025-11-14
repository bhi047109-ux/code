<?php
// update-lock.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$lockFile = '/var/www/state/lock.json';
$dir = dirname($lockFile);
if (!is_dir($dir)) {
    if (!@mkdir($dir, 0770, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot create state directory.']);
        exit;
    }
}
if (!file_exists($lockFile)) {
    file_put_contents($lockFile, json_encode(['locked' => false], JSON_PRETTY_PRINT));
    @chown($lockFile, 'www-data');
    @chgrp($lockFile, 'www-data');
    @chmod($lockFile, 0660);
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

$fp = @fopen($lockFile, 'c+');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot open lock file.']);
    exit;
}
if (!flock($fp, LOCK_EX)) {
    fclose($fp);
    http_response_code(500);
    echo json_encode(['error' => 'Cannot lock file.']);
    exit;
}

fseek($fp, 0);
$contents = stream_get_contents($fp);
$data = json_decode($contents, true);
if (!is_array($data)) $data = ['locked' => false];

if (isset($body['toggle']) && $body['toggle']) {
    $data['locked'] = !$data['locked'];
} elseif (isset($body['locked'])) {
    $data['locked'] = (bool)$body['locked'];
} else {
    $data['locked'] = !$data['locked'];
}

ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode($data);
