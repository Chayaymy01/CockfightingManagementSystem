<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';

$statistics = [
    'members' => 0,
    'farms' => 0,
];

$member_result = $conn->query('SELECT COUNT(*) AS total FROM member');
if ($member_result) {
    $statistics['members'] = (int) $member_result->fetch_assoc()['total'];
}

$farm_result = $conn->query('SELECT COUNT(*) AS total FROM farm');
if ($farm_result) {
    $statistics['farms'] = (int) $farm_result->fetch_assoc()['total'];
}

$conn->close();
echo json_encode($statistics, JSON_UNESCAPED_UNICODE);
