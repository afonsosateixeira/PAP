<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$startDate = "$year-$month-01";
$endDate = date("Y-m-t", strtotime($startDate));

$stmt = $pdo->prepare("SELECT notes.id, notes.title, notes.content, DATE(notes.schedule_date) as date, notes.schedule_date, 
                            IFNULL(categories.color, '#D3D3D3') AS category_color, categories.name AS category_name 
                        FROM notes 
                        LEFT JOIN categories ON notes.category_id = categories.id 
                        WHERE notes.user_id = ? AND notes.schedule_date BETWEEN ? AND ?");
$stmt->execute([$user_id, $startDate, $endDate]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Resposta JSON
echo json_encode($notes);
?>


