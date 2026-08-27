<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if global form submission is closed by staff
    $settingsQuery = $pdo->query("SELECT setting_value FROM global_settings WHERE setting_key = 'status_change_programme'")->fetchColumn();
    if ($settingsQuery === 'Closed') {
        header("Location: index.php?status=form_closed");
        exit();
    }

    // Capture POST data
    $full_name         = trim($_POST['full_name'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $nric_passport     = trim($_POST['nric_passport'] ?? '');
    $phone_no          = trim($_POST['phone_no'] ?? '');
    $current_programme = trim($_POST['current_programme'] ?? '');
    $semester          = trim($_POST['semester'] ?? '');
    $new_programme     = trim($_POST['new_programme'] ?? '');
    $reasons           = trim($_POST['reasons'] ?? '');
    
    // Capture manual Student ID input if typed into the form, fallback to session user_id if logged in
    $manual_student_id = trim($_POST['student_id'] ?? '');

    // Capture checkbox values
    $admin_fee_agreed       = isset($_POST['admin_fee']) ? 1 : 0;
    $course_fee_diff_agreed = isset($_POST['course_fee_diff']) ? 1 : 0;
    $non_refundable_agreed  = isset($_POST['non_refundable']) ? 1 : 0;
    $business_hours_agreed  = isset($_POST['business_hours']) ? 1 : 0;
    $declaration_agreed     = isset($_POST['declaration']) ? 1 : 0;

    // Check if typed student_id exists in users table, else store current session user_id or NULL
    $student_id = $_SESSION['user_id'] ?? NULL;
    if (!empty($manual_student_id)) {
        $userCheck = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $userCheck->execute([$manual_student_id]);
        $foundId = $userCheck->fetchColumn();
        if ($foundId) {
            $student_id = $foundId;
        }
    }

    $sql = "INSERT INTO changeprogramforms 
            (student_id, full_name, email, nric_passport, phone_no, current_program, semester, new_programme, reasons, admin_fee_agreed, course_fee_diff_agreed, non_refundable_agreed, business_hours_agreed, declaration_agreed) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $student_id,
        $full_name,
        $email,
        $nric_passport,
        $phone_no,
        $current_programme,
        $semester,
        $new_programme,
        $reasons,
        $admin_fee_agreed,
        $course_fee_diff_agreed,
        $non_refundable_agreed,
        $business_hours_agreed,
        $declaration_agreed
    ]);

    header("Location: student_dashboard.php?status=success");
    exit();
}