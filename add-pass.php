<?php
// add-pass.php - Administrative GatePass Issuance with HMAC-SHA256 & Instant Badge Generation
session_start();
include('db.php');

if (!isset($_SESSION['admin_id']) || strlen($_SESSION['admin_id']) == 0) {
    header("location:logout.php");
    exit;
}

$message = '';
$issuedPass = null;

if (isset($_POST['submit'])) {
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $contactno = mysqli_real_escape_string($conn, trim($_POST['contactno']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $idtype = mysqli_real_escape_string($conn, trim($_POST['idtype']));
    $idcardno = mysqli_real_escape_string($conn, trim($_POST['idcardno']));
    $vehicle_no = mysqli_real_escape_string($conn, trim($_POST['vehicle_no'] ?: 'N/A'));
    $security_clearance = mysqli_real_escape_string($conn, trim($_POST['security_clearance'] ?: 'Standard'));
    $host_name = mysqli_real_escape_string($conn, trim($_POST['host_name'] ?: 'Security Operations'));
    $host_department = mysqli_real_escape_string($conn, trim($_POST['host_department'] ?: 'Operations'));
    $fromdate = mysqli_real_escape_string($conn, trim($_POST['fromdate']));
    $todate = mysqli_real_escape_string($conn, trim($_POST['todate']));
    $reason = mysqli_real_escape_string($conn, trim($_POST['reason']));

    $passnumber = mt_rand(100000000, 999999999);
    $hmacHash = generatePassHMAC($passnumber, $idcardno, $todate, $category);

    $sql = "INSERT INTO passes(pass_number, category, full_name, contact_number, email, identity_type, identity_card_no, vehicle_no, security_clearance, host_name, host_department, from_date, to_date, reason, status, hmac_hash) 
            VALUES ('$passnumber', '$category', '$fullname', '$contactno', '$email', '$idtype', '$idcardno', '$vehicle_no', '$security_clearance', '$host_name', '$host_department', '$fromdate', '$todate', '$reason', 'Approved', '$hmacHash')";
    $query = mysqli_query($conn, $sql);

    if ($query) {
        $issuedPass = [
            'id' => mysqli_insert_id($conn),
            'pass_number' => $passnumber,
            'full_name' => $fullname,
            'category' => $category,
            'security_clearance' => $security_clearance,
            'vehicle_no' => $vehicle_no,
            'host_name' => $host_name,
            'host_department' => $host_department,
            'from_date' => $fromdate,
            'to_date' => $todate,
            'identity_type' => $idtype,
            'identity_card_no' => $idcardno,
            'hmac_hash' => $hmacHash
        ];
        logCheckpointEvent($conn, $passnumber, $fullname, 'ADMIN_ISSUED', 'Admin Terminal', 'ADMIN-' . $_SESSION['admin_id'], 99.8, $vehicle_no, "Pass officially dispatched by administrator with clearance $security_clearance");
        $message = "<div class='alert-success-toast'><i class='fas fa-check-circle'></i> GatePass #$passnumber generated with HMAC-SHA256 signature!</div>";
    } else {
        $message = "<div class='alert-danger-toast'><i class='fas fa-exclamation-triangle'></i> Error issuing pass: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
    }
}

$active_page = 'add-pass.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
        .issue-card {
            background: rgba(18, 28, 38, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 255, 195, 0.28);
            border-radius: 24px;
            padding: 34px 38px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.6);
            max-width: 820px;
            margin: 0 auto 30px auto;
        }
        .issue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 255, 195, 0.2);
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .issue-header h2 {
            margin: 0;
            color: #24ffe3;
            font-size: 1.6rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .badge-pill-cyan {
            background: rgba(0, 255, 195, 0.15);
            border: 1px solid rgba(0, 255, 195, 0.4);
            color: #00ffc3;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 700;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        label {
            font-size: 0.74rem;
            font-weight: 700;
            color: #799ea8;
            margin-bottom: 6px;
            letter-spacing: 0.04em;
        }
        input, select, textarea {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(0, 255, 195, 0.25);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #00ffc3;
            box-shadow: 0 0 14px rgba(0, 255, 195, 0.35);
            background: rgba(255,255,255,0.1);
        }
        select option {
            background: #102430;
            color: #fff;
        }
        .btn-issue {
            grid-column: span 2;
            background: linear-gradient(135deg, #00ffc3, #00bfa1);
            color: #053027;
            font-weight: 800;
            font-size: 1rem;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 255, 195, 0.4);
            transition: all 0.2s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-issue:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 255, 195, 0.6);
        }
        .alert-success-toast {
            background: rgba(0, 255, 195, 0.15);
            border: 1px solid #00ffc3;
            color: #00ffc3;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .alert-danger-toast {
            background: rgba(255, 82, 82, 0.15);
            border: 1px solid #ff5252;
            color: #ff5252;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Success Dispatch Result Card */
        .dispatch-card {
            background: rgba(12, 22, 30, 0.95);
            border: 2px solid #00ffc3;
            border-radius: 20px;
            padding: 26px;
            text-align: center;
            box-shadow: 0 0 35px rgba(0, 255, 195, 0.4);
            margin-bottom: 26px;
        }
        .qr-wrap {
            display: inline-block;
            background: #fff;
            padding: 12px;
            border-radius: 14px;
            margin: 14px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.6);
        }
        .dispatch-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            text-align: left;
            margin: 16px 0;
            font-size: 0.86rem;
        }
        .dispatch-meta-item {
            background: rgba(255,255,255,0.04);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(0, 255, 195, 0.15);
        }
        .dispatch-meta-item span {
            display: block;
            font-size: 0.7rem;
            color: #799ea8;
            font-weight: 700;
        }
        .dispatch-meta-item strong {
            color: #fff;
        }
        .dispatch-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 18px;
        }
        .btn-dispatch-action {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(0, 255, 195, 0.35);
            color: #24ffe3;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-dispatch-action:hover {
            background: rgba(0, 255, 195, 0.2);
            color: #fff;
            box-shadow: 0 0 16px rgba(0, 255, 195, 0.4);
        }
    </style>

