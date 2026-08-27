<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = :id");
$stmt->execute(['id' => $student_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Statuses
$myChangeForms = $pdo->prepare("SELECT * FROM ChangeProgramForms WHERE student_id = ? ORDER BY form_id DESC");
$myChangeForms->execute([$student_id]);
$changeData = $myChangeForms->fetchAll(PDO::FETCH_ASSOC);

$myDeferForms = $pdo->prepare("SELECT * FROM DefermentForms WHERE student_id = ? ORDER BY form_id DESC");
$myDeferForms->execute([$student_id]);
$deferData = $myDeferForms->fetchAll(PDO::FETCH_ASSOC);

$myWithdrawForms = $pdo->prepare("SELECT * FROM WithdrawalForms WHERE student_id = ? ORDER BY form_id DESC");
$myWithdrawForms->execute([$student_id]);
$withdrawData = $myWithdrawForms->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    if ($status === 'APPROVED') return '<span class="badge-status status-approved">APPROVED</span>';
    if ($status === 'REJECTED') return '<span class="badge-status status-rejected">REJECTED</span>';
    return '<span class="badge-status status-pending">PENDING</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NMUC Student Portal - Notifications & Status</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'header.php'; ?>

<div class="container" style="width: 100%; max-width: 1000px; margin: 0;">
    <h3>My Application Notifications & Approvals</h3>
    <p style="margin-bottom: 20px; color: #666; font-size: 0.9rem;">Track real-time approval status for submitted applications below.</p>

    <h4 style="margin-top: 20px;">1. Change Programme Requests</h4>
    <table>
        <tr><th>Submitted Date</th><th>Requested Programme</th><th>Status</th><th>Processed By</th></tr>
        <?php if (!empty($changeData)): ?>
            <?php foreach ($changeData as $row): ?>
            <tr>
                <td><?= $row['submitted_at'] ?? 'N/A' ?></td>
                <td><?= htmlspecialchars($row['new_programme']) ?></td>
                <td><?= getStatusBadge($row['registrar_status'] ?? 'PENDING') ?></td>
                <td><?= htmlspecialchars($row['registrar_approved_by'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">No Change Programme applications submitted yet.</td></tr>
        <?php endif; ?>
    </table>

    <h4 style="margin-top: 25px;">2. Course Deferment Requests</h4>
    <table>
        <tr><th>Submitted Date</th><th>Deferment Semester</th><th>Reasons</th><th>Status</th><th>Processed By</th></tr>
        <?php if (!empty($deferData)): ?>
            <?php foreach ($deferData as $row): ?>
            <tr>
                <td><?= $row['submitted_at'] ?? 'N/A' ?></td>
                <td><?= htmlspecialchars($row['deferment_sem']) ?></td>
                <td><?= htmlspecialchars($row['reasons_for_deferment']) ?></td>
                <td><?= getStatusBadge($row['registrar_status'] ?? 'PENDING') ?></td>
                <td><?= htmlspecialchars($row['registrar_approved_by'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No Course Deferment applications submitted yet.</td></tr>
        <?php endif; ?>
    </table>

    <h4 style="margin-top: 25px;">3. Withdrawal Requests</h4>
<table>
    <tr>
        <th>Submitted Date</th>
        <th>Programme</th>
        <th>Status</th>
        <th>Processed By</th>
    </tr>
    <?php if (!empty($withdrawData)): ?>
        <?php foreach ($withdrawData as $row): ?>
        <?php 
            // Extract pure name by removing (APPROVED) or (REJECTED) appended tags
            $rawOfficer = $row['office_acknowledged_by'] ?? '-';
            $cleanOfficer = trim(preg_replace('/\s*\((APPROVED|REJECTED)\)/i', '', $rawOfficer));

            // Determine status from the text or status column
            $status = 'PENDING';
            if (stripos($rawOfficer, 'APPROVED') !== false || ($row['status'] ?? '') === 'APPROVED') {
                $status = 'APPROVED';
            } elseif (stripos($rawOfficer, 'REJECTED') !== false || ($row['status'] ?? '') === 'REJECTED') {
                $status = 'REJECTED';
            }
        ?>
        <tr>
            <td><?= $row['submitted_at'] ?? 'N/A' ?></td>
            <td><?= htmlspecialchars($row['programme']) ?></td>
            <td><?= getStatusBadge($status) ?></td>
            <td><?= htmlspecialchars($cleanOfficer) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="4">No Withdrawal applications submitted yet.</td></tr>
    <?php endif; ?>
</table>
</div>

<?php include 'footer.php'; ?>