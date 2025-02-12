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

$stmt = $pdo->prepare("SELECT id, title, content, DATE(schedule_date) as date FROM notes WHERE user_id = ? AND schedule_date BETWEEN ? AND ?");
$stmt->execute([$user_id, $startDate, $endDate]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($notes);
?>
