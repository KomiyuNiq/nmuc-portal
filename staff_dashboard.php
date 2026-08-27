<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: index.php");
    exit();
}

// Helper function to extract and format status strings cleanly
function parseStatus($rawStatus) {
    if (empty($rawStatus)) {
        return 'Pending';
    }
    if (stripos($rawStatus, 'APPROVED') !== false) {
        return 'APPROVED';
    }
    if (stripos($rawStatus, 'REJECTED') !== false) {
        return 'REJECTED';
    }
    return htmlspecialchars($rawStatus);
}

// Handle Form POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $staff_name = $_SESSION['full_name'] ?? 'Staff Officer';
    $today      = date('Y-m-d');
    $action     = $_POST['action_type'];

    // Multi-select Delete Action
    if ($action === 'delete_selected') {
        $selected_ids = $_POST['selected_ids'] ?? [];
        $table_type   = $_POST['table_type'] ?? '';

        if (!empty($selected_ids) && is_array($selected_ids)) {
            $tables = [
                'change_programme' => 'changeprogramforms',
                'deferment'        => 'defermentforms',
                'withdrawal'       => 'withdrawalforms'
            ];

            if (array_key_exists($table_type, $tables)) {
                $tableName  = $tables[$table_type];
                $primaryKey = 'form_id';
                
                // Construct parameters correctly for PDO deletion
                $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM {$tableName} WHERE {$primaryKey} IN ({$placeholders})");
                $stmt->execute(array_values($selected_ids));
            }
        }
    }
    // Global Form Toggle (Close / Re-open Form Type for All Students)
    elseif ($action === 'toggle_global_form_status') {
        $setting_key = $_POST['setting_key'];
        $current     = $_POST['current_status'] ?? 'Active';
        $newStatus   = ($current === 'Closed') ? 'Active' : 'Closed';

        $stmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$setting_key, $newStatus, $newStatus]);
    }
    // Individual Student Request Approvals
    elseif ($action === 'change_prog') {
        $form_id = $_POST['form_id'];
        $status  = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE changeprogramforms SET registrar_status = ?, registrar_approved_by = ?, registrar_date = ? WHERE form_id = ?");
        $stmt->execute([$status, $staff_name, $today, $form_id]);
    } elseif ($action === 'deferment') {
        $form_id = $_POST['form_id'];
        $status  = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE defermentforms SET registrar_status = ?, registrar_approved_by = ?, registrar_date = ? WHERE form_id = ?");
        $stmt->execute([$status, $staff_name, $today, $form_id]);
    } elseif ($action === 'withdrawal') {
        $form_id = $_POST['form_id'];
        $status  = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE withdrawalforms SET office_acknowledged_by = ?, office_date = ? WHERE form_id = ?");
        $stmt->execute([$staff_name . " ($status)", $today, $form_id]);
    }

    $activeTab = $_POST['active_tab'] ?? 'change_programme';
    header("Location: staff_dashboard.php?tab=" . urlencode($activeTab));
    exit();
}

// Active Tab Handling
$activeTab = $_GET['tab'] ?? 'change_programme';

