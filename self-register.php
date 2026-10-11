<?php
// self-register.php - Next-Gen Self-Service Visitor Pre-Registration & Digital NDA Portal
include('db.php');

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
    $fromdate = mysqli_real_escape_string($conn, trim($_POST['fromdate']));
    $todate = mysqli_real_escape_string($conn, trim($_POST['todate']));
    $reason = mysqli_real_escape_string($conn, trim($_POST['reason']));
    $security_clearance = mysqli_real_escape_string($conn, trim($_POST['security_clearance'] ?: 'Standard'));
    $host_name = mysqli_real_escape_string($conn, trim($_POST['host_name'] ?: 'Security Operations'));
    $host_department = mysqli_real_escape_string($conn, trim($_POST['host_department'] ?: 'General Administration'));
    $nda_signed = isset($_POST['nda_agreement']) ? 1 : 0;
    $nda_signature_data = mysqli_real_escape_string($conn, trim($_POST['nda_signature_data'] ?? ''));

    $passnumber = mt_rand(100000000, 999999999);
    $hmacHash = generatePassHMAC($passnumber, $idcardno, $todate, $category);

    $sql = "INSERT INTO passes(pass_number, category, full_name, contact_number, email, identity_type, identity_card_no, vehicle_no, security_clearance, host_name, host_department, from_date, to_date, reason, status, hmac_hash, nda_signed, nda_signature_data) 
            VALUES ('$passnumber', '$category', '$fullname', '$contactno', '$email', '$idtype', '$idcardno', '$vehicle_no', '$security_clearance', '$host_name', '$host_department', '$fromdate', '$todate', '$reason', 'Approved', '$hmacHash', $nda_signed, '$nda_signature_data')";
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
            'hmac_hash' => $hmacHash,
            'nda_signed' => $nda_signed,
            'nda_signature_data' => $nda_signature_data
        ];
        logCheckpointEvent($conn, $passnumber, $fullname, 'PRE_REGISTERED', 'Web Portal', 'SELF-SERVICE', 99.5, $vehicle_no, "Self-service pre-registered with host approval ($host_name) & digital NDA e-signature");
    } else {
        $message = "<div class='alert-msg text-red'><i class='fas fa-exclamation-circle'></i> Registration failed: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Pre-Registration Portal | GatePass Nexus</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- QR Code Library for Instant Client-side Cryptographic Rendering -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --primary: #00ffc3;
            --primary-glow: rgba(0, 255, 195, 0.4);
            --bg-dark: #091319;
            --card-bg: rgba(18, 28, 38, 0.9);
            --border-glow: rgba(0, 255, 195, 0.28);
            --text-main: #d7ffff;
            --text-dim: #8da4af;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #091319 0%, #102430 50%, #153243 100%);
            min-height: 100vh;
            margin: 0;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .portal-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glow);
            border-radius: 24px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.85);
            width: 100%;
            max-width: 760px;
            padding: 38px 42px;
            position: relative;
        }
        .portal-header {
            text-align: center;
            margin-bottom: 26px;
        }
        .portal-header h1 {
            color: var(--primary);
            font-weight: 900;
            font-size: 1.9rem;
            margin: 0;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .portal-header p {
            color: var(--text-dim);
            font-size: 0.88rem;
            margin-top: 6px;
        }
        .section-divider {
            grid-column: span 2;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0 6px 0;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.07em;
            color: var(--primary);
            text-transform: uppercase;
        }
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(0, 255, 195, 0.25);
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
            padding: 10px 14px;
            color: #fff;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 14px var(--primary-glow);
            background: rgba(255,255,255,0.1);
        }
        select option {
            background: #102430;
            color: #fff;
        }
        /* Signature Canvas Pad */
        .sig-container {
            border: 1.5px dashed rgba(0, 255, 195, 0.4);
            border-radius: 12px;
            background: rgba(9, 19, 25, 0.8);
            position: relative;
            padding: 4px;
            text-align: center;
        }
        canvas#signatureCanvas {
            width: 100%;
            height: 110px;
            display: block;
            cursor: crosshair;
            background: transparent;
        }
        .sig-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 10px;
            border-top: 1px solid rgba(0, 255, 195, 0.15);
            font-size: 0.72rem;
            color: var(--text-dim);
        }
        .btn-clear-sig {
            background: none;
            border: none;
            color: #ff6b6b;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .btn-clear-sig:hover { text-decoration: underline; }
        .nda-checkbox-box {
            background: rgba(0, 255, 195, 0.06);
            border: 1px solid rgba(0, 255, 195, 0.2);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.8rem;
            color: #b0d9d9;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.4;
        }
        .nda-checkbox-box input[type="checkbox"] {
            margin-top: 3px;
            accent-color: var(--primary);
            cursor: pointer;
            transform: scale(1.15);
        }
        .btn-submit {
            grid-column: span 2;
            background: linear-gradient(135deg, #00ffc3, #00bfa1);
            color: #053027;
            font-weight: 800;
            font-size: 1rem;
            padding: 15px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 20px var(--primary-glow);
            transition: all 0.2s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.05em;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 255, 195, 0.7);
        }
        .alert-msg {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.88rem;
        }
        .text-red {
            background: rgba(255, 82, 82, 0.15);
            border: 1px solid rgba(255, 82, 82, 0.4);
            color: #ff5252;
        }

        /* Success Pass Card (Printable Badge) */
        .pass-badge-result {
            background: rgba(12, 22, 30, 0.95);
            border: 2px solid var(--primary);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 0 35px var(--primary-glow);
            position: relative;
        }
        .badge-header-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 255, 195, 0.25);
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .badge-org-title {
            text-align: left;
        }
        .badge-org-title h3 {
            margin: 0;
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: 900;
            letter-spacing: 0.08em;
        }
        .badge-org-title span {
            font-size: 0.72rem;
            color: var(--text-dim);
            letter-spacing: 0.05em;
        }
        .clearance-pill {
            background: rgba(0, 255, 195, 0.15);
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.74rem;
            font-weight: 800;
        }
        .qr-display-box {
            display: inline-block;
            background: #fff;
            padding: 12px;
            border-radius: 14px;
            margin: 14px 0;
            box-shadow: 0 6px 24px rgba(0,0,0,0.6);
        }
        .pass-meta-table {
            width: 100%;
            margin: 16px 0;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }
        .pass-meta-table td {
            padding: 7px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .pass-meta-table td:first-child {
            color: #799ea8;
            font-weight: 700;
            width: 38%;
        }
        .sig-preview-img {
            max-height: 40px;
            border-bottom: 1px dashed rgba(0, 255, 195, 0.4);
            filter: invert(1) hue-rotate(180deg);
        }
        .btn-action-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 22px;
            flex-wrap: wrap;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(0, 255, 195, 0.35);
            color: #24ffe3;
            padding: 11px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: rgba(0, 255, 195, 0.2);
            color: #fff;
            box-shadow: 0 0 16px var(--primary-glow);
        }
        .back-nav {
            margin-top: 24px;
            text-align: center;
            font-size: 0.82rem;
        }
        .back-nav a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        /* Print Media Styling for <2s Badge Dispatch */
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
                padding: 0 !important;
            }
            .portal-card {
                background: #fff !important;
                border: 2px solid #000 !important;
                box-shadow: none !important;
                color: #000 !important;
                max-width: 100% !important;
                padding: 10px !important;
            }
            .btn-action-group, .back-nav {
                display: none !important;
            }
            .pass-badge-result {
                border: 2px solid #000 !important;
                box-shadow: none !important;
                color: #000 !important;
                background: #fff !important;
            }
            .pass-meta-table td {
                color: #000 !important;
                border-bottom: 1px solid #ccc !important;
            }
            .pass-meta-table td:first-child {
                color: #333 !important;
            }
            .badge-org-title h3, .clearance-pill {
                color: #000 !important;
            }
        }
    </style>
