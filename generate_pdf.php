<?php
session_start();
require 'db.php';

// Check authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    die("Unauthorized access.");
}

$type = $_GET['type'] ?? '';
$id   = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$type || !$id) {
    die("Invalid request parameters.");
}

// Fetch form data using direct table queries and user table fallbacks
if ($type === 'change_programme') {
    $stmt = $pdo->prepare("SELECT c.*, u.username AS user_matrix, u.email AS user_email
                           FROM changeprogramforms c 
                           LEFT JOIN users u ON c.student_id = u.user_id 
                           WHERE c.form_id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $title = "CHANGE PROGRAMME FORM";
} elseif ($type === 'deferment') {
    // Tries DefermentForms first, then falls back to deferment_applications if needed
    $stmt = $pdo->prepare("SELECT d.*, u.username AS user_matrix, u.email AS user_email, u.full_name AS user_full_name
                           FROM DefermentForms d 
                           LEFT JOIN users u ON d.student_id = u.user_id 
                           WHERE d.form_id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        $stmtAlt = $pdo->prepare("SELECT d.*, NULL as user_matrix, d.email AS user_email
                                  FROM deferment_applications d 
                                  WHERE d.id = ? OR d.form_id = ?");
        $stmtAlt->execute([$id, $id]);
        $data = $stmtAlt->fetch(PDO::FETCH_ASSOC);
    }
    $title = "COURSE DEFERMENT FORM";
} elseif ($type === 'withdrawal') {
    $stmt = $pdo->prepare("SELECT w.*, u.username AS user_matrix, u.email AS user_email
                           FROM withdrawalforms w 
                           LEFT JOIN users u ON w.student_id = u.user_id 
                           WHERE w.form_id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $title = "WITHDRAWAL FORM";
} else {
    die("Invalid form type.");
}

if (!$data) {
    die("Form record not found.");
}

// Map parameters with fallbacks (Direct form fields prioritized over Users table join)
$fullName   = !empty($data['full_name']) ? $data['full_name'] : (!empty($data['name']) ? $data['name'] : ($data['user_full_name'] ?? 'N/A'));
$icPassport = !empty($data['nric_passport']) ? $data['nric_passport'] : (!empty($data['ic_number']) ? $data['ic_number'] : ($data['nric'] ?? 'N/A'));
$phoneNo    = !empty($data['phone_no']) ? $data['phone_no'] : (!empty($data['phone_number']) ? $data['phone_number'] : ($data['phone'] ?? 'N/A'));
$emailAddr  = !empty($data['email']) ? $data['email'] : ($data['user_email'] ?? 'N/A');

// Resolve ID NO. across all potential sources
$idNumber = 'N/A';
if (!empty($data['user_matrix'])) {
    $idNumber = $data['user_matrix'];
} elseif (!empty($data['student_id_number'])) {
    $idNumber = $data['student_id_number'];
} elseif (!empty($data['student_id']) && $data['student_id'] !== 'NULL') {
    $idNumber = $data['student_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($fullName) ?></title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.3;
            margin: 0;
            padding: 12mm 15mm;
            background-color: #fff;
        }

        /* Top Header Layout */
        .header-container {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .header-logo-left {
            display: table-cell;
            width: 20%;
            vertical-align: middle;
        }

        .header-title-center {
            display: table-cell;
            width: 60%;
            text-align: center;
            vertical-align: middle;
        }

        .header-logo-right {
            display: table-cell;
            width: 20%;
            text-align: right;
            vertical-align: middle;
        }

        .header-title-center h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            color: #000;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Section Banners */
        .section-banner {
            background-color: #404040;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
            padding: 4px 8px;
            margin-top: 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Form Row Grids & Light Grey Blocks */
        .grid-block {
            background-color: #e9e9e9;
            padding: 4px;
            margin-bottom: 4px;
        }

        table.form-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 2px;
        }

        table.form-table td {
            background-color: #ffffff;
            border: 1px solid #d0d0d0;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 10px;
        }

        .field-label {
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
        }

        .field-value {
            font-weight: normal;
            color: #000;
        }

        /* Student Declaration Block */
        .declaration-container {
            background-color: #e9e9e9;
            padding: 8px 10px;
            margin-bottom: 8px;
        }

        .declaration-row {
            margin-bottom: 6px;
            font-size: 9.5px;
            line-height: 1.35;
        }

        .declaration-status {
            font-weight: bold;
            font-size: 11px;
            margin-left: 6px;
        }

        /* Office Use Boxes */
        table.office-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        table.office-grid th {
            border: 1px solid #000;
            background-color: #ffffff;
            padding: 4px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        table.office-grid td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
            font-size: 9.5px;
            height: 110px;
            background-color: #ffffff;
        }

        .underline-text {
            text-decoration: underline;
            font-weight: bold;
        }

        .dotted-line {
            display: inline-block;
            border-bottom: 1px dotted #000;
            width: 100%;
            margin-top: 3px;
        }

        .footer-note {
            font-size: 9px;
            font-style: italic;
            font-weight: bold;
            margin-top: 6px;
            text-align: left;
        }

        @media print {
            @page {
                margin: 0;
            }
            body { 
                padding: 12mm 15mm;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 15px; text-align: right;">
    <button onclick="window.print()" style="padding: 8px 18px; background-color: #404040; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
        🖨️ Save as PDF / Print
    </button>
</div>

<!-- COMMON HEADER WITH LOGOS -->
<div class="header-container">
    <div class="header-logo-left">
        <img src="NmucLogo.png" alt="NMUC Logo" style="max-height: 50px; width: auto;">
    </div>
    <div class="header-title-center">
        <h1><?= htmlspecialchars($title) ?></h1>
    </div>
    <div class="header-logo-right">
        <img src="StaarLogo.jpeg" alt="STAAR Logo" style="max-height: 50px; width: auto;">
    </div>
</div>

<!-- 1. CHANGE PROGRAMME FORM -->
<?php if ($type === 'change_programme'): ?>
    <div class="section-banner">A. PERSONAL PARTICULARS</div>
    <div class="grid-block">
        <table class="form-table">
            <tr>
                <td colspan="2"><span class="field-label">NAME : </span><span class="field-value"><?= htmlspecialchars($fullName) ?></span></td>
            </tr>
            <tr>
                <td style="width: 50%;"><span class="field-label">NRIC/PASSPORT NO. : </span><span class="field-value"><?= htmlspecialchars($icPassport) ?></span></td>
                <td style="width: 50%;"><span class="field-label">ID NO. : </span><span class="field-value"><?= htmlspecialchars($idNumber) ?></span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="field-label">PROGRAMME : </span><span class="field-value"><?= htmlspecialchars($data['current_program'] ?? $data['current_programme'] ?? $data['programme'] ?? 'N/A') ?></span></td>
            </tr>
            <tr>
                <td style="width: 50%;"><span class="field-label">PHONE NO. : </span><span class="field-value"><?= htmlspecialchars($phoneNo) ?></span></td>
                <td style="width: 50%;"><span class="field-label">EMAIL : </span><span class="field-value"><?= htmlspecialchars($emailAddr) ?></span></td>
            </tr>
        </table>
    </div>

    <div class="section-banner">B. NEW PROGRAMME APPLICATION</div>
    <div class="grid-block">
        <table class="form-table">
            <tr>
                <td><span class="field-label">NEW PROGRAMME : </span><span class="field-value"><?= htmlspecialchars($data['new_programme'] ?? $data['new_program'] ?? 'N/A') ?></span></td>
            </tr>
            <tr>
                <td>
                    <span class="field-label">REASONS FOR APPLYING CHANGE OF PROGRAMME :</span><br>
                    <span class="field-value"><?= htmlspecialchars($data['reasons'] ?? $data['reasons_for_change'] ?? 'N/A') ?></span>
                </td>
            </tr>
        </table>
    </div>

    <div class="declaration-container">
        <div class="declaration-row">I am fully aware that I am required to pay an administrative fee RM150 for the change of programme and subject to the management approval <span class="declaration-status">yes</span></div>
        <div class="declaration-row">I also agreed that I will bear any differences in the course fee. <span class="declaration-status">yes</span></div>
        <div class="declaration-row">I am fully aware that any excess in course fees will not be refunded by the NMUC. <span class="declaration-status">yes</span></div>
        <div class="declaration-row">Any form faxed/emailed outside business hours, during weekends or holidays will be processed and effective on the next business day and hour. <span class="declaration-status">yes</span></div>
        <div class="declaration-row">I hereby understand all the information stated in this form and declared that all particulars related to me are CORRECT and COMPLETE. <span class="declaration-status">yes</span></div>
    </div>

    <div class="section-banner" style="text-align: center;">FOR OFFICE USE</div>
    <table class="office-grid">
        <tr>
            <th style="width: 50%;">STAAR DEPT</th>
            <th style="width: 50%;">FINANCE DEPT</th>
        </tr>
        <tr>
            <td>
                <strong>RECCOMENDED / NOT RECCOMENDED</strong><br>
                COMMENTS : -------------------------------------------------------------<br><br>
                -------------------------------------------------------------------------<br><br>
                <span class="underline-text">VERIFIED BY</span><br>
                NAME :<br><br>
                DATE :
            </td>
            <td>
                AMOUNT PAID :<br><br>
                RECEIPT/INVOICE NO. :<br><br><br>
                <span class="underline-text">COLLECTED BY</span><br>
                NAME :<br><br>
                DATE
            </td>
        </tr>
    </table>
    <table class="office-grid" style="margin-top: -1px;">
        <tr>
            <th colspan="2">REGISTRAR OFFICE</th>
        </tr>
        <tr>
            <td style="width: 50%; border-right: none;">
                <strong>APPROVED / REJECTED</strong><br><br>
                COMMENTS : -------------------------------------------------------------<br><br>
                -------------------------------------------------------------------------<br><br>
                -------------------------------------------------------------------------
            </td>
            <td style="width: 50%; border-left: none;">
                <span class="underline-text">APPROVED BY</span><br><br><br><br>
                NAME :<br><br>
                DATE
            </td>
        </tr>
    </table>

<!-- 2. COURSE DEFERMENT FORM -->
<?php elseif ($type === 'deferment'): ?>
    <div class="section-banner">PERSONAL AND PROGRAMME PARTICULARS</div>
    <div class="grid-block">
        <table class="form-table">
            <tr>
                <td colspan="3"><span class="field-label">TYPE OF STUDENT : </span><span class="field-value"><?= htmlspecialchars($data['student_type'] ?? 'Local Student') ?></span></td>
            </tr>
            <tr>
                <td colspan="3"><span class="field-label">NAME : </span><span class="field-value"><?= htmlspecialchars($fullName) ?></span></td>
            </tr>
            <tr>
                <td style="width: 50%;" colspan="2"><span class="field-label">NRIC/PASSPORT NO. : </span><span class="field-value"><?= htmlspecialchars($icPassport) ?></span></td>
                <td style="width: 50%;"><span class="field-label">ID NO. : </span><span class="field-value"><?= htmlspecialchars($idNumber) ?></span></td>
            </tr>
            <tr>
                <td style="width: 50%;" colspan="2"><span class="field-label">PHONE NO : </span><span class="field-value"><?= htmlspecialchars($phoneNo) ?></span></td>
                <td style="width: 50%;"><span class="field-label">EMAIL : </span><span class="field-value"><?= htmlspecialchars($emailAddr) ?></span></td>
            </tr>
            <tr>
                <td colspan="3"><span class="field-label">PROGRAMME : </span><span class="field-value"><?= htmlspecialchars($data['programme'] ?? $data['program'] ?? 'N/A') ?></span></td>
            </tr>
            <tr>
                <td style="width: 30%;"><span class="field-label">INTAKE : </span><span class="field-value"><?= htmlspecialchars($data['intake'] ?? 'N/A') ?></span></td>
                <td style="width: 35%;"><span class="field-label">CURRENT SEM. :</span><span class="field-value"><?= htmlspecialchars($data['current_sem'] ?? $data['current_semester'] ?? 'N/A') ?></span></td>
                <td style="width: 35%;"><span class="field-label">DEFERMENT SEM : </span><span class="field-value"><?= htmlspecialchars($data['deferment_sem'] ?? $data['deferment_semester'] ?? 'N/A') ?></span></td>
            </tr>
            <tr>
                <td colspan="3"><span class="field-label">REASONS FOR DEFERMENT : </span><span class="field-value"><?= htmlspecialchars($data['reasons_for_deferment'] ?? $data['reasons'] ?? 'N/A') ?></span></td>
            </tr>
        </table>
    </div>

    <div class="section-banner">STUDENT DECLARATION</div>
    <div class="declaration-container">
        <div class="declaration-row">I declare that I understand the Terms and Conditions stipulated in the PTPTN Loan Agreement and I shall not hold NMUC responsible or liable in any way for any claims, damages, losses, expenses, costs in the event that I do not receive PTPTN payment for the semester that I defer and to make my own financial arrangements. <span class="declaration-status">yes</span></div>
        <div class="declaration-row">I am fully aware that I am required to pay an administrative fee RM 100 for the deferment and subject to the management approval. I also agree that I will bear any differences in the course fee. <span class="declaration-status">yes</span></div>
        <div class="declaration-row">I am fully aware that any excess in course fees will not be refunded by the NMUC. <span class="declaration-status">yes</span></div>
        <div class="declaration-row">I hereby understand all the information stated in this form and declared that all particulars related to me are CORRECT and COMPLETE. <span class="declaration-status">yes</span></div>
    </div>

    <div class="section-banner" style="text-align: center;">FOR OFFICE USE</div>
    <table class="office-grid">
        <tr>
            <th style="width: 33%;">FINANCE DEPT</th>
            <th style="width: 33%;">STAAR DEPT</th>
            <th style="width: 34%;">REGISTRAR OFFICE</th>
        </tr>
        <tr>
            <td>
                AMOUNT PAID :<br><br>
                RECEIPT/INVOICE NO. :<br><br><br>
                <span class="underline-text">COLLECTED BY</span><br>
                NAME :<br><br><br>
                DATE :
            </td>
            <td>
                <strong>RECCOMENDED / NOT RECCOMENDED</strong><br><br><br>
                <span class="underline-text">VERIFIED BY</span><br>
                NAME :<br><br><br>
                DATE :
            </td>
            <td>
                APPROVED / REJECTED<br>
                COMMENT : -----------------------<br><br>
                -------------------------------------<br><br>
                <span class="underline-text">APPROVED BY</span><br>
                NAME :<br><br><br>
                DATE :
            </td>
        </tr>
    </table>
    <div class="footer-note">** Please note that any request will require a minimum of 5 working days to be completed.</div>

<!-- 3. WITHDRAWAL FORM -->
<?php elseif ($type === 'withdrawal'): ?>
    <div class="section-banner">PERSONAL AND PROGRAMME PARTICULARS</div>
    <div class="grid-block">
        <table class="form-table">
            <tr>
                <td colspan="2"><span class="field-label">TYPE OF STUDENT : </span><span class="field-value"><?= htmlspecialchars($data['student_type'] ?? 'Local Student') ?></span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="field-label">NAME : </span><span class="field-value"><?= htmlspecialchars($fullName) ?></span></td>
            </tr>
            <tr>
                <td style="width: 50%;"><span class="field-label">NRIC/PASSPORT NO : </span><span class="field-value"><?= htmlspecialchars($icPassport) ?></span></td>
                <td style="width: 50%;"><span class="field-label">ID NO. : </span><span class="field-value"><?= htmlspecialchars($idNumber) ?></span></td>
            </tr>
            <tr>
                <td style="width: 50%;">
                    <span class="field-label">ADDRESS :</span><br>
                    <span class="field-value"><?= nl2br(htmlspecialchars($data['address'] ?? 'N/A')) ?></span>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <span class="field-label">PARENT'S PHONE NO. : </span><span class="field-value"><?= htmlspecialchars($data['parent_phone'] ?? $data['parent_phone_no'] ?? 'N/A') ?></span>
                </td>
            </tr>
            <tr>
                <td style="width: 50%;"><span class="field-label">STUDENT PHONE NO : </span><span class="field-value"><?= htmlspecialchars($phoneNo) ?></span></td>
                <td style="width: 50%;"><span class="field-label">EMAIL : </span><span class="field-value"><?= htmlspecialchars($emailAddr) ?></span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="field-label">PROGRAMME : </span><span class="field-value"><?= htmlspecialchars($data['programme'] ?? $data['program'] ?? 'N/A') ?></span></td>
            </tr>
            <tr>
                <td style="width: 50%;"><span class="field-label">SPONSOR PARTICULARS : </span><span class="field-value"><?= htmlspecialchars($data['sponsor'] ?? $data['sponsor_particulars'] ?? 'N/A') ?></span></td>
                <td style="width: 50%;"><span class="field-label">INTAKE : </span><span class="field-value"><?= htmlspecialchars($data['intake'] ?? 'N/A') ?></span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="field-label">WITHDRAWAL REASONS : </span><span class="field-value"><?= htmlspecialchars($data['withdrawal_reasons'] ?? $data['reasons'] ?? 'N/A') ?></span></td>
            </tr>
        </table>
    </div>

    <div class="section-banner">STUDENT DECLARATION</div>
    <div class="declaration-container">
        <div class="declaration-row">I understand that my withdrawal application from ALL classes is effective from the date this form is processed and my tuition fees, if any, will be calculated based on that effective date and in accordance with the published refund schedule. Any form faxed/emailed outside business hours, during weekends or holidays will be processed and effective on the next business day. <span class="declaration-status">yes</span></div>
        <div class="declaration-row">I further understand that I am dropping ALL Classes and withdrawing from NMUC and I am fully aware of and will be bound by NMUC rules and regulations for this application. <span class="declaration-status">yes</span></div>
        <div class="declaration-row">I also understand that my withdrawal application will only be process after NMUC Counselor contact me. <span class="declaration-status">yes</span></div>
        <div class="declaration-row">I hereby understand all the information stated in this form and declared that all particulars related to me are correct. <span class="declaration-status">yes</span></div>
    </div>

    <div class="section-banner" style="text-align: center;">FOR OFFICE USE</div>
    <div style="padding: 10px 5px;">
        <strong>COUNSELLOR'S COMMENTS :</strong> 
        <span class="dotted-line"></span>
        <span class="dotted-line"></span>
        <span class="dotted-line"></span>
        <br><br><br>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; border: none; vertical-align: top;">
                    <strong>COUNSELLOR :</strong><br><br>
                    ---------------------------------------<br>
                    NAME :<br>
                    DATE :
                </td>
                <td style="width: 50%; border: none; vertical-align: top;">
                    <strong>ACKNOWLEDGED BY :</strong><br><br>
                    ---------------------------------------------------<br>
                    NAME :<br>
                    DATE :
                </td>
            </tr>
        </table>
    </div>
<?php endif; ?>

<script>
window.onload = function() {
    window.print();
};
</script>

</body>
</html>