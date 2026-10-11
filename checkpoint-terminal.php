<?php
// checkpoint-terminal.php - Edge AI Checkpoint Terminal with Cryptographic QR, Computer Vision HUD & IoT Turnstile Relay
session_start();
include('db.php');

if (!isset($_SESSION['admin_id']) || strlen($_SESSION['admin_id']) == 0) {
    header("location:logout.php");
    exit;
}

$active_page = 'checkpoint-terminal.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$autoScan = htmlspecialchars($_GET['scan'] ?? $_GET['pass'] ?? '');
?>
<!-- QR Code & jsQR Libraries for Edge Camera QR Scanning -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<style>
        .terminal-container {
            padding: 0;
            color: #e0f8f7;
            font-family: 'Poppins', sans-serif;
        }
        .terminal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(18, 28, 38, 0.8);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(0, 255, 195, 0.28);
            border-radius: 20px;
            padding: 20px 28px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            flex-wrap: wrap;
            gap: 16px;
        }
        .header-left h2 {
            font-size: 1.6rem;
            font-weight: 900;
            margin: 0;
            letter-spacing: 0.08em;
            color: #24ffe3;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-left p {
            margin: 4px 0 0 0;
            font-size: 0.85rem;
            color: #8da4af;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .badge-status-pulse {
            background: rgba(0, 255, 195, 0.15);
            color: #24ffe3;
            border: 1px solid rgba(0, 255, 195, 0.4);
            padding: 8px 16px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #00ffc3;
            box-shadow: 0 0 10px #00ffc3;
            animation: pulseAnim 1.4s infinite;
        }
        @keyframes pulseAnim {
            0% { transform: scale(0.9); opacity: 0.7; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.7; }
        }
        .latency-pill {
            background: rgba(14, 24, 34, 0.9);
            border: 1px solid rgba(0, 255, 195, 0.3);
            padding: 8px 14px;
            border-radius: 20px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #00ffc3;
        }

        /* Terminal 2-Column Grid */
        .terminal-grid {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        @media (max-width: 992px) {
            .terminal-grid { grid-template-columns: 1fr; }
        }
        .grid-card {
            background: rgba(18, 28, 38, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 255, 195, 0.22);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
        }
        .card-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #24ffe3;
            font-size: 0.92rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0, 255, 195, 0.18);
            margin-bottom: 16px;
        }
        .cam-controls {
            display: flex;
            gap: 8px;
        }
        .btn-micro {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(0, 255, 195, 0.3);
            color: #8da4af;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-micro.active {
            background: rgba(0, 255, 195, 0.2);
            color: #00ffc3;
            border-color: #00ffc3;
        }

        /* Viewfinder & Vision HUD */
        .viewfinder-wrapper {
            position: relative;
            background: #091319;
            border: 2px solid rgba(0, 255, 195, 0.35);
            border-radius: 16px;
            height: 290px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }
        #webcamVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .sim-camera-hud {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .hud-scanline {
            position: absolute;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #00ffc3, transparent);
            box-shadow: 0 0 15px #00ffc3;
            animation: scanAnim 2.5s ease-in-out infinite;
        }
        @keyframes scanAnim {
            0% { top: 0%; }
            50% { top: 96%; }
            100% { top: 0%; }
        }
        .hud-reticle {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 170px; height: 170px;
            border: 1px dashed rgba(0, 255, 195, 0.3);
            border-radius: 14px;
        }
        .reticle-corner {
            position: absolute;
            width: 16px; height: 16px;
            border-color: #00ffc3;
            border-style: solid;
        }
        .top-left { top: -2px; left: -2px; border-width: 3px 0 0 3px; }
        .top-right { top: -2px; right: -2px; border-width: 3px 3px 0 0; }
        .bottom-left { bottom: -2px; left: -2px; border-width: 0 0 3px 3px; }
        .bottom-right { bottom: -2px; right: -2px; border-width: 0 3px 3px 0; }
        
        /* Computer Vision Bounding Boxes */
        .vision-box {
            position: absolute;
            border: 2px solid;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .face-box {
            top: 22px; left: 24px;
            width: 100px; height: 110px;
            border-color: #00ffc3;
            box-shadow: 0 0 12px rgba(0, 255, 195, 0.4);
            animation: jitterFace 3s infinite alternate;
        }
        @keyframes jitterFace {
            0% { transform: translate(0, 0); }
            100% { transform: translate(4px, 6px); }
        }
        .plate-box {
            bottom: 30px; right: 24px;
            width: 150px; height: 50px;
            border-color: #ffd600;
            box-shadow: 0 0 12px rgba(255, 214, 0, 0.4);
        }
        .box-tag {
            position: absolute;
            top: -20px; left: 0;
            background: rgba(9, 19, 25, 0.85);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        .face-box .box-tag { color: #00ffc3; border: 1px solid #00ffc3; }
        .plate-box .box-tag { color: #ffd600; border: 1px solid #ffd600; }
        .hud-stats-overlay {
            position: absolute;
            bottom: 8px; left: 12px; right: 12px;
            display: flex;
            justify-content: space-between;
            font-family: monospace;
            font-size: 0.72rem;
            color: rgba(0, 255, 195, 0.7);
        }

        /* Input Action Bar */
        .search-input-wrap {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }
        .search-input-wrap input {
            flex: 1;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(0, 255, 195, 0.3);
            border-radius: 10px;
            padding: 11px 16px;
            color: #fff;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
        }
        .search-input-wrap input:focus {
            border-color: #00ffc3;
            box-shadow: 0 0 14px rgba(0, 255, 195, 0.4);
        }
        .btn-scan {
            background: linear-gradient(135deg, #00ffc3, #00bfa1);
            color: #053027;
            border: none;
            padding: 0 20px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-scan:hover {
            box-shadow: 0 0 18px rgba(0, 255, 195, 0.6);
        }
        .quick-samples {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .chip {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(0, 255, 195, 0.2);
            color: #24ffe3;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 0.74rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .chip:hover {
            background: rgba(0, 255, 195, 0.2);
            color: #fff;
        }

        /* Right Column: Dossier Inspector */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #8da4af;
        }
        .dossier-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(14, 24, 34, 0.8);
            border: 1px solid rgba(0, 255, 195, 0.3);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 18px;
        }
        .banner-status-left h3 {
            margin: 0;
            color: #fff;
            font-size: 1.35rem;
            font-weight: 800;
        }
        .sub-meta {
            font-size: 0.8rem;
            color: #799ea8;
        }
        .seal-text {
            font-weight: 900;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .detail-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(0, 255, 195, 0.15);
            padding: 8px 12px;
            border-radius: 10px;
        }
        .detail-item label {
            display: block;
            font-size: 0.68rem;
            color: #799ea8;
            font-weight: 700;
        }
        .detail-item strong {
            font-size: 0.88rem;
            color: #fff;
        }
        .crypto-hash {
            font-family: monospace;
            font-size: 0.72rem;
            color: #00ffc3;
            word-break: break-all;
        }
        .reason-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(0, 255, 195, 0.15);
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
        }
        .reason-box label {
            font-size: 0.68rem;
            color: #799ea8;
            font-weight: 700;
            display: block;
            margin-bottom: 4px;
        }
        .reason-box p {
            margin: 0;
            font-size: 0.85rem;
            color: #c4f9f4;
        }

        /* Checkpoint & Barrier Actions */
        .checkpoint-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
        }
        .btn-checkpoint {
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.82rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-checkin {
            background: linear-gradient(135deg, #00ffc3, #00bfa1);
            color: #053027;
        }
        .btn-checkin:hover { box-shadow: 0 0 16px rgba(0, 255, 195, 0.6); }
        .btn-checkout {
            background: linear-gradient(135deg, #0099ff, #0066cc);
            color: #ffffff;
        }
        .btn-checkout:hover { box-shadow: 0 0 16px rgba(0, 153, 255, 0.6); }
        .btn-flag {
            background: rgba(255, 68, 68, 0.15);
            border: 1px solid rgba(255, 68, 68, 0.4);
            color: #ff6b6b;
        }
        .btn-flag:hover { background: #ff4444; color: #fff; box-shadow: 0 0 16px rgba(255, 68, 68, 0.6); }
        
        .btn-turnstile-relay {
            background: linear-gradient(135deg, #ffd600, #ff9100);
            color: #1a1000;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 900;
            font-size: 0.84rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(255, 214, 0, 0.4);
        }
        .btn-turnstile-relay:hover {
            box-shadow: 0 6px 25px rgba(255, 214, 0, 0.7);
            transform: translateY(-2px);
        }

        /* Audit Table */
        .audit-stream-card {
            background: rgba(18, 28, 38, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 255, 195, 0.22);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .audit-table-wrapper {
            overflow-x: auto;
            max-height: 240px;
        }
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
        }
        .audit-table th {
            text-align: left;
            color: #24ffe3;
            padding: 10px 12px;
            border-bottom: 2px solid rgba(0, 255, 195, 0.2);
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .audit-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #c4f9f4;
        }
        .status-pill {
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 700;
            background: rgba(0, 255, 195, 0.15);
            color: #00ffc3;
        }
        .text-cyan { color: #00ffc3; }
        .text-green { color: #00ff88; }
        .text-red { color: #ff5252; }
        .text-orange { color: #ff9800; }
    </style>

<div class="terminal-container">
    <div class="terminal-header">
        <div class="header-left">
            <h2><i class="fas fa-shield-alt text-cyan"></i> CHECKPOINT TERMINAL ALPHA</h2>
            <p>Edge AI Computer Vision • HMAC Cryptographic Validator • IoT Turnstile Relay</p>
        </div>
        <div class="header-right">
            <span class="badge-status-pulse" id="gateStatus"><span class="pulse-dot"></span> GATE-01: ONLINE</span>
            <span class="latency-pill" id="latencyPill"><i class="fas fa-bolt"></i> LATENCY: <span id="latencyVal">--</span> ms</span>
        </div>
    </div>

    <div class="terminal-grid">
        <!-- Left: Optical Scanner & Vision Feed -->
        <div class="grid-card">
            <div class="card-title">
                <span><i class="fas fa-video"></i> OPTICAL SCANNER & VISION FEED</span>
                <div class="cam-controls">
                    <button class="btn-micro active" id="btnModeSim" onclick="setCameraMode('sim')">SIMULATED HUD</button>
                    <button class="btn-micro" id="btnModeWebcam" onclick="setCameraMode('webcam')">WEBCAM LIVE</button>
                    <button class="btn-micro" id="btnAudioToggle" onclick="toggleAudio()"><i class="fas fa-volume-up" id="audioIcon"></i></button>
                </div>
            </div>

            <div class="viewfinder-wrapper" id="viewfinderWrapper">
                <video id="webcamVideo" autoplay playsinline style="display:none;"></video>
                <canvas id="qrScannerCanvas" style="display:none;"></canvas>

                <div class="sim-camera-hud" id="simCameraHud">
                    <div class="hud-scanline"></div>
                    <div class="hud-reticle">
                        <div class="reticle-corner top-left"></div>
                        <div class="reticle-corner top-right"></div>
                        <div class="reticle-corner bottom-left"></div>
                        <div class="reticle-corner bottom-right"></div>
                    </div>

                    <!-- Face Biometric Box -->
                    <div class="vision-box face-box" id="hudFaceBox">
                        <span class="box-tag"><i class="fas fa-user-check"></i> FACE: MATCH 98.6%</span>
                    </div>

                    <!-- ALPR Plate Box -->
                    <div class="vision-box plate-box" id="hudPlateBox">
                        <span class="box-tag"><i class="fas fa-car"></i> ALPR: <span id="hudPlateText">DL-08-AX-9942</span></span>
                    </div>

                    <div class="hud-stats-overlay">
                        <span>FPS: 60.0</span>
                        <span>RES: 1080p-HDR</span>
                        <span>AI-YOLOv8 & OpenCV READY</span>
                    </div>
                </div>
            </div>

            <!-- Manual & Sample Inputs -->
            <div class="search-input-wrap">
                <input type="text" id="passNumberInput" placeholder="Scan QR payload or enter Pass Number..." autocomplete="off">
                <button class="btn-scan" onclick="manualScan()"><i class="fas fa-search"></i> VERIFY</button>
            </div>
            <div class="quick-samples">
                <small class="text-muted" style="color:#799ea8; font-size:0.75rem;">QUICK LOAD SAMPLES:</small>
                <button class="chip" onclick="quickFill('918237465')"><i class="fas fa-check-circle text-green"></i> Pass #918237465 (Dr. Rao - Secret)</button>
                <button class="chip" onclick="quickFill('845920138')"><i class="fas fa-star text-yellow"></i> Pass #845920138 (Elena - VIP)</button>
                <button class="chip" onclick="quickFill('682109789')"><i class="fas fa-times-circle text-red"></i> Pass #682109789 (Expired / Overstay)</button>
                <a href="add-pass.php" class="chip" style="text-decoration:none;">+ Issue Active Pass</a>
            </div>
        </div>

        <!-- Right: Visitor Dossier & Cryptographic Inspector -->
        <div class="grid-card">
            <div class="card-title">
                <span><i class="fas fa-fingerprint"></i> VISITOR CREDENTIAL DOSSIER</span>
                <span id="securityBadge" class="status-pill">STANDBY</span>
            </div>

            <div id="emptyInspectorState" class="empty-state">
                <i class="fas fa-qrcode fa-3x" style="color:rgba(0, 255, 195, 0.4);"></i>
                <p style="margin:16px 0 6px 0; color:#d7ffff;">Ready for scan. Align visitor QR pass in viewfinder or enter pass number.</p>
                <small style="color:#799ea8;">HMAC-SHA256 signature verification executes in &lt;50ms</small>
            </div>

            <div id="activeDossier" style="display: none;">
                <div class="dossier-banner" id="dossierBanner">
                    <div class="banner-status-left">
                        <h3 id="dossierName">Ram Kumar</h3>
                        <span class="sub-meta" id="dossierMeta">Visitor • Clearance: Standard</span>
                    </div>
                    <div style="text-align:right;">
                        <div id="sealIcon"><i class="fas fa-shield-alt text-cyan"></i></div>
                        <span class="seal-text text-cyan" id="sealText">HMAC VERIFIED</span>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <label>PASS NUMBER</label>
                        <strong id="dPassNumber" class="text-cyan">--</strong>
                    </div>
                    <div class="detail-item">
                        <label>GOVERNMENT ID</label>
                        <strong id="dNationalId">--</strong>
                    </div>
                    <div class="detail-item">
                        <label>VALIDITY WINDOW</label>
                        <strong id="dValidity">--</strong>
                    </div>
                    <div class="detail-item">
                        <label>VEHICLE REG (ALPR)</label>
                        <strong id="dVehicle">--</strong>
                    </div>
                    <div class="detail-item">
                        <label>INTERNAL HOST</label>
                        <strong id="dHost">--</strong>
                    </div>
                    <div class="detail-item">
                        <label>CURRENT STATUS</label>
                        <span id="dStatus" class="status-pill">--</span>
                    </div>
                    <div class="detail-item" style="grid-column: span 2;">
                        <label>CRYPTOGRAPHIC SIGNATURE (HMAC-SHA256)</label>
                        <span class="crypto-hash" id="dHash">--</span>
                    </div>
                </div>

                <div class="reason-box">
                    <label>VISIT PURPOSE / OPERATIONAL DETAILS</label>
                    <p id="dReason">--</p>
                </div>

                <!-- Checkpoint Action Buttons -->
                <div class="checkpoint-actions">
                    <button class="btn-checkpoint btn-checkin" onclick="executeCheckpointAction('CHECK_IN')">
                        <i class="fas fa-sign-in-alt"></i> GRANT INGRESS (CHECK-IN)
                    </button>
                    <button class="btn-checkpoint btn-checkout" onclick="executeCheckpointAction('CHECK_OUT')">
                        <i class="fas fa-sign-out-alt"></i> LOG EGRESS (CHECK-OUT)
                    </button>
                    <button class="btn-checkpoint btn-flag" onclick="executeCheckpointAction('FLAG')">
                        <i class="fas fa-exclamation-triangle"></i> FLAG SECURITY ANOMALY
                    </button>
                    <button class="btn-turnstile-relay" onclick="actuateTurnstileRelay()">
                        <i class="fas fa-bolt"></i> ACTUATE TURNSTILE RELAY
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom: Live Audit Stream -->
    <div class="audit-stream-card">
        <div class="card-title">
            <span><i class="fas fa-stream"></i> REAL-TIME CHECKPOINT TELEMETRY FEED</span>
            <span style="font-size:0.75rem; color:#00ff88;"><i class="fas fa-circle fa-xs"></i> LIVE AUDIT STREAM</span>
        </div>
        <div class="audit-table-wrapper">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>TIMESTAMP</th>
                        <th>PASS #</th>
                        <th>VISITOR</th>
                        <th>ACTION</th>
                        <th>GATE</th>
                        <th>BIOMETRIC %</th>
                        <th>ALPR PLATE</th>
                        <th>NOTE</th>
                    </tr>
                </thead>
                <tbody id="auditTableBody">
                    <tr><td colspan="8" class="text-center text-muted">Awaiting checkpoint events...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Web Audio Synthesizer for High-Tech Checkpoint & Barrier SFX
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
let audioEnabled = true;

function playSfx(type) {
    if (!audioEnabled || !audioCtx) return;
    try {
        if (audioCtx.state === 'suspended') audioCtx.resume();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        const now = audioCtx.currentTime;

        if (type === 'scan') {
            osc.frequency.setValueAtTime(880, now);
            osc.frequency.exponentialRampToValueAtTime(1760, now + 0.08);
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.12);
            osc.start(now);
            osc.stop(now + 0.12);
        } else if (type === 'success') {
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(523.25, now);
            osc.frequency.setValueAtTime(659.25, now + 0.08);
            osc.frequency.setValueAtTime(783.99, now + 0.16);
            gain.gain.setValueAtTime(0.25, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.35);
            osc.start(now);
            osc.stop(now + 0.35);
        } else if (type === 'alarm') {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(240, now);
            osc.frequency.linearRampToValueAtTime(160, now + 0.35);
            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.4);
            osc.start(now);
            osc.stop(now + 0.4);
        } else if (type === 'relay') {
            // Pneumatic turnstile barrier release
            osc.type = 'sine';
            osc.frequency.setValueAtTime(120, now);
            osc.frequency.exponentialRampToValueAtTime(60, now + 0.25);
            gain.gain.setValueAtTime(0.4, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.3);
            osc.start(now);
            osc.stop(now + 0.3);
        }
    } catch(e) {}
}

function toggleAudio() {
    audioEnabled = !audioEnabled;
    document.getElementById('audioIcon').className = audioEnabled ? 'fas fa-volume-up' : 'fas fa-volume-mute';
}

// Camera Modes (Simulated HUD vs Real Webcam with jsQR scanning)
let webcamStream = null;
let qrScanInterval = null;

function setCameraMode(mode) {
    const simHud = document.getElementById('simCameraHud');
    const video = document.getElementById('webcamVideo');
    const btnSim = document.getElementById('btnModeSim');
    const btnCam = document.getElementById('btnModeWebcam');

    if (mode === 'webcam') {
        btnSim.classList.remove('active');
        btnCam.classList.add('active');
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
            .then(stream => {
                webcamStream = stream;
                video.srcObject = stream;
                video.style.display = 'block';
                simHud.style.background = 'transparent';
                startEdgeQrScanner();
            })
            .catch(err => {
                alert("Camera access denied or unavailable. Running in simulated HUD mode.");
                setCameraMode('sim');
            });
        } else {
            alert("Webcam API not supported in this browser. Running in simulated HUD mode.");
            setCameraMode('sim');
        }
    } else {
        btnCam.classList.remove('active');
        btnSim.classList.add('active');
        if (webcamStream) {
            webcamStream.getTracks().forEach(track => track.stop());
            webcamStream = null;
        }
        clearInterval(qrScanInterval);
        video.style.display = 'none';
        simHud.style.background = '#091319';
    }
}

function startEdgeQrScanner() {
    const video = document.getElementById('webcamVideo');
    const canvas = document.getElementById('qrScannerCanvas');
    const ctx = canvas.getContext('2d');

    qrScanInterval = setInterval(() => {
        if (video.readyState === video.HAVE_ENOUGH_DATA && window.jsQR) {
            canvas.height = video.videoHeight;
            canvas.width = video.videoWidth;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "dontInvert"
            });
            if (code && code.data) {
                document.getElementById('passNumberInput').value = code.data;
                manualScan();
            }
        }
    }, 400);
}

// State & Actions
let currentPass = null;

function quickFill(passNumber) {
    document.getElementById('passNumberInput').value = passNumber;
    manualScan();
}

function manualScan() {
    const input = document.getElementById('passNumberInput').value.trim();
    if (!input) return;

    playSfx('scan');
    document.getElementById('latencyVal').textContent = '...';

    const t0 = performance.now();
    fetch('api/verify-pass.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pass_number: input, qr_payload: input })
    })
    .then(res => res.json())
    .then(data => {
        const clientLatency = Math.round(performance.now() - t0);
        document.getElementById('latencyVal').textContent = data.verification_time_ms || clientLatency;

        if (!data.success || !data.pass) {
            playSfx('alarm');
            showNotFound(data.message || 'Pass not found.');
            return;
        }

        renderDossier(data);
    })
    .catch(err => {
        alert('Network or server error during verification: ' + err);
    });
}

function renderDossier(data) {
    currentPass = data.pass;
    document.getElementById('emptyInspectorState').style.display = 'none';
    document.getElementById('activeDossier').style.display = 'block';

    const p = data.pass;
    document.getElementById('dossierName').textContent = p.full_name;
    document.getElementById('dossierMeta').textContent = `${p.category} • Clearance: ${p.security_clearance || 'Standard'}`;
    document.getElementById('dPassNumber').textContent = p.pass_number;
    document.getElementById('dNationalId').textContent = `${p.identity_type}: ${p.identity_card_no}`;
    document.getElementById('dValidity').textContent = `${p.from_date} to ${p.to_date}`;
    document.getElementById('dVehicle').textContent = p.vehicle_no || 'N/A';
    document.getElementById('dHost').textContent = `${p.host_name || 'Security Operations'} (${p.host_department || 'General'})`;
    document.getElementById('dStatus').textContent = p.status;
    document.getElementById('dHash').textContent = p.hmac_hash ? p.hmac_hash.substring(0, 36) + '...' : 'VERIFIED_ON_FLY';
    document.getElementById('dReason').textContent = p.reason || 'No statement recorded';

    if (p.vehicle_no && p.vehicle_no !== 'N/A') {
        document.getElementById('hudPlateText').textContent = p.vehicle_no;
    }

    const banner = document.getElementById('dossierBanner');
    const sealIcon = document.getElementById('sealIcon');
    const sealText = document.getElementById('sealText');
    const badge = document.getElementById('securityBadge');

    if (data.tampered) {
        playSfx('alarm');
        banner.style.borderColor = '#ff4444';
        sealIcon.innerHTML = '<i class="fas fa-times-circle text-red fa-2x"></i>';
        sealText.textContent = 'TAMPER DETECTED';
        sealText.className = 'seal-text text-red';
        badge.textContent = 'SIGNATURE MISMATCH';
        badge.className = 'status-pill st-Flagged';
    } else if (data.expired) {
        playSfx('alarm');
        banner.style.borderColor = '#ff9800';
        sealIcon.innerHTML = '<i class="fas fa-history text-orange fa-2x"></i>';
        sealText.textContent = 'EXPIRED PASS';
        sealText.className = 'seal-text text-orange';
        badge.textContent = 'EXPIRED';
        badge.className = 'status-pill';
    } else {
        playSfx('success');
        banner.style.borderColor = 'rgba(0, 255, 195, 0.4)';
        sealIcon.innerHTML = '<i class="fas fa-shield-alt text-cyan fa-2x"></i>';
        sealText.textContent = 'HMAC VERIFIED';
        sealText.className = 'seal-text text-cyan';
        badge.textContent = 'AUTHENTIC PASS';
        badge.className = 'status-pill st-Approved';
    }

    refreshTelemetry();
}

function showNotFound(msg) {
    document.getElementById('emptyInspectorState').style.display = 'block';
    document.getElementById('activeDossier').style.display = 'none';
    document.getElementById('emptyInspectorState').innerHTML = `
        <i class="fas fa-exclamation-triangle fa-3x text-red"></i>
        <h4 class="text-red mt-3">VERIFICATION FAILED</h4>
        <p>${msg}</p>
        <button class="chip mt-2" onclick="manualScan()">Try Again</button>
    `;
}

function executeCheckpointAction(action) {
    if (!currentPass) return;

    const payload = {
        pass_number: currentPass.pass_number,
        action: action,
        gate: 'Gate-Alpha North',
        guard_id: 'ADMIN-01',
        biometric_match: 98.6,
        alpr_plate: currentPass.vehicle_no || 'DL-08-AX-9942',
        note: action === 'FLAG' ? 'Manual security flag triggered at terminal' : ''
    };

    fetch('api/checkpoint-action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            playSfx(action === 'FLAG' ? 'alarm' : 'success');
            currentPass.status = data.new_status;
            document.getElementById('dStatus').textContent = data.new_status;
            refreshTelemetry();
            alert(`[ACTION LOGGED] ${data.message}`);
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function actuateTurnstileRelay() {
    if (!currentPass) {
        alert("Please scan a visitor pass first to actuate turnstile barrier.");
        return;
    }

    playSfx('relay');
    const gateStatus = document.getElementById('gateStatus');
    gateStatus.innerHTML = '<span class="pulse-dot" style="background:#ffd600;"></span> BARRIER UNLOCKING...';

    fetch('api/turnstile-webhook.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            pass_number: currentPass.pass_number,
            turnstile_id: 'TURNSTILE-ALPHA-01',
            gate: 'Gate-Alpha North',
            event_type: 'ENTRY_REQUEST'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'AUTHORIZED') {
            playSfx('success');
            gateStatus.innerHTML = '<span class="pulse-dot" style="background:#00ff88;"></span> BARRIER OPEN (5.0s)';
            setTimeout(() => {
                gateStatus.innerHTML = '<span class="pulse-dot"></span> GATE-01: ONLINE';
            }, 5000);
            refreshTelemetry();
            alert(`[IoT RELAY] Turnstile barrier opened. Latency: ${data.latency_ms}ms.`);
        } else {
            playSfx('alarm');
            gateStatus.innerHTML = '<span class="pulse-dot" style="background:#ff3344;"></span> BARRIER LOCKED: DENIED';
            setTimeout(() => {
                gateStatus.innerHTML = '<span class="pulse-dot"></span> GATE-01: ONLINE';
            }, 3000);
            alert(`[IoT RELAY] Access Denied: ${data.message}`);
        }
    });
}

function refreshTelemetry() {
    fetch('api/telemetry.php')
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;
        const tbody = document.getElementById('auditTableBody');
        if (data.recent_logs && data.recent_logs.length > 0) {
            tbody.innerHTML = data.recent_logs.map(log => `
                <tr>
                    <td><small class="text-muted">${log.timestamp}</small></td>
                    <td><strong class="text-cyan">${log.pass_number}</strong></td>
                    <td>${log.visitor_name || '--'}</td>
                    <td><span class="status-pill">${log.action}</span></td>
                    <td>${log.checkpoint_gate}</td>
                    <td>${log.biometric_match_pct ? log.biometric_match_pct + '%' : 'N/A'}</td>
                    <td>${log.alpr_plate || 'N/A'}</td>
                    <td><small>${log.status_note || ''}</small></td>
                </tr>
            `).join('');
        }
    });
}

// Auto-run if query param provided
document.addEventListener('DOMContentLoaded', () => {
    refreshTelemetry();
    setInterval(refreshTelemetry, 5000);

    const autoScanVal = "<?php echo $autoScan; ?>";
    if (autoScanVal) {
        document.getElementById('passNumberInput').value = autoScanVal;
        manualScan();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
