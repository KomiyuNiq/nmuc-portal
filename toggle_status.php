<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    die("Unauthorized access.");
}

$type = $_GET['type'] ?? '';
$id   = $_GET['id'] ?? 0;

$tableMap = [
    'change_programme' => 'changeprogramforms',
    'deferment'        => 'DefermentForms',
    'withdrawal'       => 'withdrawalforms'
];

if (!isset($tableMap[$type]) || !$id) {
    die("Invalid request parameters.");
}

$table = $tableMap[$type];

// Get current form status
$stmt = $pdo->prepare("SELECT status FROM `$table` WHERE form_id = ?");
$stmt->execute([$id]);
$currentStatus = $stmt->fetchColumn();

// Toggle status logic
$newStatus = ($currentStatus === 'Closed') ? 'Active' : 'Closed';

// Update DB
$updateStmt = $pdo->prepare("UPDATE `$table` SET status = ? WHERE form_id = ?");
$updateStmt->execute([$newStatus, $id]);

// Redirect back to staff dashboard with tab state intact
header("Location: staff_dashboard.php?tab=" . urlencode($type) . "&msg=status_updated");
exit();
?>