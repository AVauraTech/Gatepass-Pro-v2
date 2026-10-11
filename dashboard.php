<?php
// dashboard.php - GatePass Nexus Next-Gen Control Center
session_start();
include('db.php');

if (!isset($_SESSION['admin_id']) || strlen($_SESSION['admin_id']) == 0) {
    header("location:logout.php");
    exit;
}

$active_page = 'dashboard.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// Check overstay anomalies automatically
checkOverstayAnomalies($conn);

// Gather stats
$total_passes = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM passes"));
$today_passes = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM passes WHERE date(pass_creation_date) = CURDATE()"));
$active_inside = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM passes WHERE status = 'Checked-In'"));
$overstay_alerts = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM passes WHERE overstay_alert = 1 OR status = 'Flagged'"));
$unresolved_alerts = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM security_alerts WHERE resolved = 0"));

$threat_level = 'GREEN';
if ($unresolved_alerts >= 3 || $overstay_alerts >= 3) {
    $threat_level = 'RED';
} elseif ($unresolved_alerts > 0 || $overstay_alerts > 0) {
    $threat_level = 'AMBER';
}

// 7-Day Issuance Telemetry Volume
$weeklyData = [];
$maxWeeklyCount = 1;
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dayLabel = date('D', strtotime("-$i days"));
    $fullLabel = date('M d', strtotime("-$i days"));
    $cntQ = mysqli_query($conn, "SELECT COUNT(id) as c FROM passes WHERE DATE(pass_creation_date) = '$d'");
    $cntRow = mysqli_fetch_assoc($cntQ);
    $c = intval($cntRow['c'] ?? 0);
    if ($c > $maxWeeklyCount) $maxWeeklyCount = $c;
    $weeklyData[] = [
        'date' => $d,
        'day' => $dayLabel,
        'label' => $fullLabel,
        'count' => $c
    ];
}

