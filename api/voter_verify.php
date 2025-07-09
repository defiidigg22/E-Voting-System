<?php
header('Content-Type: application/json');

// --- CONFIG ---
require_once '../admin/db_config.php'; // adjust path if needed

// --- API KEY VALIDATION ---
$apiKey = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if (!$apiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'API key required']);
    exit;
}

// Check API key in database (table: api_keys)
$stmt = $conn->prepare('SELECT * FROM api_keys WHERE api_key = ? AND status = "active"');
$stmt->bind_param('s', $apiKey);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or inactive API key']);
    exit;
}
// (Optional) Increment usage count, check usage limit here

// --- VOTER VERIFICATION ---
$voter_id = $_GET['voter_id'] ?? $_POST['voter_id'] ?? '';
if (!$voter_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'voter_id required']);
    exit;
}

// Check if voter exists
$stmt = $conn->prepare('SELECT id, has_voted FROM voters WHERE id = ?');
$stmt->bind_param('s', $voter_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'voter_id' => $voter_id,
        'registered' => true,
        'has_voted' => (bool)$row['has_voted']
    ]);
} else {
    echo json_encode([
        'success' => true,
        'voter_id' => $voter_id,
        'registered' => false,
        'has_voted' => false
    ]);
}
exit;
