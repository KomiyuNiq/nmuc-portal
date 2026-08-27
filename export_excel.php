<?php
session_start();
require 'db.php';

// Check authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    exit('Unauthorized access');
}

// Clean output buffer to prevent blank leading lines
if (ob_get_level()) {
    ob_end_clean();
}

$type = $_GET['type'] ?? '';
$filename = "NMUC_" . ucfirst($type) . "_GoogleSheets_" . date('Ymd_His') . ".csv";

// Headers configured for Google Sheets compatibility
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM ensuring proper character encoding in Google Sheets
fputs($output, "\xEF\xBB\xBF");

if ($type === 'change_programme') {
    fputcsv($output, ['Form ID', 'Student Name', 'Student ID', 'Current Programme', 'Semester', 'New Programme', 'Reasons', 'Status', 'Approved By', 'Date']);
    
    $query = "SELECT c.*, COALESCE(u.full_name, 'N/A') AS full_name, COALESCE(u.username, 'N/A') AS username 
              FROM ChangeProgramForms c 
              LEFT JOIN Users u ON c.student_id = u.user_id 
              ORDER BY c.form_id DESC";
    $stmt = $pdo->query($query);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['form_id'],
            $row['full_name'],
            $row['username'],
            $row['current_program'],
            $row['semester'],
            $row['new_programme'],
            $row['reasons'],
            $row['registrar_status'] ?? 'Pending',
            $row['registrar_approved_by'] ?? '-',
            $row['registrar_date'] ?? '-'
        ]);
    }
} elseif ($type === 'deferment') {
    fputcsv($output, ['Form ID', 'Student Name', 'Student ID', 'Student Type', 'Programme', 'Intake', 'Current Sem', 'Deferment Sem', 'Reasons', 'Status', 'Approved By', 'Date']);
    
    $query = "SELECT d.*, COALESCE(u.full_name, 'N/A') AS full_name, COALESCE(u.username, 'N/A') AS username 
              FROM DefermentForms d 
              LEFT JOIN Users u ON d.student_id = u.user_id 
              ORDER BY d.form_id DESC";
    $stmt = $pdo->query($query);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['form_id'],
            $row['full_name'],
            $row['username'],
            $row['student_type'],
            $row['programme'],
            $row['intake'],
            $row['current_sem'],
            $row['deferment_sem'],
            $row['reasons_for_deferment'],
            $row['registrar_status'] ?? 'Pending',
            $row['registrar_approved_by'] ?? '-',
            $row['registrar_date'] ?? '-'
        ]);
    }
} elseif ($type === 'withdrawal') {
    fputcsv($output, ['Form ID', 'Student Name', 'Student ID', 'Student Type', 'Programme', 'Sponsor', 'Reasons', 'Status / Acknowledged By', 'Date']);
    
    $query = "SELECT w.*, COALESCE(u.full_name, 'N/A') AS full_name, COALESCE(u.username, 'N/A') AS username 
              FROM WithdrawalForms w 
              LEFT JOIN Users u ON w.student_id = u.user_id 
              ORDER BY w.form_id DESC";
    $stmt = $pdo->query($query);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['form_id'],
            $row['full_name'],
            $row['username'],
            $row['student_type'],
            $row['programme'],
            $row['sponsor'],
            $row['withdrawal_reasons'],
            $row['office_acknowledged_by'] ?? 'Pending',
            $row['office_date'] ?? '-'
        ]);
    }
}

fclose($output);
exit();