// Fetch recent checkpoint logs
$logs_query = mysqli_query($conn, "SELECT pass_number, visitor_name, action, checkpoint_gate, biometric_match_pct, alpr_plate, timestamp FROM checkpoint_logs ORDER BY id DESC LIMIT 6");
$recent_logs = [];
if ($logs_query) {
    while ($l = mysqli_fetch_assoc($logs_query)) {
        $recent_logs[] = $l;
    }
}
?>
<style>
        .dashboard-header {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(14px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            border: 1px solid rgba(0, 255, 195, 0.2);
            border-radius: 24px;
            padding: 28px 24px;
            text-align: center;
            position: relative;
            margin-bottom: 24px;
        }
        .nexus-logo-title {
            font-size: 2.3rem;
            font-weight: 900;
            color: #24ffe3;
            letter-spacing: 0.12em;
            margin-bottom: 6px;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            transition: color 0.3s;
        }
        .nexus-logo-title:hover {
            color: #62ffd9;
            text-shadow: 0 0 24px rgba(36, 255, 227, 0.6);
        }
        .subtitle {
            color: #8eaebc;
            font-size: 0.92rem;
            margin-bottom: 12px;
        }
        .datetime-bar {
            font-size: 0.85rem;
            background: rgba(36, 255, 227, 0.12);
            color: #24ffe3;
            border-radius: 20px;
            max-width: 380px;
            margin: 0 auto;
            padding: 6px 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid rgba(36, 255, 227, 0.25);
        }

        /* KPI Cards Grid */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .gcard {
            background: rgba(18, 28, 38, 0.82);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(0, 255, 195, 0.22);
            border-radius: 20px;
            padding: 22px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .gcard:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 255, 195, 0.3);
            border-color: #00ffc3;
        }
        .gcard-title {
            font-weight: 700;
            font-size: 0.78rem;
            color: #799ea8;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .gcard-number {
            font-size: 2rem;
            font-weight: 900;
            color: #fff;
            letter-spacing: 0.04em;
        }
        .gcard-tag {
            font-size: 0.72rem;
            color: #a0d5d5;
        }
        .gcard .icon {
            font-size: 2.2rem;
            color: #24ffe3;
            filter: drop-shadow(0 0 12px rgba(36, 255, 227, 0.5));
        }

        .threat-pill {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 900;
            letter-spacing: 0.06em;
        }
        .threat-GREEN { background: rgba(0, 255, 136, 0.2); color: #00ff88; border: 1px solid #00ff88; }
        .threat-AMBER { background: rgba(255, 170, 0, 0.2); color: #ffaa00; border: 1px solid #ffaa00; }
        .threat-RED { background: rgba(255, 51, 68, 0.2); color: #ff3344; border: 1px solid #ff3344; animation: blinkThreat 1.2s infinite; }
        @keyframes blinkThreat { 50% { opacity: 0.5; } }

        /* Telemetry Chart & Overstay Monitor Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 992px) {
            .analytics-grid { grid-template-columns: 1fr; }
        }
        .analytics-card {
            background: rgba(18, 28, 38, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 255, 195, 0.22);
            border-radius: 20px;
            padding: 22px 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
        }
        .card-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #24ffe3;
            font-size: 0.92rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0, 255, 195, 0.18);
            margin-bottom: 18px;
        }

        /* 7-Day Glowing Neon Bar Chart */
        .chart-bars-wrap {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 140px;
            padding: 10px 10px 0 10px;
            gap: 12px;
        }
        .bar-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            height: 100%;
            justify-content: flex-end;
        }
        .bar-pill {
            width: 100%;
            max-width: 38px;
            background: linear-gradient(180deg, #00ffc3, #008f75);
            border-radius: 6px 6px 0 0;
            box-shadow: 0 0 12px rgba(0, 255, 195, 0.4);
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
        }
        .bar-pill:hover {
            box-shadow: 0 0 20px rgba(0, 255, 195, 0.8);
            transform: scaleY(1.05);
        }
        .bar-count-tooltip {
            position: absolute;
            top: -24px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.72rem;
            font-weight: 800;
            color: #24ffe3;
        }
        .bar-date-label {
            margin-top: 8px;
            font-size: 0.72rem;
            color: #799ea8;
            font-weight: 600;
        }

        /* Overstay Anomaly Scanner Box */
        .overstay-box-content {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1;
        }
        .btn-run-overstay {
            background: linear-gradient(135deg, #ff9800, #e65100);
            color: #fff;
            border: none;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.84rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.4);
        }
        .btn-run-overstay:hover {
            box-shadow: 0 6px 22px rgba(255, 152, 0, 0.6);
            transform: translateY(-2px);
        }

        /* Quick Launch Action Deck */
        .action-deck {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .launch-card {
            background: linear-gradient(135deg, rgba(20, 35, 48, 0.9), rgba(12, 22, 30, 0.95));
            border: 1px solid rgba(0, 255, 195, 0.3);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-decoration: none;
            color: inherit;
            transition: all 0.25s;
        }
        .launch-card:hover {
            transform: translateY(-5px);
            border-color: #00ffc3;
            box-shadow: 0 15px 40px rgba(0, 255, 195, 0.4);
            color: inherit;
        }
        .launch-badge {
            align-self: flex-start;
            background: rgba(0, 255, 195, 0.15);
            color: #00ffc3;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 12px;
            margin-bottom: 10px;
            letter-spacing: 0.06em;
        }
        .launch-card h3 {
            font-size: 1.2rem;
            font-weight: 800;
            margin: 0 0 6px 0;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .launch-card p {
            font-size: 0.82rem;
            color: #8da4af;
            margin: 0 0 14px 0;
            line-height: 1.4;
        }
        .launch-cta {
            font-size: 0.82rem;
            font-weight: 800;
            color: #00ffc3;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Recent Activity Table */
        .feed-card {
            background: rgba(18, 28, 38, 0.85);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(0, 255, 195, 0.2);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .feed-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 800;
            color: #24ffe3;
            font-size: 0.92rem;
            letter-spacing: 0.06em;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 255, 195, 0.15);
        }
        .feed-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
        }
        .feed-table th {
            text-align: left;
            padding: 10px 12px;
            color: #799ea8;
            font-size: 0.74rem;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(0, 255, 195, 0.2);
        }
        .feed-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #d7ffff;
        }
        .action-tag {
            background: rgba(0, 255, 195, 0.12);
            color: #00ffc3;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 700;
        }
    </style>

    <div class="dashboard-header">
        <div class="nexus-logo-title" id="nexusEgg" title="Click me 5 times!">
            <i class="fa-solid fa-shield-halved" style="font-size:2rem; margin-right:12px; color:#24ffe3;"></i>
            GatePass Nexus Pro <span class="egg">✨</span>
        </div>
        <div class="subtitle">Next-Gen Enterprise Security & Access Operations • Edge AI Vision • RL Checkpoint Game</div>
        <div class="datetime-bar" id="dtbar"><i class="fa-regular fa-clock"></i> <span id="clock"></span></div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="dashboard-cards">
        <div class="gcard">
            <div>
                <div class="gcard-title">Total Passes</div>
                <div class="gcard-number" id="kpiTotal"><?php echo $total_passes; ?></div>
                <div class="gcard-tag">Cumulative Registry</div>
            </div>
            <i class="fa-solid fa-id-card-clip icon"></i>
        </div>
        <div class="gcard">
            <div>
                <div class="gcard-title">Today's Passes</div>
                <div class="gcard-number" id="kpiToday"><?php echo $today_passes; ?></div>
                <div class="gcard-tag">Issued Today</div>
            </div>
            <i class="fa-solid fa-calendar-day icon"></i>
        </div>
        <div class="gcard">
            <div>
                <div class="gcard-title">Active Inside</div>
                <div class="gcard-number" style="color:#00ff88;" id="kpiActive"><?php echo $active_inside; ?></div>
                <div class="gcard-tag">Currently Checked-In</div>
            </div>
            <i class="fa-solid fa-building-user icon" style="color:#00ff88;"></i>
        </div>
        <div class="gcard">
            <div>
                <div class="gcard-title">Overstay Alerts</div>
                <div class="gcard-number" style="color:<?php echo $overstay_alerts > 0 ? '#ff3344' : '#00ffc3'; ?>;" id="kpiAlerts"><?php echo $overstay_alerts; ?></div>
                <div class="gcard-tag">Unresolved Anomalies</div>
            </div>
            <i class="fa-solid fa-bell-exclamation icon" style="color:<?php echo $overstay_alerts > 0 ? '#ff3344' : '#00ffc3'; ?>;"></i>
        </div>
        <div class="gcard">
            <div>
                <div class="gcard-title">Threat Level</div>
                <div class="mt-2"><span class="threat-pill threat-<?php echo $threat_level; ?>"><?php echo $threat_level; ?> DEFCON</span></div>
                <div class="gcard-tag mt-2">AI Sentinel Monitor</div>
            </div>
            <i class="fa-solid fa-radar icon"></i>
        </div>
    </div>

    <!-- Analytics & Overstay Engine Section -->
    <div class="analytics-grid">
        <!-- 7-Day Issuance Telemetry -->
        <div class="analytics-card">
            <div class="card-heading">
                <span><i class="fas fa-chart-bar"></i> 7-DAY CREDENTIAL ISSUANCE TELEMETRY</span>
                <span style="font-size:0.75rem; color:#799ea8;">DAILY VOLUME</span>
            </div>
            <div class="chart-bars-wrap">
                <?php foreach ($weeklyData as $bar): 
                    $pct = max(15, round(($bar['count'] / $maxWeeklyCount) * 100));
                ?>
                <div class="bar-col">
                    <div class="bar-pill" style="height: <?php echo $pct; ?>%;">
                        <span class="bar-count-tooltip"><?php echo $bar['count']; ?></span>
                    </div>
                    <span class="bar-date-label"><?php echo $bar['day']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Overstay Anomaly Engine Tracker -->
        <div class="analytics-card">
            <div class="card-heading">
                <span><i class="fas fa-user-clock text-orange"></i> TEMPORAL OVERSTAY TRACKER</span>
                <span class="badge-pill" style="font-size:0.72rem; color:#ff9800; border:1px solid #ff9800; padding:2px 8px; border-radius:10px;">CELERY WORKER</span>
            </div>
            <div class="overstay-box-content">
                <p style="font-size:0.84rem; color:#8da4af; margin:0 0 14px 0;">
                    Automated background tracker scans active checked-in visitors against facility maximum dwell limits (8 hrs) and expiration windows, dispatching instant anomaly alerts.
                </p>
                <div style="background:rgba(255,255,255,0.04); padding:10px 14px; border-radius:10px; border:1px solid rgba(0,255,195,0.15); margin-bottom:14px; font-size:0.8rem;">
                    <span>Active Dwell Incidents: <strong style="color:<?php echo $overstay_alerts > 0 ? '#ff4d4d' : '#00ffc3'; ?>;"><?php echo $overstay_alerts; ?></strong></span><br>
                    <span>Unresolved Security Alerts: <strong style="color:#ffd600;"><?php echo $unresolved_alerts; ?></strong></span>
                </div>
                <button class="btn-run-overstay" onclick="triggerOverstayScan()">
                    <i class="fas fa-radar"></i> EXECUTE TEMPORAL DWELL SCAN
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Launch Deck -->
    <div class="action-deck">
        <a href="checkpoint-terminal.php" class="launch-card">
            <div>
                <span class="launch-badge">PRIMARY OPERATIONS</span>
                <h3><i class="fas fa-video text-cyan"></i> Checkpoint Terminal</h3>
                <p>Edge AI Computer Vision HUD, optical QR verification (&lt;50ms HMAC latency), and automated ALPR vehicle plate recognition.</p>
            </div>
            <div class="launch-cta">LAUNCH TERMINAL ALPHA <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="gatekeeper-sim.php" class="launch-card">
            <div>
                <span class="launch-badge" style="color:#ffd600; background:rgba(255,214,0,0.15);">ADVERSARIAL SIMULATION</span>
                <h3><i class="fas fa-gamepad text-yellow"></i> Gatekeeper AI Game</h3>
                <p>Reinforcement Learning adversarial checkpoint game. Inspect synthesized visitor credentials, detect forged HMAC passes, and climb leaderboards.</p>
            </div>
            <div class="launch-cta" style="color:#ffd600;">ENTER SIMULATION MODE <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="manage-passes.php" class="launch-card">
            <div>
                <span class="launch-badge" style="color:#00ff88; background:rgba(0,255,136,0.15);">DATABASE REGISTRY</span>
                <h3><i class="fas fa-tasks text-green"></i> PassVault Registry</h3>
                <p>Real-time client-side search filtering across visitor records, clearance tiers, vehicle registration plates, and overstay statuses.</p>
            </div>
            <div class="launch-cta" style="color:#00ff88;">OPEN PASSVAULT <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="self-register.php" target="_blank" class="launch-card">
            <div>
                <span class="launch-badge" style="color:#00e5ff; background:rgba(0,229,255,0.15);">PUBLIC PORTAL</span>
                <h3><i class="fas fa-id-badge text-cyan"></i> Self-Service Portal</h3>
                <p>Visitor pre-registration flow with internal host approval workflows, HTML5 digital NDA e-signatures, and instant HMAC pass dispatch.</p>
            </div>
            <div class="launch-cta" style="color:#00e5ff;">OPEN VISITOR PORTAL <i class="fas fa-external-link-alt"></i></div>
        </a>
    </div>

    <!-- Live Real-Time Checkpoint Stream -->
    <div class="feed-card">
        <div class="feed-title">
            <span><i class="fas fa-stream"></i> RECENT CHECKPOINT AUDIT TELEMETRY</span>
            <a href="checkpoint-terminal.php" style="color:#00ffc3; font-size:0.8rem; text-decoration:none;"><i class="fas fa-expand"></i> Full Screen Terminal</a>
        </div>
        <table class="feed-table">
            <thead>
                <tr>
                    <th>TIMESTAMP</th>
                    <th>PASS NUMBER</th>
                    <th>VISITOR NAME</th>
                    <th>ACTION</th>
                    <th>CHECKPOINT GATE</th>
                    <th>BIOMETRIC %</th>
                    <th>ALPR PLATE</th>
                </tr>
            </thead>
            <tbody id="feedTableBody">
                <?php if (count($recent_logs) > 0): ?>
                    <?php foreach ($recent_logs as $log): ?>
                        <tr>
                            <td><small style="color:#8da4af;"><?php echo $log['timestamp']; ?></small></td>
                            <td><strong style="color:#00ffc3;"><?php echo htmlspecialchars($log['pass_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($log['visitor_name'] ?: 'Visitor'); ?></td>
                            <td><span class="action-tag"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['checkpoint_gate']); ?></td>
                            <td><?php echo $log['biometric_match_pct'] ? $log['biometric_match_pct'].'%' : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($log['alpr_plate'] ?: 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#6a828f;">No checkpoint actions logged yet today. Launch Checkpoint Terminal to process passes.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<script>
function updateClock(){
    let now = new Date();
    let opts = { weekday: 'short', year: 'numeric', month: 'long', day: 'numeric' };
    let t = now.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    document.getElementById('clock').textContent = now.toLocaleDateString(undefined, opts) + " — " + t;
}
setInterval(updateClock, 1000);
updateClock();

function triggerOverstayScan() {
    fetch('api/overstay-engine.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`[TEMPORAL SCAN COMPLETE]\nInspected Visitors: ${data.inspected_active_visitors}\nAnomalies Flagged: ${data.anomalies_flagged_count}\nLatency: ${data.execution_time_ms}ms`);
            location.reload();
        }
    });
}

// Auto-refresh telemetry stream every 5s
function refreshDashboardStream() {
    fetch('api/telemetry.php')
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;
        document.getElementById('kpiTotal').textContent = data.metrics.total_passes;
        document.getElementById('kpiToday').textContent = data.metrics.today_passes;
        document.getElementById('kpiActive').textContent = data.metrics.active_inside;
        document.getElementById('kpiAlerts').textContent = data.metrics.overstay_alerts;

        const tbody = document.getElementById('feedTableBody');
        if (data.recent_logs && data.recent_logs.length > 0) {
            tbody.innerHTML = data.recent_logs.slice(0, 6).map(log => `
                <tr>
                    <td><small style="color:#8da4af;">${log.timestamp}</small></td>
                    <td><strong style="color:#00ffc3;">${log.pass_number}</strong></td>
                    <td>${log.visitor_name || 'Visitor'}</td>
                    <td><span class="action-tag">${log.action}</span></td>
                    <td>${log.checkpoint_gate}</td>
                    <td>${log.biometric_match_pct ? log.biometric_match_pct + '%' : 'N/A'}</td>
                    <td>${log.alpr_plate || 'N/A'}</td>
                </tr>
            `).join('');
        }
    });
}
setInterval(refreshDashboardStream, 5000);
</script>

<?php include 'includes/footer.php'; ?>