// Fetch Global Form Toggle States
$settingsQuery = $pdo->query("SELECT setting_key, setting_value FROM global_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

$changeProgClosed = ($settingsQuery['status_change_programme'] ?? 'Active') === 'Closed';
$defermentClosed  = ($settingsQuery['status_deferment'] ?? 'Active') === 'Closed';
$withdrawalClosed = ($settingsQuery['status_withdrawal'] ?? 'Active') === 'Closed';

// Retrieve Data (Fetch Student ID / Matrix No. directly from users.username along with fallback full_name)
$changeList   = $pdo->query("SELECT c.*, COALESCE(u.username, 'N/A') AS student_matrix_no, COALESCE(NULLIF(c.full_name, ''), u.full_name, 'Guest') AS full_name FROM changeprogramforms c LEFT JOIN users u ON c.student_id = u.user_id ORDER BY c.form_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$deferList    = $pdo->query("SELECT d.*, COALESCE(u.username, 'N/A') AS student_matrix_no, COALESCE(NULLIF(d.full_name, ''), u.full_name, 'Guest') AS full_name FROM defermentforms d LEFT JOIN users u ON d.student_id = u.user_id ORDER BY d.form_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$withdrawList = $pdo->query("SELECT w.*, COALESCE(u.username, 'N/A') AS student_matrix_no, COALESCE(NULLIF(w.full_name, ''), u.full_name, 'Guest') AS full_name FROM withdrawalforms w LEFT JOIN users u ON w.student_id = u.user_id ORDER BY w.form_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NMUC Staff Portal</title>
    <link rel="stylesheet" href="style.css?v=3">
    <style>
        :root {
            --nmuc-navy: #032b43;
            --nmuc-cyan: #00a8e8;
            --nmuc-orange: #ff5722;
            --nmuc-bg-light: #eef4f8;
            --nmuc-card-bg: rgba(255, 255, 255, 0.92);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--nmuc-bg-light);
            font-family: Arial, sans-serif;
            color: #333333;
        }

        /* Top Header */
        .header {
            background-color: var(--nmuc-navy);
            color: #ffffff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 4px solid var(--nmuc-orange);
        }

        .header h1 {
            font-size: 1.4rem;
        }

        .header h1 span {
            color: var(--nmuc-cyan);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 15px;
        }

        /* Horizontal Scrollable Tabs */
        .tabs-container {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            border-bottom: 3px solid var(--nmuc-navy);
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .tab-btn {
            padding: 12px 18px;
            background-color: #dbe4ec;
            color: var(--nmuc-navy);
            font-weight: bold;
            border: none;
            border-radius: 6px 6px 0 0;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .tab-btn.active {
            background-color: var(--nmuc-navy);
            color: #ffffff;
            border-bottom: 3px solid var(--nmuc-orange);
        }

        .tab-content {
            display: none;
            background: var(--nmuc-card-bg);
            padding: 20px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 15px rgba(3, 43, 67, 0.08);
        }

        .tab-content.active {
            display: block;
        }

        /* Section Headers */
        .section-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px; 
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-export { 
            background-color: #0F9D58; 
            color: white; 
            padding: 8px 14px; 
            border-radius: 5px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.88rem; 
            transition: background-color 0.2s ease; 
            display: inline-block;
        }

        .btn-export:hover { 
            background-color: #0B8043; 
        }

        .btn-select-mode {
            background-color: #6c757d;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-select-mode:hover {
            background-color: #5a6268;
        }

        .btn-delete-selected {
            background-color: #d9534f;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-delete-selected:hover {
            background-color: #c9302c;
        }

        .btn-cancel-select {
            background-color: #6c757d;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            margin-left: 5px;
        }

        /* Selectable Checkbox Column Hiding */
        .cb-col {
            display: none;
            width: 35px;
            text-align: center;
        }

        /* Responsive Table Container */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            min-width: 650px;
        }

        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #dcdcdc;
            font-size: 0.9rem;
        }

        th {
            background-color: var(--nmuc-navy);
            color: #ffffff;
            font-weight: bold;
        }

        /* Action Buttons */
        td.action-cell {
            white-space: nowrap !important;
            text-align: center;
        }

        .action-form { 
            display: inline-block !important; 
            margin: 2px !important; 
        }

        .btn-approve, .btn-reject, .btn-pdf, .btn-toggle-global {
            display: inline-block;
            padding: 6px 12px;
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            vertical-align: middle;
            text-decoration: none;
        }

        .btn-approve {
            background-color: #28a745;
            color: #ffffff;
        }

        .btn-reject {
            background-color: var(--nmuc-orange);
            color: #ffffff;
        }

        .btn-pdf {
            background-color: var(--nmuc-cyan);
            color: #ffffff;
        }

        .btn-pdf:hover {
            opacity: 0.9;
        }

        .btn-toggle-close {
            background-color: var(--nmuc-orange);
            color: #ffffff;
        }

        .btn-toggle-reopen {
            background-color: var(--nmuc-cyan);
            color: #ffffff;
        }

        .status-APPROVED { color: #28a745; font-weight: bold; }
        .status-REJECTED { color: var(--nmuc-orange); font-weight: bold; }
        .status-Pending  { color: #d97706; font-weight: bold; }

        .global-badge {
            font-size: 0.85rem;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-left: 8px;
            display: inline-block;
            margin-top: 5px;
        }
        .badge-open { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-closed-header { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .tab-content {
                padding: 15px 10px;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>NMUC <span>STAFF DASHBOARD</span></h1>
    <div>
        Staff: <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong> | 
        <a href="logout.php" style="color: var(--nmuc-cyan); text-decoration: none; font-weight: bold;">Logout</a>
    </div>
</div>

<div class="container">

    <!-- TAB NAVIGATION BAR -->
    <div class="tabs-container">
        <button class="tab-btn <?= $activeTab === 'change_programme' ? 'active' : '' ?>" onclick="switchTab('change_programme')">
            Change Programme Requests (<?= count($changeList) ?>)
        </button>
        <button class="tab-btn <?= $activeTab === 'deferment' ? 'active' : '' ?>" onclick="switchTab('deferment')">
            Course Deferment Requests (<?= count($deferList) ?>)
        </button>
        <button class="tab-btn <?= $activeTab === 'withdrawal' ? 'active' : '' ?>" onclick="switchTab('withdrawal')">
            Withdrawal Requests (<?= count($withdrawList) ?>)
        </button>
    </div>

    <!-- TAB 1: CHANGE PROGRAMME -->
    <div id="tab-change_programme" class="tab-content <?= $activeTab === 'change_programme' ? 'active' : '' ?>">
        <div class="section-header">
            <div>
                <h2 style="display:inline-block; margin:0; color: var(--nmuc-navy);">1. Change Programme Requests</h2>
                <span class="global-badge <?= $changeProgClosed ? 'badge-closed-header' : 'badge-open' ?>">
                    Form Status: <?= $changeProgClosed ? 'CLOSED' : 'OPEN' ?>
                </span>
            </div>
            <div class="header-actions">
                <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to <?= $changeProgClosed ? 'RE-OPEN' : 'CLOSE' ?> submissions for Change Programme forms?');">
                    <input type="hidden" name="action_type" value="toggle_global_form_status">
                    <input type="hidden" name="setting_key" value="status_change_programme">
                    <input type="hidden" name="current_status" value="<?= $changeProgClosed ? 'Closed' : 'Active' ?>">
                    <input type="hidden" name="active_tab" value="change_programme">
                    <button type="submit" class="btn-toggle-global <?= $changeProgClosed ? 'btn-toggle-reopen' : 'btn-toggle-close' ?>">
                        <?= $changeProgClosed ? '[+] Re-Open Form' : '[-] Close Form' ?>
                    </button>
                </form>
                <a href="export_excel.php?type=change_programme" class="btn-export">
                    Export to Google Sheets (.csv)
                </a>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="action_type" value="delete_selected">
            <input type="hidden" name="table_type" value="change_programme">
            <input type="hidden" name="active_tab" value="change_programme">

            <div style="margin-bottom: 10px; text-align: right;">
                <button type="button" class="btn-select-mode" id="btn-select-change_programme" onclick="enableSelection('change_programme')">☑ Select Records</button>
                <div id="controls-change_programme" style="display: none;">
                    <button type="submit" class="btn-delete-selected" onclick="return confirmDeletion(event, 'change-cb');">🗑️ Delete Selected</button>
                    <button type="button" class="btn-cancel-select" onclick="disableSelection('change_programme', 'change-cb', 'selectAllChange')">Cancel</button>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="cb-col cb-col-change_programme">
                                <input type="checkbox" id="selectAllChange" onclick="toggleSelectAll(this, 'change-cb')">
                            </th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Current Prog</th>
                            <th>New Prog</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($changeList)): ?>
                        <?php foreach ($changeList as $row): ?>
                        <?php $statusDisplay = parseStatus($row['registrar_status'] ?? 'Pending'); ?>
                        <tr>
                            <td class="cb-col cb-col-change_programme">
                                <input type="checkbox" name="selected_ids[]" value="<?= $row['form_id'] ?>" class="change-cb">
                            </td>
                            <td><?= htmlspecialchars($row['student_matrix_no']) ?></td>
                            <td><?= htmlspecialchars($row['full_name'] ?? 'No Name Recorded') ?></td>
                            <td><?= htmlspecialchars($row['current_program']) ?></td>
                            <td><?= htmlspecialchars($row['new_programme']) ?></td>
                            <td><span class="status-<?= $statusDisplay ?>"><?= $statusDisplay ?></span></td>
                            <td class="action-cell">
                                <button type="button" class="btn-approve" onclick="submitSingleAction('change_prog', '<?= $row['form_id'] ?>', 'APPROVED', 'change_programme')">Accept</button>
                                <button type="button" class="btn-reject" onclick="submitSingleAction('change_prog', '<?= $row['form_id'] ?>', 'REJECTED', 'change_programme')">Reject</button>
                                <a href="generate_pdf.php?type=change_programme&id=<?= $row['form_id'] ?>" target="_blank" class="btn-pdf">📄 PDF</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No submissions found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <!-- TAB 2: COURSE DEFERMENT -->
    <div id="tab-deferment" class="tab-content <?= $activeTab === 'deferment' ? 'active' : '' ?>">
        <div class="section-header">
            <div>
                <h2 style="display:inline-block; margin:0; color: var(--nmuc-navy);">2. Course Deferment Requests</h2>
                <span class="global-badge <?= $defermentClosed ? 'badge-closed-header' : 'badge-open' ?>">
                    Form Status: <?= $defermentClosed ? 'CLOSED' : 'OPEN' ?>
                </span>
            </div>
            <div class="header-actions">
                <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to <?= $defermentClosed ? 'RE-OPEN' : 'CLOSE' ?> submissions for Deferment forms?');">
                    <input type="hidden" name="action_type" value="toggle_global_form_status">
                    <input type="hidden" name="setting_key" value="status_deferment">
                    <input type="hidden" name="current_status" value="<?= $defermentClosed ? 'Closed' : 'Active' ?>">
                    <input type="hidden" name="active_tab" value="deferment">
                    <button type="submit" class="btn-toggle-global <?= $defermentClosed ? 'btn-toggle-reopen' : 'btn-toggle-close' ?>">
                        <?= $defermentClosed ? '[+] Re-Open Form' : '[-] Close Form' ?>
                    </button>
                </form>
                <a href="export_excel.php?type=deferment" class="btn-export">
                    Export to Google Sheets (.csv)
                </a>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="action_type" value="delete_selected">
            <input type="hidden" name="table_type" value="deferment">
            <input type="hidden" name="active_tab" value="deferment">

            <div style="margin-bottom: 10px; text-align: right;">
                <button type="button" class="btn-select-mode" id="btn-select-deferment" onclick="enableSelection('deferment')">☑ Select Records</button>
                <div id="controls-deferment" style="display: none;">
                    <button type="submit" class="btn-delete-selected" onclick="return confirmDeletion(event, 'defer-cb');">🗑️ Delete Selected</button>
                    <button type="button" class="btn-cancel-select" onclick="disableSelection('deferment', 'defer-cb', 'selectAllDefer')">Cancel</button>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="cb-col cb-col-deferment">
                                <input type="checkbox" id="selectAllDefer" onclick="toggleSelectAll(this, 'defer-cb')">
                            </th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Programme</th>
                            <th>Deferment Term</th>
                            <th>Reasons</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($deferList)): ?>
                        <?php foreach ($deferList as $row): ?>
                        <?php $statusDisplay = parseStatus($row['registrar_status'] ?? 'Pending'); ?>
                        <tr>
                            <td class="cb-col cb-col-deferment">
                                <input type="checkbox" name="selected_ids[]" value="<?= $row['form_id'] ?>" class="defer-cb">
                            </td>
                            <td><?= htmlspecialchars($row['student_matrix_no']) ?></td>
                            <td><?= htmlspecialchars($row['full_name'] ?? 'No Name Recorded') ?></td>
                            <td><?= htmlspecialchars($row['programme']) ?></td>
                            <td><?= htmlspecialchars($row['deferment_sem']) ?></td>
                            <td><?= htmlspecialchars($row['reasons_for_deferment']) ?></td>
                            <td><span class="status-<?= $statusDisplay ?>"><?= $statusDisplay ?></span></td>
                            <td class="action-cell">
                                <button type="button" class="btn-approve" onclick="submitSingleAction('deferment', '<?= $row['form_id'] ?>', 'APPROVED', 'deferment')">Accept</button>
                                <button type="button" class="btn-reject" onclick="submitSingleAction('deferment', '<?= $row['form_id'] ?>', 'REJECTED', 'deferment')">Reject</button>
                                <a href="generate_pdf.php?type=deferment&id=<?= $row['form_id'] ?>" target="_blank" class="btn-pdf">📄 PDF</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No submissions found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <!-- TAB 3: WITHDRAWAL -->
    <div id="tab-withdrawal" class="tab-content <?= $activeTab === 'withdrawal' ? 'active' : '' ?>">
        <div class="section-header">
            <div>
                <h2 style="display:inline-block; margin:0; color: var(--nmuc-navy);">3. Withdrawal Requests</h2>
                <span class="global-badge <?= $withdrawalClosed ? 'badge-closed-header' : 'badge-open' ?>">
                    Form Status: <?= $withdrawalClosed ? 'CLOSED' : 'OPEN' ?>
                </span>
            </div>
            <div class="header-actions">
                <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to <?= $withdrawalClosed ? 'RE-OPEN' : 'CLOSE' ?> submissions for Withdrawal forms?');">
                    <input type="hidden" name="action_type" value="toggle_global_form_status">
                    <input type="hidden" name="setting_key" value="status_withdrawal">
                    <input type="hidden" name="current_status" value="<?= $withdrawalClosed ? 'Closed' : 'Active' ?>">
                    <input type="hidden" name="active_tab" value="withdrawal">
                    <button type="submit" class="btn-toggle-global <?= $withdrawalClosed ? 'btn-toggle-reopen' : 'btn-toggle-close' ?>">
                        <?= $withdrawalClosed ? '[+] Re-Open Form' : '[-] Close Form' ?>
                    </button>
                </form>
                <a href="export_excel.php?type=withdrawal" class="btn-export">
                    Export to Google Sheets (.csv)
                </a>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="action_type" value="delete_selected">
            <input type="hidden" name="table_type" value="withdrawal">
            <input type="hidden" name="active_tab" value="withdrawal">

            <div style="margin-bottom: 10px; text-align: right;">
                <button type="button" class="btn-select-mode" id="btn-select-withdrawal" onclick="enableSelection('withdrawal')">☑ Select Records</button>
                <div id="controls-withdrawal" style="display: none;">
                    <button type="submit" class="btn-delete-selected" onclick="return confirmDeletion(event, 'withdraw-cb');">🗑️ Delete Selected</button>
                    <button type="button" class="btn-cancel-select" onclick="disableSelection('withdrawal', 'withdraw-cb', 'selectAllWithdraw')">Cancel</button>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="cb-col cb-col-withdrawal">
                                <input type="checkbox" id="selectAllWithdraw" onclick="toggleSelectAll(this, 'withdraw-cb')">
                            </th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Programme</th>
                            <th>Sponsor</th>
                            <th>Reasons</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($withdrawList)): ?>
                        <?php foreach ($withdrawList as $row): ?>
                        <?php $statusDisplay = parseStatus($row['office_acknowledged_by'] ?? 'Pending'); ?>
                        <tr>
                            <td class="cb-col cb-col-withdrawal">
                                <input type="checkbox" name="selected_ids[]" value="<?= $row['form_id'] ?>" class="withdraw-cb">
                            </td>
                            <td><?= htmlspecialchars($row['student_matrix_no']) ?></td>
                            <td><?= htmlspecialchars($row['full_name'] ?? 'No Name Recorded') ?></td>
                            <td><?= htmlspecialchars($row['programme']) ?></td>
                            <td><?= htmlspecialchars($row['sponsor']) ?></td>
                            <td><?= htmlspecialchars($row['withdrawal_reasons']) ?></td>
                            <td><span class="status-<?= $statusDisplay ?>"><?= $statusDisplay ?></span></td>
                            <td class="action-cell">
                                <button type="button" class="btn-approve" onclick="submitSingleAction('withdrawal', '<?= $row['form_id'] ?>', 'APPROVED', 'withdrawal')">Accept</button>
                                <button type="button" class="btn-reject" onclick="submitSingleAction('withdrawal', '<?= $row['form_id'] ?>', 'REJECTED', 'withdrawal')">Reject</button>
                                <a href="generate_pdf.php?type=withdrawal&id=<?= $row['form_id'] ?>" target="_blank" class="btn-pdf">📄 PDF</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No submissions found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

</div>

<!-- Hidden dynamic form for row actions -->
<form id="singleActionForm" method="POST" style="display:none;">
    <input type="hidden" name="action_type" id="sa_action_type">
    <input type="hidden" name="form_id" id="sa_form_id">
    <input type="hidden" name="status" id="sa_status">
    <input type="hidden" name="active_tab" id="sa_active_tab">
</form>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    document.getElementById('tab-' + tabName).classList.add('active');
    
    // Highlight button based on current event or match
    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(btn => {
        if (btn.getAttribute('onclick').includes(tabName)) {
            btn.classList.add('active');
        }
    });

    history.replaceState(null, null, '?tab=' + tabName);
}

function enableSelection(tabKey) {
    document.getElementById('btn-select-' + tabKey).style.display = 'none';
    document.getElementById('controls-' + tabKey).style.display = 'inline-block';
    
    document.querySelectorAll('.cb-col-' + tabKey).forEach(col => {
        col.style.display = 'table-cell';
    });
}

function disableSelection(tabKey, itemClass, masterId) {
    document.getElementById('controls-' + tabKey).style.display = 'none';
    document.getElementById('btn-select-' + tabKey).style.display = 'inline-block';
    
    document.querySelectorAll('.cb-col-' + tabKey).forEach(col => {
        col.style.display = 'none';
    });

    document.querySelectorAll('.' + itemClass).forEach(cb => cb.checked = false);
    const master = document.getElementById(masterId);
    if (master) master.checked = false;
}

function toggleSelectAll(master, targetClass) {
    const checkboxes = document.querySelectorAll('.' + targetClass);
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
    });
}

function confirmDeletion(event, targetClass) {
    const checked = document.querySelectorAll('.' + targetClass + ':checked');
    if (checked.length === 0) {
        alert('Please select at least one record to delete.');
        event.preventDefault();
        return false;
    }
    if (!confirm('Are you sure you want to delete ' + checked.length + ' selected record(s)? This action cannot be undone.')) {
        event.preventDefault(); 
        return false;
    }
    return true;
}

function submitSingleAction(actionType, formId, status, activeTab) {
    document.getElementById('sa_action_type').value = actionType;
    document.getElementById('sa_form_id').value     = formId;
    document.getElementById('sa_status').value      = status;
    document.getElementById('sa_active_tab').value  = activeTab;
    document.getElementById('singleActionForm').submit();
}
</script>

</body>
</html>