<?php echo $message; ?>

    <?php if ($issuedPass): ?>
        <div class="dispatch-card" id="printableBadge">
            <h3 style="color:#00ffc3; margin:0;"><i class="fas fa-shield-check"></i> OFFICIAL GATE PASS DISPATCHED</h3>
            <p style="color:#8da4af; font-size:0.85rem; margin:4px 0 12px 0;">Cryptographic HMAC-SHA256 Token Generated • Ready for Checkpoint Ingress</p>

            <div class="qr-wrap" id="badgeQr"></div>

            <div class="dispatch-meta-grid">
                <div class="dispatch-meta-item">
                    <span>PASS NUMBER</span>
                    <strong style="color:#00ffc3; font-size:1.1rem;"><?php echo htmlspecialchars($issuedPass['pass_number']); ?></strong>
                </div>
                <div class="dispatch-meta-item">
                    <span>SECURITY CLEARANCE</span>
                    <strong style="color:#ffd600;"><?php echo htmlspecialchars($issuedPass['security_clearance']); ?></strong>
                </div>
                <div class="dispatch-meta-item">
                    <span>VISITOR NAME</span>
                    <strong><?php echo htmlspecialchars($issuedPass['full_name']); ?></strong>
                </div>
                <div class="dispatch-meta-item">
                    <span>CATEGORY</span>
                    <strong><?php echo htmlspecialchars($issuedPass['category']); ?></strong>
                </div>
                <div class="dispatch-meta-item">
                    <span>NATIONAL ID CARD</span>
                    <strong><?php echo htmlspecialchars($issuedPass['identity_type'] . ': ' . $issuedPass['identity_card_no']); ?></strong>
                </div>
                <div class="dispatch-meta-item">
                    <span>VEHICLE REG (ALPR)</span>
                    <strong><?php echo htmlspecialchars($issuedPass['vehicle_no']); ?></strong>
                </div>
                <div class="dispatch-meta-item">
                    <span>INTERNAL HOST</span>
                    <strong><?php echo htmlspecialchars($issuedPass['host_name'] . ' (' . $issuedPass['host_department'] . ')'); ?></strong>
                </div>
                <div class="dispatch-meta-item">
                    <span>VALIDITY WINDOW</span>
                    <strong><?php echo htmlspecialchars($issuedPass['from_date'] . ' to ' . $issuedPass['to_date']); ?></strong>
                </div>
            </div>

            <div class="dispatch-actions">
                <button class="btn-dispatch-action" onclick="window.print()"><i class="fas fa-print"></i> Instant Print Badge (&lt;2s)</button>
                <a href="checkpoint-terminal.php?scan=<?php echo urlencode($issuedPass['pass_number']); ?>" class="btn-dispatch-action"><i class="fas fa-barcode"></i> Ingress at Checkpoint Alpha</a>
                <a href="view-pass-detail.php?id=<?php echo $issuedPass['id']; ?>" class="btn-dispatch-action"><i class="fas fa-eye"></i> View Dossier</a>
                <a href="add-pass.php" class="btn-dispatch-action"><i class="fas fa-plus"></i> Issue Another</a>
            </div>
        </div>

        <script>
            const qrPayload = <?php echo json_encode(generateQRPayload($issuedPass)); ?>;
            new QRCode(document.getElementById("badgeQr"), {
                text: qrPayload,
                width: 150,
                height: 150,
                colorDark : "#051821",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        </script>
    <?php endif; ?>

    <div class="issue-card">
        <div class="issue-header">
            <h2><i class="fas fa-plus-circle"></i> ISSUE NEW CREDENTIAL</h2>
            <span class="badge-pill-cyan">HMAC-SHA256 SIGNATURE ENGINE</span>
        </div>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>VISITOR FULL NAME *</label>
                    <input type="text" name="fullname" placeholder="e.g. Elena Rostova" required>
                </div>
                <div class="form-group">
                    <label>ACCESS CATEGORY *</label>
                    <select name="category" required>
                        <option value="Visitor">Visitor</option>
                        <option value="Contractor">Contractor / Vendor</option>
                        <option value="VIP Delegate">VIP Delegate</option>
                        <option value="Maintenance">Maintenance & Facilities</option>
                        <option value="Security Auditor">Security Auditor</option>
                        <option value="Research Scientist">Research Scientist</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>CONTACT PHONE *</label>
                    <input type="tel" name="contactno" placeholder="e.g. 9811223344" required>
                </div>
                <div class="form-group">
                    <label>EMAIL ADDRESS *</label>
                    <input type="email" name="email" placeholder="e.g. visitor@nexusdefense.com" required>
                </div>
                <div class="form-group">
                    <label>IDENTITY CARD TYPE *</label>
                    <select name="idtype" required>
                        <option value="Aadhaar Card">Aadhaar Card</option>
                        <option value="Passport">Passport</option>
                        <option value="Driving License">Driving License</option>
                        <option value="Voter ID">Voter ID</option>
                        <option value="Corporate Badge">Corporate Badge</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>IDENTITY CARD NUMBER *</label>
                    <input type="text" name="idcardno" placeholder="e.g. 5320-5452-885" required>
                </div>
                <div class="form-group">
                    <label>VEHICLE REGISTRATION (ALPR)</label>
                    <input type="text" name="vehicle_no" placeholder="e.g. DL-04-CX-8192">
                </div>
                <div class="form-group">
                    <label>SECURITY CLEARANCE TIER *</label>
                    <select name="security_clearance" required>
                        <option value="Standard">Standard Facility Access</option>
                        <option value="Confidential">Confidential (Level 2)</option>
                        <option value="Secret">Secret (Level 3 - Escort Req)</option>
                        <option value="VIP Delegate">VIP Delegate (Fast-Track)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>INTERNAL HOST CONTACT *</label>
                    <input type="text" name="host_name" placeholder="e.g. Dr. Samantha Vance" required>
                </div>
                <div class="form-group">
                    <label>HOST DEPARTMENT *</label>
                    <input type="text" name="host_department" placeholder="e.g. Cyber Command" required>
                </div>
                <div class="form-group">
                    <label>FROM VALIDITY DATE *</label>
                    <input type="date" name="fromdate" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>TO EXPIRATION DATE *</label>
                    <input type="date" name="todate" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
                </div>
                <div class="form-group full-width">
                    <label>PURPOSE OF VISIT / OPERATIONAL DETAILS *</label>
                    <textarea name="reason" rows="2" placeholder="Specify meeting agenda, facility area access, or inspection reference..." required></textarea>
                </div>
                <button type="submit" name="submit" class="btn-issue">
                    <i class="fas fa-fingerprint"></i> GENERATE HMAC-SIGNED ACCESS PASS
                </button>
            </div>
        </form>
    </div>

<?php include 'includes/footer.php'; ?>
