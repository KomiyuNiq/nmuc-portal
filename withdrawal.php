<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if global form submission is closed by staff
    $settingsQuery = $pdo->query("SELECT setting_value FROM global_settings WHERE setting_key = 'status_withdrawal'")->fetchColumn();
    if ($settingsQuery === 'Closed') {
        header("Location: index.php?status=form_closed");
        exit();
    }

    // Capture POST data
    $full_name          = trim($_POST['full_name'] ?? '');
    $nric_passport      = trim($_POST['nric_passport'] ?? '');
    $phone_no           = trim($_POST['phone_no'] ?? '');
    $parent_phone_no    = trim($_POST['parent_phone_no'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $student_type       = trim($_POST['student_type'] ?? '');
    $intake             = trim($_POST['intake'] ?? '');
    $sponsor            = trim($_POST['sponsor'] ?? '');
    $programme          = trim($_POST['programme'] ?? '');
    $address            = trim($_POST['address'] ?? '');
    $withdrawal_reasons = trim($_POST['withdrawal_reasons'] ?? '');
    
    // Capture manual Student ID input if typed into the form
    $manual_student_id = trim($_POST['student_id'] ?? '');

    $student_id = $_SESSION['user_id'] ?? NULL;
    if (!empty($manual_student_id)) {
        $userCheck = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $userCheck->execute([$manual_student_id]);
        $foundId = $userCheck->fetchColumn();
        if ($foundId) {
            $student_id = $foundId;
        }
    }

    $sql = "INSERT INTO withdrawalforms 
            (student_id, full_name, nric_passport, phone_no, parent_phone_no, email, student_type, intake, sponsor, programme, address, withdrawal_reasons, office_acknowledged_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $student_id,
        $full_name,
        $nric_passport,
        $phone_no,
        $parent_phone_no,
        $email,
        $student_type,
        $intake,
        $sponsor,
        $programme,
        $address,
        $withdrawal_reasons
    ]);

    header("Location: student_dashboard.php?status=success");
    exit();
}