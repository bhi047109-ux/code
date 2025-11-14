<?php
// update-lock.php
header('Content-Type: application/json');

// Allow fetches from anywhere for now (adjust in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$lockFile = __DIR__ . '/lock.json';

// Read request body (optional toggle param)
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

// Use file locking to avoid race conditions
$fp = fopen($lockFile, 'c+');
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

// Read current content
fseek($fp, 0);
$contents = stream_get_contents($fp);
$data = json_decode($contents, true);
if (!is_array($data)) $data = ['locked' => false];

// Determine new state
if (isset($body['toggle']) && $body['toggle']) {
    $data['locked'] = !$data['locked'];
} elseif (isset($body['locked'])) {
    // set explicit state if provided
    $data['locked'] = (bool)$body['locked'];
} else {
    // default: toggle
    $data['locked'] = !$data['locked'];
}

// Write back
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

// Return new state
echo json_encode($data);
