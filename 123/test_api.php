<?php
require 'config.php';
header('Content-Type: application/json');
$stmt = $pdo->prepare('SELECT seat_number FROM booked_seats WHERE showtime_id = ? AND seat_number != ""');
$stmt->execute([7]);
$seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($seats);
