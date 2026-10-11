<?php
// view-pass-detail.php - Executive Access Dossier, HMAC QR Code & High-Speed Badge Dispatch
session_start();
include('db.php');

if (!isset($_SESSION['admin_id']) || strlen($_SESSION['admin_id']) == 0) {
    header("location:logout.php");
    exit;
}

$pass_id = intval($_GET['id'] ?? 0);
$ret = mysqli_query($conn, "SELECT * FROM passes WHERE id='$pass_id' LIMIT 1");
$row = mysqli_fetch_assoc($ret);
$not_found = !$row;

$active_page = 'manage-passes.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<!-- QR Code Library for Instant Client-side Cryptographic Rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
        .dossier-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }
        .dossier-header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-back {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(0, 255, 195, 0.25);
            color: #24ffe3;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: rgba(0, 255, 195, 0.15);
            color: #fff;
        }

        /* Glassmorphism Dossier Card */
        .card-dossier {
            background: rgba(18, 28, 38, 0.88);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(0, 255, 195, 0.28);
            border-radius: 24px;
            padding: 34px 38px;
            box-shadow: 0 15px 45px rgba(0,0,0,0.6);
            position: relative;
        }
        .dossier-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid rgba(0, 255, 195, 0.2);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .dossier-title-area h2 {
            margin: 0 0 6px 0;
            color: #24ffe3;
            font-size: 1.8rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .dossier-submeta {
            font-size: 0.85rem;
            color: #8da4af;
        }
        .status-badge-lg {
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .status-Approved { background: rgba(0, 255, 195, 0.15); color: #00ffc3; border: 1px solid #00ffc3; }
        .status-Checked-In { background: rgba(0, 153, 255, 0.15); color: #0099ff; border: 1px solid #0099ff; }
        .status-Checked-Out { background: rgba(158, 158, 158, 0.15); color: #b0bec5; border: 1px solid #78909c; }
        .status-Flagged { background: rgba(255, 82, 82, 0.15); color: #ff5252; border: 1px solid #ff5252; animation: pulseRed 1.4s infinite; }
        @keyframes pulseRed { 50% { opacity: 0.6; } }

        /* Two-Column Grid: Left QR & Badge, Right Metadata Table */
        .dossier-content-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 28px;
            margin-bottom: 28px;
        }
        @media (max-width: 768px) {
            .dossier-content-grid { grid-template-columns: 1fr; }
        }
        .badge-preview-column {
            background: rgba(12, 22, 30, 0.95);
            border: 1px solid rgba(0, 255, 195, 0.3);
            border-radius: 18px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 6px 25px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .qr-render-box {
            background: #fff;
            padding: 12px;
            border-radius: 12px;
            margin: 12px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }
        .crypto-hash-pill {
            font-family: monospace;
            font-size: 0.68rem;
            color: #24ffe3;
            background: rgba(36, 255, 227, 0.08);
            border: 1px solid rgba(36, 255, 227, 0.2);
            padding: 4px 8px;
            border-radius: 6px;
            word-break: break-all;
            margin-top: 8px;
            width: 100%;
        }

        /* Detail Table */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .meta-table th {
            width: 38%;
            text-align: left;
            padding: 11px 14px;
            color: #799ea8;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            letter-spacing: 0.03em;
        }
        .meta-table td {
            padding: 11px 14px;
            color: #e0f8f7;
            font-weight: 500;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        /* Action Buttons */
        .action-bar {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            border-top: 1px solid rgba(0, 255, 195, 0.2);
            padding-top: 22px;
        }
        .btn-act {
            padding: 12px 22px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.88rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .btn-print {
            background: linear-gradient(135deg, #00ffc3, #00bfa1);
            color: #053027;
            box-shadow: 0 4px 20px rgba(0, 255, 195, 0.4);
        }
        .btn-print:hover {
            box-shadow: 0 6px 26px rgba(0, 255, 195, 0.7);
            transform: translateY(-2px);
        }
        .btn-checkpoint-act {
            background: linear-gradient(135deg, #0099ff, #0066cc);
            color: #fff;
            box-shadow: 0 4px 20px rgba(0, 153, 255, 0.4);
        }
        .btn-checkpoint-act:hover {
            box-shadow: 0 6px 26px rgba(0, 153, 255, 0.7);
            transform: translateY(-2px);
        }
        .btn-edit-act {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(0, 255, 195, 0.35);
            color: #24ffe3;
        }
        .btn-edit-act:hover {
            background: rgba(0, 255, 195, 0.15);
            color: #fff;
        }

        /* Automated DOM Print-Rendering Module (<2s Dispatch) */
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
                padding: 0 !important;
            }
            .sidebar, .dashboard-header, .action-bar, .dossier-header-nav, .status-badge-lg {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .card-dossier {
                background: #fff !important;
                border: 2px solid #000 !important;
                box-shadow: none !important;
                color: #000 !important;
                border-radius: 8px !important;
                padding: 20px !important;
            }
            .dossier-top-bar {
                border-bottom: 2px solid #000 !important;
            }
            .dossier-title-area h2 {
                color: #000 !important;
            }
            .badge-preview-column {
                background: #fff !important;
                border: 1px solid #000 !important;
                color: #000 !important;
            }
            .meta-table th {
                color: #333 !important;
                border-bottom: 1px solid #ccc !important;
            }
            .meta-table td {
                color: #000 !important;
                border-bottom: 1px solid #ccc !important;
            }
            .crypto-hash-pill {
                color: #000 !important;
                border: 1px solid #000 !important;
                background: #fff !important;
            }
        }
    </style>

<div class="dossier-wrapper">
    <div class="dossier-header-nav">
            <a href="manage-passes.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to PassVault</a>
            <span style="font-size:0.8rem; color:#799ea8;"><i class="fas fa-clock"></i> Generated: <?php echo date('Y-m-d H:i:s'); ?></span>
        </div>

        <?php if ($not_found): ?>
            <div class="card-dossier" style="text-align:center; padding:50px;">
                <i class="fas fa-exclamation-triangle fa-3x text-red"></i>
                <h3 style="color:#ff5252; margin:16px 0 8px 0;">Credential Not Found</h3>
                <p style="color:#8da4af;">The requested gatepass ID does not exist in the Access Control Registry.</p>
                <a href="manage-passes.php" class="btn-act btn-checkpoint-act mt-3">Return to PassVault</a>
            </div>
        <?php else: ?>
            <div class="card-dossier" id="printableDossier">
                <div class="dossier-top-bar">
                    <div class="dossier-title-area">
                        <h2>
                            <i class="fas fa-shield-alt"></i> GATEPASS DOSSIER #<?php echo htmlspecialchars($row['pass_number']); ?>
                        </h2>
                        <span class="dossier-submeta">
                            Facility Ingress Permit • Security Tier: <strong><?php echo htmlspecialchars($row['security_clearance'] ?? 'Standard'); ?></strong>
                        </span>
                    </div>
                    <?php 
                        $statusClass = 'status-' . htmlspecialchars($row['status'] ?: 'Approved');
                    ?>
                    <span class="status-badge-lg <?php echo $statusClass; ?>">
                        <i class="fas fa-circle fa-xs"></i> <?php echo strtoupper(htmlspecialchars($row['status'] ?: 'APPROVED')); ?>
                    </span>
                </div>

                <div class="dossier-content-grid">
                    <!-- Left: QR Code Badge -->
                    <div class="badge-preview-column">
                        <small style="color:#799ea8; font-weight:700; letter-spacing:0.06em;">DIGITAL ACCESS TOKEN</small>
                        <div class="qr-render-box" id="passQr"></div>
                        <small style="color:#a0d5d5; font-size:0.75rem;">SCAN AT CHECKPOINT ALPHA</small>
                        <div class="crypto-hash-pill" title="HMAC-SHA256 Token">
                            SIG: <?php echo htmlspecialchars(substr($row['hmac_hash'] ?: 'PENDING_SIG', 0, 24)); ?>...
                        </div>
                    </div>

                    <!-- Right: Relational Data Table -->
                    <div>
                        <table class="meta-table">
                            <tr>
                                <th>VISITOR FULL NAME</th>
                                <td><strong style="color:#24ffe3; font-size:1.05rem;"><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>ACCESS CATEGORY</th>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                            </tr>
                            <tr>
                                <th>GOVERNMENT IDENTITY</th>
                                <td><?php echo htmlspecialchars($row['identity_type'] . ': ' . $row['identity_card_no']); ?></td>
                            </tr>
                            <tr>
                                <th>CONTACT TELEPHONE</th>
                                <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                            </tr>
                            <tr>
                                <th>EMAIL ADDRESS</th>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                            </tr>
                            <tr>
                                <th>VEHICLE PLATE (ALPR)</th>
                                <td><span style="background:rgba(0,255,195,0.1); padding:3px 8px; border-radius:6px; font-family:monospace;"><?php echo htmlspecialchars($row['vehicle_no'] ?: 'N/A'); ?></span></td>
                            </tr>
                            <tr>
                                <th>INTERNAL HOST CONTACT</th>
                                <td><?php echo htmlspecialchars(($row['host_name'] ?? 'Security Operations') . ' (' . ($row['host_department'] ?? 'General') . ')'); ?></td>
                            </tr>
                            <tr>
                                <th>TEMPORAL VALIDITY</th>
                                <td><?php echo htmlspecialchars($row['from_date'] . ' to ' . $row['to_date']); ?></td>
                            </tr>
                            <tr>
                                <th>PURPOSE OF VISIT</th>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                            </tr>
                            <tr>
                                <th>CHECK-IN TIMESTAMP</th>
                                <td><?php echo htmlspecialchars($row['checkin_time'] ?: 'Not checked in yet'); ?></td>
                            </tr>
                            <tr>
                                <th>CHECK-OUT TIMESTAMP</th>
                                <td><?php echo htmlspecialchars($row['checkout_time'] ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>PASS CREATION DATE</th>
                                <td><?php echo htmlspecialchars($row['pass_creation_date']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="action-bar">
                    <button class="btn-act btn-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Instant Print Badge / Receipt (&lt;2s)
                    </button>
                    <a href="checkpoint-terminal.php?scan=<?php echo urlencode($row['pass_number']); ?>" class="btn-act btn-checkpoint-act">
                        <i class="fas fa-shield-alt"></i> Verify at Checkpoint Terminal Alpha
                    </a>
                    <a href="edit-pass-detail.php?id=<?php echo $row['id']; ?>" class="btn-act btn-edit-act">
                        <i class="fas fa-edit"></i> Edit Pass Details
                    </a>
                </div>
            </div>

            <script>
                // Generate QR Code with HMAC Payload
                const passData = <?php echo json_encode(generateQRPayload($row)); ?>;
                new QRCode(document.getElementById("passQr"), {
                    text: passData,
                    width: 160,
                    height: 160,
                    colorDark : "#051821",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            </script>
        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>
