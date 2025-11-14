<?php
// update-alert.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$alertFile = '/var/www/state/alert.json';
$dir = dirname($alertFile);
if (!is_dir($dir)) {
    if (!@mkdir($dir, 0770, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot create state directory.']);
        exit;
    }
}
if (!file_exists($alertFile)) {
    file_put_contents($alertFile, json_encode(['alert' => false], JSON_PRETTY_PRINT));
    @chown($alertFile, 'www-data');
    @chgrp($alertFile, 'www-data');
    @chmod($alertFile, 0660);
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

$fp = @fopen($alertFile, 'c+');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot open alert file.']);
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
if (!is_array($data)) $data = ['alert' => false];

if (isset($body['alert'])) {
    $data['alert'] = (bool)$body['alert'];
} elseif (isset($body['clear']) && $body['clear']) {
    $data['alert'] = false;
} else {
    $data['alert'] = true;
}

ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode($data);