</head>
<body>

<div class="portal-card">
    <?php if ($issuedPass): ?>
        <div class="pass-badge-result" id="printablePass">
            <div class="badge-header-strip">
                <div class="badge-org-title">
                    <h3><i class="fas fa-shield-alt"></i> GATEPASS NEXUS</h3>
                    <span>CENTRAL ACCESS CONTROL BADGE</span>
                </div>
                <span class="clearance-pill"><?php echo htmlspecialchars($issuedPass['security_clearance']); ?> CLEARANCE</span>
            </div>

            <div class="qr-display-box" id="qrcode"></div>

            <table class="pass-meta-table">
                <tr><td>Pass Number</td><td><strong style="color:var(--primary); font-size:1.15rem;"><?php echo htmlspecialchars($issuedPass['pass_number']); ?></strong></td></tr>
                <tr><td>Visitor Name</td><td><strong><?php echo htmlspecialchars($issuedPass['full_name']); ?></strong></td></tr>
                <tr><td>Category</td><td><?php echo htmlspecialchars($issuedPass['category']); ?></td></tr>
                <tr><td>Identity Card</td><td><?php echo htmlspecialchars($issuedPass['identity_type'] . ': ' . $issuedPass['identity_card_no']); ?></td></tr>
                <tr><td>Vehicle (ALPR)</td><td><?php echo htmlspecialchars($issuedPass['vehicle_no']); ?></td></tr>
                <tr><td>Internal Host</td><td><?php echo htmlspecialchars($issuedPass['host_name'] . ' (' . $issuedPass['host_department'] . ')'); ?></td></tr>
                <tr><td>Valid Window</td><td><?php echo htmlspecialchars($issuedPass['from_date'] . ' to ' . $issuedPass['to_date']); ?></td></tr>
                <tr><td>NDA Status</td><td><span style="color:#00ff88;"><i class="fas fa-check-shield"></i> Executed & Digitally Signed</span></td></tr>
                <tr><td>HMAC Hash</td><td><code style="color:var(--primary); font-size:0.75rem;"><?php echo substr($issuedPass['hmac_hash'], 0, 24); ?>...</code></td></tr>
            </table>

            <div class="btn-action-group">
                <button class="btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Instant Badge Print</button>
                <a href="checkpoint-terminal.php?scan=<?php echo urlencode($issuedPass['pass_number']); ?>" class="btn-secondary"><i class="fas fa-barcode"></i> Test Checkpoint Verification</a>
                <a href="self-register.php" class="btn-secondary"><i class="fas fa-plus"></i> New Pass</a>
            </div>
        </div>

        <script>
            // Generate QR Code Payload matching enterprise format
            const payload = <?php echo json_encode(generateQRPayload($issuedPass)); ?>;
            new QRCode(document.getElementById("qrcode"), {
                text: payload,
                width: 160,
                height: 160,
                colorDark : "#051821",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        </script>

    <?php else: ?>

        <div class="portal-header">
            <h1><i class="fas fa-id-badge text-cyan"></i> GATEPASS NEXUS</h1>
            <p>Self-Service Visitor Access Registration & Host Pre-Approval</p>
        </div>

        <?php echo $message; ?>

        <form method="POST" action="" id="regForm" onsubmit="return handleFormSubmit()">
            <input type="hidden" name="nda_signature_data" id="ndaSignatureData">

            <div class="form-grid">
                <!-- Section 1: Visitor Identity -->
                <div class="section-divider">
                    <i class="fas fa-user-circle"></i> 1. Visitor Identity & Credentials
                </div>

                <div class="form-group">
                    <label>VISITOR FULL NAME *</label>
                    <input type="text" name="fullname" placeholder="e.g. Vikramaditya Rao" required>
                </div>
                <div class="form-group">
                    <label>ACCESS CATEGORY *</label>
                    <select name="category" required>
                        <option value="Visitor">Visitor</option>
                        <option value="Contractor">Contractor / Vendor</option>
                        <option value="VIP Delegate">VIP Delegate</option>
                        <option value="Maintenance">Maintenance & Logistics</option>
                        <option value="Security Auditor">Security Auditor</option>
                        <option value="Candidate">Interviewee / Candidate</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>CONTACT PHONE NUMBER *</label>
                    <input type="tel" name="contactno" placeholder="e.g. 9876543210" required>
                </div>
                <div class="form-group">
                    <label>EMAIL ADDRESS *</label>
                    <input type="email" name="email" placeholder="e.g. visitor@nexusdefense.com" required>
                </div>
                <div class="form-group">
                    <label>GOVERNMENT ID TYPE *</label>
                    <select name="idtype" required>
                        <option value="Aadhaar Card">Aadhaar Card</option>
                        <option value="Passport">Passport</option>
                        <option value="Driving License">Driving License</option>
                        <option value="Voter ID">Voter ID</option>
                        <option value="Corporate Badge">Corporate Badge</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>GOVERNMENT ID NUMBER *</label>
                    <input type="text" name="idcardno" placeholder="e.g. 5420-1928-8812" required>
                </div>
                <div class="form-group">
                    <label>VEHICLE LICENSE PLATE (ALPR)</label>
                    <input type="text" name="vehicle_no" placeholder="e.g. DL-08-AX-9942">
                </div>
                <div class="form-group">
                    <label>SECURITY CLEARANCE TIER</label>
                    <select name="security_clearance">
                        <option value="Standard">Standard Facility Access</option>
                        <option value="Confidential">Confidential (Level 2)</option>
                        <option value="Secret">Secret (Level 3 - Escort Req)</option>
                        <option value="VIP Delegate">VIP Delegate (Fast-Track)</option>
                    </select>
                </div>

                <!-- Section 2: Host Approval & Temporal Window -->
                <div class="section-divider">
                    <i class="fas fa-building"></i> 2. Host Approval & Temporal Window
                </div>

                <div class="form-group">
                    <label>INTERNAL HOST CONTACT *</label>
                    <input type="text" name="host_name" placeholder="e.g. Dr. Samantha Vance" required>
                </div>
                <div class="form-group">
                    <label>HOST DEPARTMENT *</label>
                    <input type="text" name="host_department" placeholder="e.g. AI Research / Cybersecurity" required>
                </div>
                <div class="form-group">
                    <label>VALID FROM DATE *</label>
                    <input type="date" name="fromdate" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>VALID UNTIL DATE *</label>
                    <input type="date" name="todate" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
                </div>
                <div class="form-group full-width">
                    <label>PURPOSE OF VISIT / PROJECT REFERENCE *</label>
                    <textarea name="reason" rows="2" placeholder="Specify visit objective, escort protocol, or meeting agenda..." required></textarea>
                </div>

                <!-- Section 3: Digital NDA Agreement & E-Signature Pad -->
                <div class="section-divider">
                    <i class="fas fa-file-contract"></i> 3. Security Policy & Digital NDA E-Signature
                </div>

                <div class="form-group full-width">
                    <div class="nda-checkbox-box">
                        <input type="checkbox" name="nda_agreement" id="ndaAgreement" required>
                        <label for="ndaAgreement" style="margin:0; cursor:pointer; color:#c4f9f4;">
                            I hereby agree to the <strong>Nexus Facility Non-Disclosure Agreement (NDA)</strong> and Premises Security Protocols. I certify that all biometric & vehicle credentials provided are authentic and agree to automated temporal monitoring while on premises.
                        </label>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-pen-nib"></i> VISITOR DIGITAL E-SIGNATURE (DRAW WITH MOUSE OR TOUCH) *</label>
                    <div class="sig-container">
                        <canvas id="signatureCanvas" height="110"></canvas>
                        <div class="sig-actions">
                            <span>Touch or drag above to sign digitally</span>
                            <button type="button" class="btn-clear-sig" onclick="clearSignature()"><i class="fas fa-eraser"></i> Clear Signature</button>
                        </div>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-shield-alt"></i> SUBMIT, VERIFY & DISPATCH DIGITAL PASS
                </button>
            </div>
        </form>

        <div class="back-nav">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Return to Admin Control Panel</a>
        </div>

        <script>
            // HTML5 Canvas Digital Signature Pad Implementation
            const canvas = document.getElementById('signatureCanvas');
            const ctx = canvas.getContext('2d');
            let isDrawing = false;
            let hasDrawn = false;

            function resizeCanvas() {
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width;
                canvas.height = 110;
                ctx.lineWidth = 2.5;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#00ffc3';
            }
            window.addEventListener('resize', resizeCanvas);
            resizeCanvas();

            function getPos(e) {
                const rect = canvas.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top
                };
            }

            function startDraw(e) {
                isDrawing = true;
                hasDrawn = true;
                const pos = getPos(e);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
                e.preventDefault();
            }

            function draw(e) {
                if (!isDrawing) return;
                const pos = getPos(e);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
                e.preventDefault();
            }

            function stopDraw() {
                isDrawing = false;
            }

            canvas.addEventListener('mousedown', startDraw);
            canvas.addEventListener('mousemove', draw);
            window.addEventListener('mouseup', stopDraw);

            canvas.addEventListener('touchstart', startDraw, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            window.addEventListener('touchend', stopDraw);

            function clearSignature() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasDrawn = false;
            }

            function handleFormSubmit() {
                if (!hasDrawn) {
                    alert("Please provide your digital NDA e-signature in the signature box before submitting.");
                    return false;
                }
                document.getElementById('ndaSignatureData').value = canvas.toDataURL('image/png');
                return true;
            }
        </script>

    <?php endif; ?>
</div>

</body>
</html>
