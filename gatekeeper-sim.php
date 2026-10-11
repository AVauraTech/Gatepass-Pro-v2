<?php
// gatekeeper-sim.php - Adversarial Gatekeeper AI Checkpoint Simulation Game
// Featuring Q-Learning RL Difficulty Controller, LLM Adversarial Agents & Procedural Forensics
session_start();
include('db.php');

if (!isset($_SESSION['admin_id']) || strlen($_SESSION['admin_id']) == 0) {
    header("location:logout.php");
    exit;
}

$active_page = 'gatekeeper-sim.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<style>
        .sim-container {
            padding: 0;
            color: #e0f8f7;
            font-family: 'Poppins', sans-serif;
            user-select: none;
        }

        /* Top HUD Bar */
        .game-hud-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(14, 24, 34, 0.92);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 255, 195, 0.35);
            border-radius: 18px;
            padding: 12px 24px;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.6);
            flex-wrap: wrap;
            gap: 14px;
        }
        .hud-stat {
            display: flex;
            flex-direction: column;
        }
        .hud-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: #799ea8;
            letter-spacing: 0.08em;
        }
        .hud-val {
            font-size: 1.25rem;
            font-weight: 900;
            font-family: 'Share Tech Mono', monospace;
        }
        .text-cyan { color: #00ffc3; }
        .text-green { color: #00ff88; }
        .text-yellow { color: #ffd600; }
        .text-orange { color: #ff9100; }
        .text-red { color: #ff4d4d; }

        /* Main Workspace: 2 Panels */
        .sim-grid {
            display: grid;
            grid-template-columns: 1fr 1.25fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 1024px) {
            .sim-grid { grid-template-columns: 1fr; }
        }
        .game-panel {
            background: rgba(18, 28, 38, 0.88);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 255, 195, 0.25);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #24ffe3;
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0, 255, 195, 0.15);
            margin-bottom: 16px;
        }

        /* Left Panel: Visitor Stage & Biometrics */
        .visitor-stage {
            position: relative;
            background: #091319;
            border: 2px solid rgba(0, 255, 195, 0.3);
            border-radius: 16px;
            height: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .avatar-wrap {
            position: relative;
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: rgba(36, 255, 227, 0.05);
            border: 2px dashed rgba(36, 255, 227, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .sweat-bead {
            position: absolute;
            top: 24px;
            right: 28px;
            width: 6px;
            height: 10px;
            background: #00e5ff;
            border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
            box-shadow: 0 0 6px #00e5ff;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .sweat-active {
            opacity: 1;
            animation: dropSweat 1.2s infinite;
        }
        @keyframes dropSweat {
            0% { transform: translateY(0); opacity: 0.9; }
            100% { transform: translateY(18px); opacity: 0.1; }
        }
        .speech-bubble {
            position: absolute;
            bottom: 10px;
            left: 16px; right: 16px;
            background: rgba(14, 24, 34, 0.95);
            border: 1px solid rgba(0, 255, 195, 0.4);
            border-radius: 12px;
            padding: 10px 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.8);
            min-height: 48px;
            display: flex;
            align-items: center;
        }
        .speech-bubble p {
            margin: 0;
            font-size: 0.85rem;
            color: #e0f8f7;
            font-style: italic;
        }

        /* Biometric Cardiac Telemetry */
        .cardiac-monitor-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(10, 18, 25, 0.9);
            border: 1px solid rgba(0, 255, 195, 0.2);
            border-radius: 10px;
            padding: 8px 14px;
            margin-bottom: 14px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.8rem;
        }
        .ecg-line {
            width: 90px;
            height: 18px;
            stroke: #00ffc3;
            fill: none;
            stroke-width: 2;
        }

        /* Interrogation Hub */
        .interrogation-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(0, 255, 195, 0.18);
            border-radius: 12px;
            padding: 12px;
        }
        .interrogation-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #799ea8;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .quick-questions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 10px;
        }
        .btn-interrogate {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(0, 255, 195, 0.25);
            color: #24ffe3;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-interrogate:hover {
            background: rgba(0, 255, 195, 0.15);
            color: #fff;
            border-color: #00ffc3;
        }
        .custom-query-wrap {
            display: flex;
            gap: 8px;
        }
        .custom-query-wrap input {
            flex: 1;
            background: rgba(14, 24, 34, 0.9);
            border: 1px solid rgba(0, 255, 195, 0.3);
            border-radius: 8px;
            padding: 7px 12px;
            color: #fff;
            font-size: 0.8rem;
            outline: none;
        }
        .btn-ask {
            background: linear-gradient(135deg, #00ffc3, #00bfa1);
            color: #053027;
            border: none;
            padding: 0 14px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 0.78rem;
            cursor: pointer;
        }

        /* Right Panel: Physical Pass & Inspection Tools */
        .tool-bar-toggles {
            display: flex;
            gap: 8px;
        }
        .btn-tool-toggle {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(0, 255, 195, 0.3);
            color: #8da4af;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.74rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-tool-toggle.active {
            background: rgba(0, 255, 195, 0.2);
            color: #00ffc3;
            border-color: #00ffc3;
            box-shadow: 0 0 12px rgba(0, 255, 195, 0.4);
        }

        /* Physical Pass Document */
        .pass-document {
            position: relative;
            background: linear-gradient(135deg, #0e1e28 0%, #152c3b 100%);
            border: 2px solid rgba(0, 255, 195, 0.4);
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.6);
            margin-bottom: 14px;
            overflow: hidden;
            transition: filter 0.3s;
        }
        .pass-doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 255, 195, 0.2);
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .org-badge-text {
            font-size: 0.7rem;
            font-weight: 800;
            color: #799ea8;
            letter-spacing: 0.08em;
        }
        .doc-name {
            margin: 2px 0 0 0;
            color: #24ffe3;
            font-size: 1.15rem;
            font-weight: 900;
        }
        .doc-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.82rem;
            padding: 4px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .doc-row span { color: #799ea8; font-weight: 600; }
        .doc-row strong { color: #e0f8f7; }
        .hmac-token-chip {
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.75rem;
        }

        /* Ultraviolet Lamp Overlay */
        .uv-watermark-overlay {
            position: absolute;
            inset: 0;
            background: rgba(80, 0, 160, 0.85);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            color: #00ffff;
            font-weight: 900;
            font-size: 1.1rem;
            letter-spacing: 0.12em;
            text-shadow: 0 0 15px #00ffff;
            display: none;
        }

        /* Intelligence Bulletin Box */
        .intel-bulletin {
            background: rgba(14, 24, 34, 0.9);
            border: 1px solid rgba(0, 255, 195, 0.2);
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 0.8rem;
        }
        .intel-bulletin-title {
            color: #ffd600;
            font-weight: 800;
            font-size: 0.75rem;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Decisions Hub */
        .decisions-hub {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .btn-decision {
            border: none;
            padding: 13px;
            border-radius: 10px;
            font-weight: 900;
            font-size: 0.84rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            letter-spacing: 0.04em;
        }
        .btn-approve {
            background: linear-gradient(135deg, #00ffc3, #00bfa1);
            color: #053027;
        }
        .btn-approve:hover { box-shadow: 0 0 18px rgba(0, 255, 195, 0.7); }
        .btn-deny {
            background: linear-gradient(135deg, #ff9800, #e65100);
            color: #fff;
        }
        .btn-deny:hover { box-shadow: 0 0 18px rgba(255, 152, 0, 0.7); }
        .btn-detain {
            grid-column: span 2;
            background: linear-gradient(135deg, #ff3344, #b71c1c);
            color: #fff;
        }
        .btn-detain:hover { box-shadow: 0 0 20px rgba(255, 51, 68, 0.8); }

        /* Q-Learning RL Brain Drawer */
        .rl-brain-card {
            background: rgba(14, 24, 34, 0.95);
            border: 1px solid rgba(0, 255, 195, 0.3);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .rl-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .rl-header h4 {
            margin: 0;
            color: #24ffe3;
            font-size: 0.88rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rl-matrix-content {
            margin-top: 14px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.78rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .q-metric-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(0, 255, 195, 0.15);
            border-radius: 8px;
            padding: 8px 12px;
        }

        /* Leaderboard Strip */
        .leaderboard-footer {
            background: rgba(14, 24, 34, 0.85);
            border: 1px solid rgba(0, 255, 195, 0.2);
            border-radius: 14px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            overflow-x: auto;
            font-size: 0.82rem;
        }
        .lb-pill {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(0, 255, 195, 0.2);
            padding: 4px 10px;
            border-radius: 8px;
            white-space: nowrap;
        }

        /* Shift End Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(5, 12, 18, 0.85);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .modal-box {
            background: #0f1c24;
            border: 2px solid #00ffc3;
            border-radius: 20px;
            width: 90%;
            max-width: 520px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 0 50px rgba(0, 255, 195, 0.5);
    </style>

<div class="sim-container">
    <!-- Top HUD Bar -->
    <div class="game-hud-bar">
        <div class="hud-stat">
            <span class="hud-label">SECURITY CLEARANCE</span>
            <span class="hud-val text-cyan" id="hudLevel">TIER 1</span>
        </div>
        <div class="hud-stat">
            <span class="hud-label">SHIFT SCORE</span>
            <span class="hud-val text-green" id="hudScore">0</span>
        </div>
        <div class="hud-stat">
            <span class="hud-label">COMBO MULTIPLIER</span>
            <span class="hud-val text-yellow" id="hudCombo">x1.0</span>
        </div>
        <div class="hud-stat">
            <span class="hud-label">ACCURACY RATE</span>
            <span class="hud-val" id="hudAccuracy">100%</span>
        </div>
        <div class="hud-stat">
            <span class="hud-label">BREACHES INTERCEPTED</span>
            <span class="hud-val text-cyan" id="hudBreaches">0</span>
        </div>
        <div class="hud-stat">
            <span class="hud-label">CHECKPOINT TIMER</span>
            <span class="hud-val text-orange" id="hudTimer">35s</span>
        </div>
        <div>
            <button class="btn-micro" onclick="endShift()"><i class="fas fa-flag-checkered"></i> END SHIFT</button>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="sim-grid">
        <!-- Left: Visitor Stage & Biometrics -->
        <div class="game-panel">
            <div class="panel-header">
                <span><i class="fas fa-camera"></i> CHECKPOINT INTERVIEW VISUALIZER</span>
                <span class="hud-label" id="stressBadge" style="color:#00ffc3;">STRESS: NORMAL</span>
            </div>

            <div class="visitor-stage" id="visitorStage">
                <div class="avatar-wrap" id="avatarBox">
                    <div class="sweat-bead" id="sweatBead"></div>
                    <div id="avatarSvg"></div>
                </div>

                <div class="speech-bubble" id="speechBubble">
                    <p id="dialogueText">"Good day officer, presenting my access permit for verification."</p>
                </div>
            </div>

            <!-- Cardiac Pulse & Biometrics -->
            <div class="cardiac-monitor-strip">
                <span><i class="fas fa-heartbeat text-red"></i> PULSE: <span id="pulseBpm">72</span> BPM</span>
                <svg class="ecg-line" viewBox="0 0 100 20">
                    <polyline points="0,10 20,10 25,2 30,18 35,10 50,10 55,5 60,15 65,10 100,10" />
                </svg>
                <span>PUPIL DILATION: <strong id="pupilText">2.8mm</strong></span>
            </div>

            <!-- Interrogation Hub -->
            <div class="interrogation-box">
                <div class="interrogation-label"><i class="fas fa-comments"></i> CONVERSATIONAL INTERROGATION PROTOCOL</div>
                <div class="quick-questions">
                    <button class="btn-interrogate" onclick="askQuestion('purpose')">
                        <i class="fas fa-question-circle"></i> "State the specific operational purpose of your visit."
                    </button>
                    <button class="btn-interrogate" onclick="askQuestion('host')">
                        <i class="fas fa-user-shield"></i> "Who is your internal facility contact and host department?"
                    </button>
                    <button class="btn-interrogate" onclick="askQuestion('vehicle')">
                        <i class="fas fa-car"></i> "Confirm vehicle registration plate and authorized cargo."
                    </button>
                    <button class="btn-interrogate" onclick="askQuestion('pressure')">
                        <i class="fas fa-exclamation-triangle"></i> "Why are you hesitating? You appear unusually stressed."
                    </button>
                </div>

                <div class="custom-query-wrap">
                    <input type="text" id="customInterrogationInput" placeholder="Type custom interrogation inquiry..." onkeydown="if(event.key==='Enter') askCustomQuestion()">
                    <button class="btn-ask" onclick="askCustomQuestion()"><i class="fas fa-paper-plane"></i> ASK</button>
                </div>
            </div>
        </div>

        <!-- Right: Forensic Document Desk & Tools -->
        <div class="game-panel">
            <div class="panel-header">
                <span><i class="fas fa-id-card"></i> CREDENTIAL DESK & FORENSIC SUITE</span>
                <div class="tool-bar-toggles">
                    <button class="btn-tool-toggle" id="btnToolUv" onclick="toggleForensicTool('uv')">
                        <i class="fas fa-lightbulb"></i> UV LAMP
                    </button>
                    <button class="btn-tool-toggle" id="btnToolHmac" onclick="toggleForensicTool('hmac')">
                        <i class="fas fa-microchip"></i> CRYPTO RADAR
                    </button>
                    <button class="btn-tool-toggle" id="btnToolBio" onclick="toggleForensicTool('bio')">
                        <i class="fas fa-fingerprint"></i> BIOMETRIC MATCH
                    </button>
                </div>
            </div>

            <!-- Physical Pass Document Card -->
            <div class="pass-document" id="passDocument">
                <div class="pass-doc-header">
                    <div>
                        <span class="org-badge-text">NEXUS DEFENSE COMPLEX • PERMIT</span>
                        <h4 class="doc-name" id="docVisitorName">Elena Rostova</h4>
                    </div>
                    <div style="text-align:right;">
                        <i class="fas fa-qrcode fa-2x text-cyan"></i>
                    </div>
                </div>

                <div class="doc-row">
                    <span>PASS NUMBER:</span>
                    <strong class="text-cyan" id="docPassNum">GP-49102847</strong>
                </div>
                <div class="doc-row">
                    <span>CATEGORY:</span>
                    <strong id="docCategory">Security Auditor</strong>
                </div>
                <div class="doc-row">
                    <span>CLEARANCE TIER:</span>
                    <strong id="docClearance" class="text-yellow">SECRET (LVL 3)</strong>
                </div>
                <div class="doc-row">
                    <span>EXPIRATION DATE:</span>
                    <strong id="docExpiry">2026-10-18</strong>
                </div>
                <div class="doc-row">
                    <span>VEHICLE (ALPR):</span>
                    <strong id="docVehicle">DL-04-CX-8192</strong>
                </div>
                <div class="doc-row">
                    <span>HMAC-SHA256 SIGNATURE:</span>
                    <span id="docHmac" class="hmac-token-chip text-cyan">0x8a9f...[VERIFIED]</span>
                </div>

                <!-- UV Watermark Overlay -->
                <div class="uv-watermark-overlay" id="uvOverlay">
                    <i class="fas fa-certificate fa-2x mb-2"></i>
                    <span id="uvWatermarkText">AUTHENTIC_OFFICIAL_WATERMARK</span>
                </div>
            </div>

            <!-- Intelligence Bulletin -->
            <div class="intel-bulletin">
                <div class="intel-bulletin-title"><i class="fas fa-broadcast-tower"></i> ACTIVE SECURITY BULLETIN</div>
                <div id="intelText" style="color:#d7ffff;">
                    • Current Gate: Alpha-01 • Today's Date: 2026-10-11
                </div>
            </div>

            <!-- Gatekeeper Decisions Hub -->
            <div class="decisions-hub">
                <button class="btn-decision btn-approve" onclick="submitDecision('APPROVE')">
                    <i class="fas fa-check-circle"></i> GRANT ENTRY (PASS)
                </button>
                <button class="btn-decision btn-deny" onclick="submitDecision('DENY')">
                    <i class="fas fa-ban"></i> DENY ACCESS
                </button>
                <button class="btn-decision btn-detain" onclick="submitDecision('DETAIN')">
                    <i class="fas fa-shield-virus"></i> DETAIN & SOUND ALARM
                </button>
            </div>
        </div>
    </div>

    <!-- Q-Learning RL Brain & Policy Telemetry -->
    <div class="rl-brain-card">
        <div class="rl-header" onclick="toggleRlDrawer()">
            <h4><i class="fas fa-brain text-cyan"></i> ADAPTIVE Q-LEARNING REINFORCEMENT LEARNING ENGINE (DIFFICULTY CONTROLLER)</h4>
            <span style="font-size:0.75rem; color:#00ffc3;"><i class="fas fa-sliders-h"></i> ACTIVE POLICY TELEMETRY</span>
        </div>
        <div class="rl-matrix-content" id="rlMatrixContent">
            <div class="q-metric-box">
                <span style="color:#799ea8;">CURRENT STATE $S_t$:</span><br>
                <strong class="text-cyan" id="rlState">STATE: ACC_HIGH • COMBO_COLD</strong>
            </div>
            <div class="q-metric-box">
                <span style="color:#799ea8;">CHOSEN POLICY ACTION $A_t$:</span><br>
                <strong class="text-yellow" id="rlAction">INJECT_HMAC_FORGERY</strong>
            </div>
            <div class="q-metric-box">
                <span style="color:#799ea8;">EXPLORATION $\epsilon$:</span><br>
                <strong id="rlEpsilon">0.20 (Learning Rate &alpha;=0.15)</strong>
            </div>
            <div class="q-metric-box">
                <span style="color:#799ea8;">CUMULATIVE REWARD $R$:</span><br>
                <strong class="text-green" id="rlReward">+120.0</strong>
            </div>
        </div>
    </div>

    <!-- Leaderboard Footer Strip -->
    <div class="leaderboard-footer" id="leaderboardFooter">
        <span><i class="fas fa-trophy text-yellow"></i> <strong>TOP AGENTS RECORD:</strong></span>
        <div id="lbItems" style="display:flex; gap:10px;">Loading high scores...</div>
    </div>
</div>

<!-- Shift End Summary Modal -->
<div class="modal-overlay" id="shiftModal">
    <div class="modal-box">
        <h2 style="color:#00ffc3; margin:0 0 10px 0;"><i class="fas fa-award"></i> SHIFT RECAP & PERFORMANCE RATING</h2>
        <div style="font-size:3rem; font-weight:900; color:#ffd600; margin:10px 0;" id="finalRank">RANK S</div>
        <p style="color:#8da4af;" id="finalSubtext">Exceptional border control diligence. Minimal breach footprint.</p>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin:20px 0; text-align:left; font-size:0.88rem;">
            <div style="background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:8px;">
                <span style="color:#799ea8;">Final Score:</span> <strong id="mScore" class="text-green">0</strong>
            </div>
            <div style="background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:8px;">
                <span style="color:#799ea8;">Accuracy:</span> <strong id="mAcc">100%</strong>
            </div>
            <div style="background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:8px;">
                <span style="color:#799ea8;">Breaches Blocked:</span> <strong id="mBreaches" class="text-cyan">0</strong>
            </div>
            <div style="background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:8px;">
                <span style="color:#799ea8;">Visitors Processed:</span> <strong id="mProcessed">0</strong>
            </div>
        </div>

        <div style="display:flex; gap:10px; justify-content:center;">
            <button class="btn-ask" style="padding:10px 18px;" onclick="saveScoreAndRestart()"><i class="fas fa-cloud-upload-alt"></i> Submit to Leaderboard</button>
            <button class="btn-micro" onclick="closeShiftModal()"><i class="fas fa-redo"></i> Continue Shift</button>
        </div>
    </div>
</div>

<script>
// Web Audio Synthesizer
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

        if (type === 'stamp') {
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(320, now);
            osc.frequency.exponentialRampToValueAtTime(120, now + 0.1);
            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.12);
            osc.start(now);
            osc.stop(now + 0.12);
        } else if (type === 'alarm') {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(440, now);
            osc.frequency.linearRampToValueAtTime(220, now + 0.35);
            gain.gain.setValueAtTime(0.35, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.4);
            osc.start(now);
            osc.stop(now + 0.4);
        } else if (type === 'ping') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, now);
            gain.gain.setValueAtTime(0.15, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
            osc.start(now);
            osc.stop(now + 0.2);
        }
    } catch(e) {}
}

// Q-Learning RL Engine for Dynamic Checkpoint Difficulty
class QLearningController {
    constructor() {
        this.actions = [
            'DEPLOY_FORGED_HMAC',
            'DEPLOY_COUNTERFEIT_UV',
            'DEPLOY_BLACKLIST_IMPERSONATOR',
            'DEPLOY_PLATE_MISMATCH',
            'DEPLOY_BIOMETRIC_DISCREPANCY',
            'DEPLOY_LEGITIMATE_FASTTRACK'
        ];
        this.qTable = {}; // key: stateString, value: array of Q-values
        this.alpha = 0.15; // learning rate
        this.gamma = 0.9;  // discount factor
        this.epsilon = 0.20; // exploration rate
        this.totalReward = 0;
        this.lastState = null;
        this.lastActionIdx = 0;
    }

    getState(accuracyPct, combo, timeRemaining) {
        const accTier = accuracyPct >= 80 ? 'ACC_HIGH' : (accuracyPct >= 60 ? 'ACC_MED' : 'ACC_LOW');
        const comboTier = combo >= 2.0 ? 'COMBO_HOT' : (combo >= 1.4 ? 'COMBO_WARM' : 'COMBO_COLD');
        const timeTier = timeRemaining > 15 ? 'TIME_CALM' : 'TIME_URGENT';
        return `${accTier}__${comboTier}__${timeTier}`;
    }

    getQValues(state) {
        if (!this.qTable[state]) {
            this.qTable[state] = new Array(this.actions.length).fill(0.0);
        }
        return this.qTable[state];
    }

    selectAction(state) {
        this.lastState = state;
        const qVals = this.getQValues(state);
        
        // Epsilon-greedy
        if (Math.random() < this.epsilon) {
            this.lastActionIdx = Math.floor(Math.random() * this.actions.length);
        } else {
            let maxVal = -Infinity;
            let bestIdx = 0;
            qVals.forEach((val, idx) => {
                if (val > maxVal) {
                    maxVal = val;
                    bestIdx = idx;
                }
            });
            this.lastActionIdx = bestIdx;
        }

        // Decay epsilon
        this.epsilon = Math.max(0.05, this.epsilon * 0.98);
        return this.actions[this.lastActionIdx];
    }

    update(reward, nextState) {
        this.totalReward += reward;
        if (!this.lastState) return;
        const currentQ = this.getQValues(this.lastState);
        const nextQ = this.getQValues(nextState);
        const maxNextQ = Math.max(...nextQ);

        // Q(s, a) = Q(s, a) + alpha * [reward + gamma * max Q(s', a') - Q(s, a)]
        currentQ[this.lastActionIdx] += this.alpha * (reward + this.gamma * maxNextQ - currentQ[this.lastActionIdx]);
    }
}

const rlAgent = new QLearningController();

// Game State
let gameState = {
    level: 1,
    score: 0,
    combo: 1.0,
    processed: 0,
    correctCount: 0,
    breachesStopped: 0,
    falseAccusations: 0,
    timeRemaining: 35,
    timerInterval: null,
    currentVisitor: null,
    activeTools: { uv: false, hmac: false, bio: false }
};

// Procedural Persona Pool
const firstNames = ["Col. Arthur", "Dr. Samantha", "Vikramaditya", "Aarav", "Elena", "Marcus", "Priya", "Agent Carlos", "Zoe", "Devraj", "Natalia", "Kavita", "Major Zhang"];
const lastNames = ["Pendleton", "Vance", "Rao", "Sharma", "Rostova", "Holloway", "Nair", "Mendoza", "Sterling", "Kulkarni", "Ivanov", "Bose", "Castillo"];
const categories = ["VIP Delegate", "Security Auditor", "Contractor", "Maintenance", "Diplomat", "Courier", "Research Scientist"];
const affiliations = ["NEXUS Cyber Command", "Quantum BioTech Ltd", "Global Logix Inc", "Federal Defense Agency", "DeepMind Security"];

function generateProceduralVisitor() {
    const acc = gameState.processed > 0 ? (gameState.correctCount / gameState.processed) * 100 : 100;
    const currentState = rlAgent.getState(acc, gameState.combo, gameState.timeRemaining);
    const chosenAction = rlAgent.selectAction(currentState);

    // Update RL Telemetry UI
    document.getElementById('rlState').textContent = currentState.replace(/__/g, ' • ');
    document.getElementById('rlAction').textContent = chosenAction;
    document.getElementById('rlEpsilon').textContent = `${rlAgent.epsilon.toFixed(2)} (α=${rlAgent.alpha})`;
    document.getElementById('rlReward').textContent = (rlAgent.totalReward >= 0 ? '+' : '') + rlAgent.totalReward.toFixed(1);

    const fn = firstNames[Math.floor(Math.random() * firstNames.length)];
    const ln = lastNames[Math.floor(Math.random() * lastNames.length)];
    const name = `${fn} ${ln}`;
    const category = categories[Math.floor(Math.random() * categories.length)];
    const company = affiliations[Math.floor(Math.random() * affiliations.length)];
    const passNum = 'GP-' + Math.floor(10000000 + Math.random() * 90000000);
    const vehicle = `DL-0${Math.floor(1+Math.random()*9)}-${['AX','CX','BZ','KL'][Math.floor(Math.random()*4)]}-${Math.floor(1000+Math.random()*9000)}`;

    const today = new Date();
    let expiryDate = new Date();
    expiryDate.setDate(today.getDate() + 4);

    let isDeceptive = (chosenAction !== 'DEPLOY_LEGITIMATE_FASTTRACK');
    let deceptionType = chosenAction;

    let hmacValid = true;
    let uvValid = true;
    let plateMatch = true;
    let bioMatch = true;
    let isBlacklisted = false;

    if (chosenAction === 'DEPLOY_FORGED_HMAC') {
        hmacValid = false;
    } else if (chosenAction === 'DEPLOY_COUNTERFEIT_UV') {
        uvValid = false;
    } else if (chosenAction === 'DEPLOY_BLACKLIST_IMPERSONATOR') {
        isBlacklisted = true;
    } else if (chosenAction === 'DEPLOY_PLATE_MISMATCH') {
        plateMatch = false;
    } else if (chosenAction === 'DEPLOY_BIOMETRIC_DISCREPANCY') {
        bioMatch = false;
    }

    const stressLevels = isDeceptive ? ["NERVOUS", "SHIFTY", "AGITATED"] : ["CALM", "NORMAL", "FOCUSED"];
    const stress = stressLevels[Math.floor(Math.random() * stressLevels.length)];
    const bpm = isDeceptive ? Math.floor(115 + Math.random() * 35) : Math.floor(68 + Math.random() * 14);
    const pupil = isDeceptive ? (4.2 + Math.random() * 1.2).toFixed(1) : (2.7 + Math.random() * 0.4).toFixed(1);

    // Conversational LLM-style Adversarial Dialogue Prompts
    const deceptiveExcuses = {
        purpose: [
            `"Just routine third-floor server maintenance on behalf of ${company}... nothing sensitive officer."`,
            `"I have an urgent, off-the-record briefing with the director. Don't cause an incident by delaying me!"`,
            `"Courier transit. What's inside this container is classified above your pay grade."`
        ],
        host: [
            `"My host? Uh, Commander Vance. Or someone from section 4. Just verify the badge on your screen!"`,
            `"Dr. Kulkarni called me in personally 10 minutes ago. He didn't give me time to log a desk extension."`,
            `"I'm an external consultant, officer. Internal contacts don't apply to my clearance level."`
        ],
        vehicle: plateMatch ? [
            `"Registered company transport plate ${vehicle}. It's in the system."`
        ] : [
            `"Oh, our logistics dispatch gave me a replacement pool car today... ${vehicle} is my personal vehicle!"`,
            `"The radar plate read यूपी-16? Must be dirt on the camera optical sensor, officer."`
        ],
        pressure: [
            `"Stressed? I'm missing an executive boardroom meeting while you nitpick my credentials!"`,
            `"It's hot in this checkpoint vestibule, that's all. Can we finish this check?"`,
            `"Look officer, I've had a 14-hour flight. Clear the turnstile so we both can go home."`
        ]
    };

    const honestDialogues = {
        purpose: `"I am conducting the scheduled bi-weekly infrastructure inspection for ${company}."`,
        host: `"Meeting Dr. Samantha Vance at Level-3 Cyber Research. Escort protocol pre-registered."`,
        vehicle: `"Vehicle ${vehicle} verified on file with logistics gate."`,
        pressure: `"Just routine procedure, officer. Take all the time you need to verify."`
    };

    return {
        name,
        category,
        company,
        passNum,
        vehicle,
        actualVehicle: plateMatch ? vehicle : `UP-16-ZZ-${Math.floor(1000+Math.random()*9000)}`,
        expiryDate: expiryDate.toISOString().split('T')[0],
        hmacValid,
        uvValid,
        plateMatch,
        bioMatch,
        isBlacklisted,
        deceptionType,
        isDeceptive,
        stress,
        bpm,
        pupil,
        dialogues: {
            purpose: isDeceptive ? deceptiveExcuses.purpose[Math.floor(Math.random()*deceptiveExcuses.purpose.length)] : honestDialogues.purpose,
            host: isDeceptive ? deceptiveExcuses.host[Math.floor(Math.random()*deceptiveExcuses.host.length)] : honestDialogues.host,
            vehicle: isDeceptive ? deceptiveExcuses.vehicle[Math.floor(Math.random()*deceptiveExcuses.vehicle.length)] : honestDialogues.vehicle,
            pressure: isDeceptive ? deceptiveExcuses.pressure[Math.floor(Math.random()*deceptiveExcuses.pressure.length)] : honestDialogues.pressure
        }
    };
}

function loadNextVisitor() {
    gameState.currentVisitor = generateProceduralVisitor();
    const v = gameState.currentVisitor;

    // Reset Visuals
    document.getElementById('docVisitorName').textContent = v.name;
    document.getElementById('docPassNum').textContent = v.passNum;
    document.getElementById('docCategory').textContent = v.category;
    document.getElementById('docClearance').textContent = v.category.includes('VIP') ? 'TOP SECRET (TIER 4)' : 'STANDARD FACILITY';
    document.getElementById('docExpiry').textContent = v.expiryDate;
    document.getElementById('docVehicle').textContent = v.vehicle;
    document.getElementById('docHmac').textContent = v.hmacValid ? "0x" + Math.random().toString(16).substr(2, 6) + "...[VALID]" : "0x" + Math.random().toString(16).substr(2, 6) + "...[CORRUPT_CHECKSUM]";
    document.getElementById('docHmac').style.color = v.hmacValid ? "#00ffc3" : "#ff4d4d";

    // Cardiac & Stress
    document.getElementById('pulseBpm').textContent = v.bpm;
    document.getElementById('pupilText').textContent = v.pupil + "mm";
    const sBadge = document.getElementById('stressBadge');
    sBadge.textContent = "STRESS: " + v.stress;
    sBadge.style.color = v.stress === 'NERVOUS' ? '#ffd600' : (v.stress === 'AGITATED' ? '#ff4d4d' : '#00ffc3');

    document.getElementById('sweatBead').className = (v.stress === 'AGITATED' || v.stress === 'NERVOUS') ? 'sweat-bead sweat-active' : 'sweat-bead';

    // SVG Avatar
    const colors = ['#00ffc3', '#00e5ff', '#ffd600', '#ff80ab', '#a7ffeb'];
    const col = colors[Math.floor(Math.random() * colors.length)];
    document.getElementById('avatarSvg').innerHTML = `
        <svg viewBox="0 0 100 100" width="105" height="105">
            <circle cx="50" cy="40" r="26" fill="${col}" opacity="0.85" />
            <circle cx="42" cy="38" r="${v.pupil > 4 ? '5' : '3.5'}" fill="#091319" />
            <circle cx="58" cy="38" r="${v.pupil > 4 ? '5' : '3.5'}" fill="#091319" />
            <path d="M 40 50 Q 50 ${v.isDeceptive ? '47' : '55'} 60 50" stroke="#091319" stroke-width="3" fill="none" />
            <path d="M 20 95 Q 50 65 80 95" fill="${col}" opacity="0.6" />
        </svg>
    `;

    document.getElementById('dialogueText').textContent = `"Greetings officer. Presenting credential ${v.passNum} for entry authorization."`;

    // UV Overlay status
    document.getElementById('uvWatermarkText').textContent = v.uvValid ? "★ AUTHENTIC OFFICIAL WATERMARK ★" : "⚠️ COUNTERFEIT UV STAMP DETECTED";
    document.getElementById('uvWatermarkText').style.color = v.uvValid ? "#00ffff" : "#ff3344";

    // Bulletin
    if (v.isBlacklisted) {
        document.getElementById('intelText').innerHTML = `<span class="text-red"><strong>CRITICAL FLASH:</strong> Infiltrator alert issued matching alias <u>${v.name}</u>! Detain immediately!</span>`;
    } else {
        document.getElementById('intelText').innerHTML = `• Gate Alpha-01 • Radar active • Check license plate and holographic seals.`;
    }

    // Reset Tools
    setToolState('uv', false);
    setToolState('hmac', false);
    setToolState('bio', false);

    resetTimer();
}

function askQuestion(type) {
    if (!gameState.currentVisitor) return;
    playSfx('ping');
    const ans = gameState.currentVisitor.dialogues[type] || `"No comment on that, officer."`;
    document.getElementById('dialogueText').textContent = ans;
}

function askCustomQuestion() {
    const input = document.getElementById('customInterrogationInput');
    const val = input.value.trim();
    if (!val || !gameState.currentVisitor) return;

    playSfx('ping');
    const v = gameState.currentVisitor;
    let reply = "";

    if (v.isDeceptive) {
        const deceptiveReplies = [
            `"I already answered your questions! Why are you scrutinizing my paperwork so aggressively?"`,
            `"Everything required is documented on that card. Just scan the code and let me through!"`,
            `"That's internal proprietary business for ${v.company}. I'm not authorized to disclose that at the gate."`
        ];
        reply = deceptiveReplies[Math.floor(Math.random() * deceptiveReplies.length)];
    } else {
        reply = `"Regarding '${val}', everything is in full compliance with facility guidelines. You can confirm with ${v.company}."`;
    }

    document.getElementById('dialogueText').textContent = reply;
    input.value = "";
}

function toggleForensicTool(tool) {
    setToolState(tool, !gameState.activeTools[tool]);
}

function setToolState(tool, active) {
    gameState.activeTools[tool] = active;
    if (tool === 'uv') {
        document.getElementById('btnToolUv').classList.toggle('active', active);
        document.getElementById('uvOverlay').style.display = active ? 'flex' : 'none';
        if (active) playSfx('ping');
    } else if (tool === 'hmac') {
        document.getElementById('btnToolHmac').classList.toggle('active', active);
        if (active) {
            playSfx('ping');
            const v = gameState.currentVisitor;
            alert(`[CRYPTO RADAR TELEMETRY]\nPass: ${v.passNum}\nHMAC Token: ${v.hmacValid ? 'AUTHENTIC' : 'CRYPTOGRAPHIC FORGERY'}\nPlate Sensor: ${v.plateMatch ? 'MATCHES RADAR' : 'MISMATCH (Detected ' + v.actualVehicle + ')'}`);
        }
    } else if (tool === 'bio') {
        document.getElementById('btnToolBio').classList.toggle('active', active);
        if (active) {
            playSfx('ping');
            const v = gameState.currentVisitor;
            alert(`[BIOMETRIC SCANNER]\nFacial Mesh Match: ${v.bioMatch ? '98.8% MATCH (PASSED)' : '62.4% DISCREPANCY (FACIAL ASYMMETRY / POSSIBLE IMPERSONATOR)'}`);
        }
    }
}

function submitDecision(action) {
    if (!gameState.currentVisitor) return;
    clearInterval(gameState.timerInterval);

    playSfx('stamp');
    const v = gameState.currentVisitor;
    let correct = false;
    let reward = 0;

    if (action === 'APPROVE') {
        if (!v.isDeceptive) {
            correct = true;
            reward = 100 * gameState.combo;
        } else {
            reward = -150; // allowed security breach!
        }
    } else if (action === 'DENY') {
        if (v.isDeceptive) {
            correct = true;
            reward = 80 * gameState.combo;
        } else {
            reward = -50; // false accusation
            gameState.falseAccusations++;
        }
    } else if (action === 'DETAIN') {
        if (v.isDeceptive && (v.isBlacklisted || !v.hmacValid || !v.uvValid)) {
            correct = true;
            reward = 150 * gameState.combo;
            gameState.breachesStopped++;
            playSfx('alarm');
        } else if (v.isDeceptive) {
            correct = true;
            reward = 100 * gameState.combo;
            gameState.breachesStopped++;
        } else {
            reward = -100; // detained innocent visitor!
            gameState.falseAccusations++;
            playSfx('alarm');
        }
    }

    gameState.processed++;

    if (correct) {
        gameState.correctCount++;
        gameState.score += Math.round(reward);
        gameState.combo = Math.min(gameState.combo + 0.2, 3.0);
        showFeedback("CORRECT DECISION! +" + Math.round(reward) + " pts", "success");
    } else {
        gameState.combo = 1.0;
        gameState.score = Math.max(0, gameState.score + Math.round(reward));
        showFeedback(action === 'APPROVE' ? "⚠️ SECURITY BREACH PERMITTED! -150 pts" : "⚠️ FALSE ACCUSATION! -50 pts", "error");
    }

    // Update RL Agent
    const acc = Math.round((gameState.correctCount / gameState.processed) * 100);
    const nextState = rlAgent.getState(acc, gameState.combo, gameState.timeRemaining);
    rlAgent.update(reward, nextState);

    gameState.level = Math.floor(gameState.processed / 5) + 1;
    updateHUD();

    setTimeout(loadNextVisitor, 1100);
}

function showFeedback(msg, type) {
    const banner = document.createElement('div');
    banner.style.position = 'fixed';
    banner.style.top = '65px';
    banner.style.left = '50%';
    banner.style.transform = 'translateX(-50%)';
    banner.style.padding = '12px 28px';
    banner.style.borderRadius = '30px';
    banner.style.fontWeight = '900';
    banner.style.fontSize = '1.05rem';
    banner.style.zIndex = '99999';
    banner.style.boxShadow = '0 10px 40px rgba(0,0,0,0.85)';
    banner.style.letterSpacing = '0.06em';
    banner.style.background = type === 'success' ? '#00ffc3' : '#ff3344';
    banner.style.color = type === 'success' ? '#053027' : '#ffffff';
    banner.textContent = msg;
    document.body.appendChild(banner);
    setTimeout(() => banner.remove(), 1100);
}

function updateHUD() {
    document.getElementById('hudLevel').textContent = "TIER " + gameState.level;
    document.getElementById('hudScore').textContent = gameState.score;
    document.getElementById('hudCombo').textContent = "x" + gameState.combo.toFixed(1);
    document.getElementById('hudBreaches').textContent = gameState.breachesStopped;
    const acc = gameState.processed > 0 ? Math.round((gameState.correctCount / gameState.processed) * 100) : 100;
    document.getElementById('hudAccuracy').textContent = acc + "%";
}

function resetTimer() {
    clearInterval(gameState.timerInterval);
    gameState.timeRemaining = Math.max(14, 35 - (gameState.level * 2));
    document.getElementById('hudTimer').textContent = gameState.timeRemaining + "s";

    gameState.timerInterval = setInterval(() => {
        gameState.timeRemaining--;
        document.getElementById('hudTimer').textContent = gameState.timeRemaining + "s";
        if (gameState.timeRemaining <= 0) {
            clearInterval(gameState.timerInterval);
            showFeedback("TIME EXPIRED! TIMEOUT DENIAL", "error");
            submitDecision('DENY');
        }
    }, 1000);
}

function toggleRlDrawer() {
    const content = document.getElementById('rlMatrixContent');
    content.style.display = content.style.display === 'none' ? 'grid' : 'none';
}

function endShift() {
    clearInterval(gameState.timerInterval);
    const acc = gameState.processed > 0 ? Math.round((gameState.correctCount / gameState.processed) * 100) : 100;
    
    let rank = 'F';
    let sub = 'Shift terminated with critical failure rate.';
    if (gameState.score >= 1200 && acc >= 90) {
        rank = 'RANK S';
        sub = 'Flawless border control. Exceptional adversarial threat interception!';
    } else if (gameState.score >= 800 && acc >= 80) {
        rank = 'RANK A';
        sub = 'High tactical vigilance. Minimal contraband leakage.';
    } else if (gameState.score >= 400 && acc >= 65) {
        rank = 'RANK B';
        sub = 'Acceptable baseline performance. Additional forensic training advised.';
    } else if (gameState.score > 0) {
        rank = 'RANK C';
        sub = 'Multiple breach oversights recorded on security logs.';
    }

    document.getElementById('finalRank').textContent = rank;
    document.getElementById('finalSubtext').textContent = sub;
    document.getElementById('mScore').textContent = gameState.score + " pts";
    document.getElementById('mAcc').textContent = acc + "%";
    document.getElementById('mBreaches').textContent = gameState.breachesStopped;
    document.getElementById('mProcessed').textContent = gameState.processed;

    document.getElementById('shiftModal').style.display = 'flex';
}

function closeShiftModal() {
    document.getElementById('shiftModal').style.display = 'none';
    resetTimer();
}

function saveScoreAndRestart() {
    const agentName = prompt("Enter Security Officer Callsign:", "Officer-" + Math.floor(100 + Math.random() * 900));
    if (!agentName) return;

    const payload = {
        agent_name: agentName,
        score: gameState.score,
        level: gameState.level,
        accuracy_pct: gameState.processed > 0 ? ((gameState.correctCount / gameState.processed) * 100).toFixed(2) : 100,
        processed_count: gameState.processed,
        breaches_prevented: gameState.breachesStopped,
        throughput_rate: (gameState.processed / 1.8).toFixed(2)
    };

    fetch('api/leaderboard.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message || 'Score recorded successfully on the Nexus Leaderboard!');
        closeShiftModal();
        loadLeaderboard();
        // Reset shift
        gameState.score = 0;
        gameState.processed = 0;
        gameState.correctCount = 0;
        gameState.breachesStopped = 0;
        gameState.combo = 1.0;
        updateHUD();
        loadNextVisitor();
    });
}

function loadLeaderboard() {
    fetch('api/leaderboard.php')
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;
        const container = document.getElementById('lbItems');
        if (data.leaderboard && data.leaderboard.length > 0) {
            container.innerHTML = data.leaderboard.slice(0, 8).map((item, idx) => `
                <div class="lb-pill">
                    <span class="text-cyan">#${idx + 1}</span>
                    <strong>${item.agent_name}</strong>:
                    <span class="text-green">${item.score} pts</span>
                    <small style="color:#799ea8;">(${item.accuracy_pct}%)</small>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<span style="color:#799ea8;">No high scores registered yet.</span>';
        }
    });
}

// Initial start
document.addEventListener('DOMContentLoaded', () => {
    loadNextVisitor();
    loadLeaderboard();
});
</script>

<?php include 'includes/footer.php'; ?>
