<?php
require_once 'config.php';

header('Content-Type: application/json');

$showtime_id = $_GET['showtime_id'] ?? null;

if (!$showtime_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT seat_number FROM booked_seats WHERE showtime_id = ? AND seat_number != ''");
    $stmt->execute([$showtime_id]);
    $booked_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($booked_seats);
} catch (Exception $e) {
    echo json_encode([]);
}
