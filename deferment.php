<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if global form submission is closed by staff
    $settingsQuery = $pdo->query("SELECT setting_value FROM global_settings WHERE setting_key = 'status_deferment'")->fetchColumn();
    if ($settingsQuery === 'Closed') {
        header("Location: index.php?status=form_closed");
        exit();
    }

    // Capture POST data
    $full_name             = trim($_POST['full_name'] ?? '');
    $student_type          = trim($_POST['student_type'] ?? '');
    $nric_passport         = trim($_POST['nric_passport'] ?? '');
    $phone_no              = trim($_POST['phone_no'] ?? '');
    $email                 = trim($_POST['email'] ?? '');
    $intake                = trim($_POST['intake'] ?? '');
    $current_sem           = trim($_POST['current_sem'] ?? '');
    $deferment_sem         = trim($_POST['deferment_sem'] ?? '');
    $programme             = trim($_POST['programme'] ?? '');
    $reasons_for_deferment = trim($_POST['reasons_for_deferment'] ?? '');
    
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

    $sql = "INSERT INTO defermentforms 
            (student_id, full_name, student_type, nric_passport, phone_no, email, intake, current_sem, deferment_sem, programme, reasons_for_deferment, registrar_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $student_id,
        $full_name,
        $student_type,
        $nric_passport,
        $phone_no,
        $email,
        $intake,
        $current_sem,
        $deferment_sem,
        $programme,
        $reasons_for_deferment
    ]);

    header("Location: student_dashboard.php?status=success");
    exit();
}