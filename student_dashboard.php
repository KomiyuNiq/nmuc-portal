<?php
session_start();
require 'db.php';

// Fetch form status settings set by staff
$settingsQuery = $pdo->query("SELECT setting_key, setting_value FROM global_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

$statusProg = $settingsQuery['status_change_programme'] ?? 'Active';
$statusDef  = $settingsQuery['status_deferment'] ?? 'Active';
$statusWith = $settingsQuery['status_withdrawal'] ?? 'Active';

$programmes = [
    "Foundation in Business", "Diploma In Maritime Law", "Diploma In Occupational Safety & Health",
    "Diploma In Maritime Transportation Management", "Diploma In Port Management", "Diploma In Shipping Management",
    "Diploma In Airlines Cabin Crew Services", "Bachelor In Maritime & Logistics (Honours)",
    "Bachelor In Occupational Safety and Health (Maritime)", "(TVET) Diploma Kemahiran Malaysia In Office Administration",
    "(TVET) Diploma Kemahiran Malaysia In Executive Secretaryship", "(TVET) Diploma Kemahiran Malaysia In Creative Content Development"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NMUC Student Application Portal</title>
    <link rel="stylesheet" href="style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --nmuc-navy: #032b43;
            --nmuc-cyan: #00a8e8;
            --nmuc-orange: #ff5722;
            --nmuc-bg-light: #eef4f8;
        }

        body {
            background-color: var(--nmuc-bg-light);
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: var(--nmuc-navy);
            color: #ffffff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid var(--nmuc-orange);
        }

        .header h1 {
            font-size: 1.4rem;
            margin: 0;
        }

        .header h1 span {
            color: var(--nmuc-cyan);
        }

        .portal-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .tab-nav {
            display: flex;
            gap: 10px;
            border-bottom: 3px solid var(--nmuc-navy);
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 12px 20px;
            background: #dbe4ec;
            color: var(--nmuc-navy);
            border: none;
            font-weight: bold;
            border-radius: 6px 6px 0 0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            background: var(--nmuc-navy);
            color: #fff;
            border-bottom: 3px solid var(--nmuc-orange);
        }

        .form-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(3, 43, 67, 0.08);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .full-width {
            grid-column: span 2;
        }

        .form-grid label {
            display: block;
            font-weight: 600;
            font-size: 0.88rem;
            margin-bottom: 5px;
        }

        .form-grid input, .form-grid select, .form-grid textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .declaration-box {
            background-color: #FFFDF0;
            border-left: 4px solid #D4AF37;
            padding: 15px 20px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .declaration-box div {
            margin-bottom: 10px;
        }

        .declaration-box label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            color: #333;
        }

        .declaration-box input[type="checkbox"] {
            margin-top: 3px;
        }

        .btn-submit {
            background-color: var(--nmuc-orange);
            color: white;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>NMUC <span>STUDENT APPLICATION PORTAL</span></h1>
</div>

<div class="portal-container">
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="alert-success">
            <strong>Application Submitted!</strong> Your request has been recorded. Updates and notifications will be sent directly to your email address.
        </div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'form_closed'): ?>
        <div class="alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
            This form application is currently closed by the administration.
        </div>
    <?php endif; ?>

    <?php if ($statusProg === 'Closed' && $statusDef === 'Closed' && $statusWith === 'Closed'): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 25px; border-radius: 8px; text-align: center;">
            <h3>All Application Forms are Currently Closed</h3>
            <p>Please contact the NMUC administration office for further assistance.</p>
        </div>
    <?php else: ?>

        <!-- TAB NAVIGATION -->
        <div class="tab-nav">
            <?php if ($statusProg !== 'Closed'): ?>
                <button class="tab-btn <?= ($statusProg !== 'Closed') ? 'active' : '' ?>" onclick="switchTab('prog', this)">Change Programme</button>
            <?php endif; ?>

            <?php if ($statusDef !== 'Closed'): ?>
                <button class="tab-btn <?= ($statusProg === 'Closed' && $statusDef !== 'Closed') ? 'active' : '' ?>" onclick="switchTab('def', this)">Course Deferment</button>
            <?php endif; ?>

            <?php if ($statusWith !== 'Closed'): ?>
                <button class="tab-btn <?= ($statusProg === 'Closed' && $statusDef === 'Closed' && $statusWith !== 'Closed') ? 'active' : '' ?>" onclick="switchTab('with', this)">Withdrawal Form</button>
            <?php endif; ?>
        </div>

        <div class="form-card">
            <!-- FORM 1: CHANGE PROGRAMME -->
            <?php if ($statusProg !== 'Closed'): ?>
            <div id="prog" class="form-content" style="<?= ($statusProg !== 'Closed') ? 'display:block;' : 'display:none;' ?>">
                <h3 style="margin-bottom: 15px; color: var(--nmuc-navy);">Change Programme Application</h3>
                <form action="change_programme.php" method="POST">
                    <div class="form-grid">
                        <div><label>Name</label><input type="text" name="full_name" required></div>
                        <div><label>Email Address (For Status Updates)</label><input type="email" name="email" required></div>
                        <div><label>NRIC / Passport No.</label><input type="text" name="nric_passport" required></div>
                        <div><label>ID No.</label><input type="text" name="student_id" required></div>
                        <div><label>Phone No.</label><input type="text" name="phone_no" required></div>
                        <div>
                            <label>Current Programme</label>
                            <select name="current_programme" required>
                                <option value="" disabled selected>-- Select Current Programme --</option>
                                <?php foreach ($programmes as $prog): ?><option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div><label>Current Semester</label><input type="text" name="semester" placeholder="e.g. 1" required></div>
                        <div class="full-width">
                            <label>New Programme Requested</label>
                            <select name="new_programme" required>
                                <option value="" disabled selected>-- Select New Programme Requested --</option>
                                <?php foreach ($programmes as $prog): ?><option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="full-width"><label>Reasons for Applying</label><textarea name="reasons" rows="3" required></textarea></div>
                    </div>

                    <div class="declaration-box">
                        <div>
                            <label>
                                <input type="checkbox" name="admin_fee" value="1" required>
                                <span>I am fully aware that I am required to pay an administrative fee RM150 for the change of programme and subject to the management approval.</span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="checkbox" name="course_fee_diff" value="1" required>
                                <span>I also agree that I will bear any differences in the course fee.</span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="checkbox" name="non_refundable" value="1" required>
                                <span>I am fully aware that any excess in course fees will not be refunded by the NMUC.</span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="checkbox" name="business_hours" value="1" required>
                                <span>Any form faxed/emailed outside business hours, during weekends or holidays will be processed and effective on the next business day and hour.</span>
                            </label>
                        </div>
                        <div>
                            <label style="font-weight: 600;">
                                <input type="checkbox" name="declaration" value="1" required>
                                <span>I hereby understand all the information stated in this form and declare that all particulars related to me are CORRECT and COMPLETE.</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Submit Change Programme Form</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- FORM 2: DEFERMENT -->
            <?php if ($statusDef !== 'Closed'): ?>
            <div id="def" class="form-content" style="<?= ($statusProg === 'Closed' && $statusDef !== 'Closed') ? 'display:block;' : 'display:none;' ?>">
                <h3 style="margin-bottom: 15px; color: var(--nmuc-navy);">Course Deferment Application</h3>
                <form action="deferment.php" method="POST">
                    <div class="form-grid">
                        <div><label>Name</label><input type="text" name="full_name" required></div>
                        <div><label>Type of Student</label><select name="student_type" required><option value="Local Student">Local Student</option><option value="International Student">International Student</option></select></div>
                        <div><label>NRIC / Passport No.</label><input type="text" name="nric_passport" required></div>
                        <div><label>ID No.</label><input type="text" name="student_id" required></div>
                        <div><label>Phone No.</label><input type="text" name="phone_no" required></div>
                        <div><label>Email Address (For Status Updates)</label><input type="email" name="email" required></div>
                        <div><label>Intake</label><input type="text" name="intake" required placeholder="e.g. 05/2023"></div>
                        <div><label>Current Semester</label><input type="text" name="current_sem" required placeholder="e.g. SEM 8"></div>
                        <div><label>Deferment Semester</label><input type="text" name="deferment_sem" required placeholder="e.g. TERM 2/2026"></div>
                        <div class="full-width">
                            <label>Programme</label>
                            <select name="programme" required>
                                <option value="" disabled selected>-- Select Programme --</option>
                                <?php foreach ($programmes as $prog): ?><option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="full-width"><label>Reasons</label><textarea name="reasons_for_deferment" rows="3" required></textarea></div>
                    </div>

                    <div class="declaration-box">
                        <div>
                            <label>
                                <input type="checkbox" name="ptptn_declaration" value="1" required>
                                <span>I declare that I understand the Terms and Conditions stipulated in the PTPTN Loan Agreement and I shall not hold NMUC responsible or liable in any way for any claims, damages, losses, expenses, costs in the event that I do not receive PTPTN payment for the semester that I defer and to make my own financial arrangements.</span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="checkbox" name="admin_fee" value="1" required>
                                <span>I am fully aware that I am required to pay an administrative fee RM100 for the deferment and subject to the management approval. I also agree that I will bear any differences in the course fee.</span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="checkbox" name="non_refundable" value="1" required>
                                <span>I am fully aware that any excess in course fees will not be refunded by the NMUC.</span>
                            </label>
                        </div>
                        <div>
                            <label style="font-weight: 600;">
                                <input type="checkbox" name="declaration" value="1" required>
                                <span>I hereby understand all the information stated in this form and declare that all particulars related to me are CORRECT and COMPLETE.</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Submit Course Deferment Request</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- FORM 3: WITHDRAWAL -->
            <?php if ($statusWith !== 'Closed'): ?>
            <div id="with" class="form-content" style="<?= ($statusProg === 'Closed' && $statusDef === 'Closed' && $statusWith !== 'Closed') ? 'display:block;' : 'display:none;' ?>">
                <h3 style="margin-bottom: 15px; color: var(--nmuc-navy);">Withdrawal Application Form</h3>
                <form action="withdrawal.php" method="POST">
                    <div class="form-grid">
                        <div><label>Name</label><input type="text" name="full_name" required></div>
                        <div><label>NRIC / Passport No.</label><input type="text" name="nric_passport" required></div>
                        <div><label>ID No.</label><input type="text" name="student_id" required></div>
                        <div><label>Student Phone No.</label><input type="text" name="phone_no" required></div>
                        <div><label>Parent Phone</label><input type="text" name="parent_phone_no" required></div>
                        <div><label>Email Address (For Status Updates)</label><input type="email" name="email" required></div>
                        <div><label>Type of Student</label><select name="student_type" required><option value="Local Student">Local Student</option><option value="International Student">International Student</option></select></div>
                        <div><label>Intake</label><input type="text" name="intake" required></div>
                        <div><label>Sponsor</label><input type="text" name="sponsor"></div>
                        <div class="full-width">
                            <label>Programme</label>
                            <select name="programme" required>
                                <option value="" disabled selected>-- Select Programme --</option>
                                <?php foreach ($programmes as $prog): ?><option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="full-width"><label>Address</label><textarea name="address" rows="2" required></textarea></div>
                        <div class="full-width"><label>Reasons</label><input type="text" name="withdrawal_reasons" required></div>
                    </div>

                    <div class="declaration-box">
                        <div>
                            <label>
                                <input type="checkbox" name="effective_date" value="1" required>
                                <span>I understand that my withdrawal application from ALL classes is effective from the date this form is processed and my tuition fees, if any, will be calculated based on that effective date and in accordance with the published refund schedule. Any form faxed/emailed outside business hours, during weekends or holidays will be processed and effective on the next business day.</span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="checkbox" name="dropping_all" value="1" required>
                                <span>I further understand that I am dropping ALL Classes and withdrawing from NMUC and I am fully aware of and will be bound by NMUC rules and regulations for this application.</span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="checkbox" name="counselor_contact" value="1" required>
                                <span>I also understand that my withdrawal application will only be processed after NMUC Counselor contacts me.</span>
                            </label>
                        </div>
                        <div>
                            <label style="font-weight: 600;">
                                <input type="checkbox" name="declaration" value="1" required>
                                <span>I hereby understand all the information stated in this form and declare that all particulars related to me are correct.</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Submit Withdrawal Application</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<script>
function switchTab(tabId, element) {
    document.querySelectorAll('.form-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).style.display = 'block';
    element.classList.add('active');
}
</script>

</body>
</html>