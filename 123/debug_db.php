<?php
require 'config.php';

echo "=== Checking booked_seats table ===\n";
$stmt = $pdo->query('SELECT * FROM booked_seats');
while ($row = $stmt->fetch()) {
    echo json_encode($row) . "\n";
}

echo "\n=== Checking showtimes table ===\n";
$stmt = $pdo->query('SELECT id, movie_id, cinema_id, show_date, show_time FROM showtimes ORDER BY id');
while ($row = $stmt->fetch()) {
    echo json_encode($row) . "\n";
}
