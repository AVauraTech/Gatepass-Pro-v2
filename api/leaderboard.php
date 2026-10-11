<?php
// api/leaderboard.php - Gatekeeper AI Simulation Leaderboard API
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!$data) {
        $data = $_POST;
    }

    $agentName = trim($data['agent_name'] ?? 'Agent-' . mt_rand(100, 999));
    $score = intval($data['score'] ?? 0);
    $level = intval($data['level'] ?? 1);
    $accuracy = floatval($data['accuracy_pct'] ?? 0.00);
    $processed = intval($data['processed_count'] ?? 0);
    $breachesPrevented = intval($data['breaches_prevented'] ?? 0);
    $throughputRate = floatval($data['throughput_rate'] ?? 0.00);

    $agentNameEsc = mysqli_real_escape_string($conn, $agentName);
    $sql = "INSERT INTO simulation_leaderboard (agent_name, score, level, accuracy_pct, processed_count, breaches_prevented, throughput_rate)
            VALUES ('$agentNameEsc', $score, $level, $accuracy, $processed, $breachesPrevented, $throughputRate)";
    $res = mysqli_query($conn, $sql);

    if ($res) {
        echo json_encode(['success' => true, 'message' => 'Score saved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error saving score.']);
    }
    exit;
}

// GET: Return top 15 leaderboard entries
$q = mysqli_query($conn, "SELECT id, agent_name, score, level, accuracy_pct, processed_count, breaches_prevented, throughput_rate, date_played 
                          FROM simulation_leaderboard ORDER BY score DESC, accuracy_pct DESC LIMIT 15");
$leaderboard = [];
if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $leaderboard[] = $row;
    }
}

echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);
