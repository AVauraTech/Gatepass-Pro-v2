<?php
// includes/header.php - Global Unified Security Console Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GatePass Pro | Enterprise Access & AI Security Platform</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Cyber & Tech Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;800;900&family=Poppins:wght@300;400;500;600;700;800;900&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-neon: #00ffc3;
            --secondary-neon: #24ffe3;
            --accent-cyan: #00e5ff;
            --alert-red: #ff3366;
            --amber-warn: #ffaa00;
            --bg-dark: #0b151c;
            --panel-bg: rgba(18, 28, 38, 0.85);
            --border-glow: rgba(0, 255, 195, 0.28);
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            background: radial-gradient(circle at 10% 20%, #0d1e27 0%, #081117 90%);
            color: #d7ffff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #09131a;
        }
        ::-webkit-scrollbar-thumb {
            background: #17424d;
            border-radius: 4px;
            border: 1px solid rgba(0, 255, 195, 0.3);
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #00ffc3;
        }

        /* Top Executive Security Bar */
        .navbar-custom {
            position: fixed;
            left: 250px;
            right: 0;
            top: 0;
            height: 62px;
            z-index: 1050;
            background: rgba(13, 22, 30, 0.94);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0, 255, 195, 0.22);
            box-shadow: 0 4px 24px rgba(0,0,0,0.55);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: left 0.3s ease;
        }
        .navbar-brand-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .navbar-title {
            color: #24ffe3;
            font-family: 'Orbitron', monospace;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: 0.12em;
            display: flex;
            align-items: center;
            gap: 10px;
            text-shadow: 0 0 14px rgba(36, 255, 227, 0.4);
        }
        .threat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .threat-pill.threat-green {
            background: rgba(0, 255, 195, 0.12);
            color: #00ffc3;
            border: 1px solid rgba(0, 255, 195, 0.4);
        }
        .threat-pill.threat-amber {
            background: rgba(255, 170, 0, 0.15);
            color: #ffaa00;
            border: 1px solid rgba(255, 170, 0, 0.45);
        }
        .threat-pill.threat-red {
            background: rgba(255, 51, 102, 0.18);
            color: #ff3366;
            border: 1px solid rgba(255, 51, 102, 0.5);
            animation: threatBlink 1.4s infinite;
        }
        @keyframes threatBlink {
            0%, 100% { opacity: 1; box-shadow: 0 0 10px rgba(255,51,102,0.6); }
            50% { opacity: 0.6; box-shadow: none; }
        }
        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            display: inline-block;
        }
        .edge-status-pill {
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.74rem;
            color: #7ef8da;
            background: rgba(0, 229, 255, 0.08);
            border: 1px solid rgba(0, 229, 255, 0.25);
            padding: 3px 9px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .navbar-user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .clock-display {
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.88rem;
            color: #8beee4;
            background: rgba(0,0,0,0.3);
            padding: 4px 10px;
            border-radius: 8px;
            border: 1px solid rgba(0, 255, 195, 0.15);
        }
        .admin-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #e0ffff;
            background: rgba(255, 255, 255, 0.06);
            padding: 5px 12px;
            border-radius: 20px;
            border: 1px solid rgba(0, 255, 195, 0.2);
        }
        .admin-badge i {
            color: #00ffc3;
        }
        .btn-top-logout {
            background: rgba(255, 51, 102, 0.12);
            color: #ff4778;
            border: 1px solid rgba(255, 51, 102, 0.35);
            padding: 5px 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-top-logout:hover {
            background: #ff3366;
            color: #fff;
            box-shadow: 0 0 14px rgba(255, 51, 102, 0.5);
        }
        @media (max-width: 900px) {
            .navbar-custom { left: 70px; padding: 0 14px; }
            .edge-status-pill, .clock-display { display: none; }
            .navbar-title { font-size: 0.88rem; }
        }
        @media (max-width: 580px) {
            .admin-badge span { display: none; }
        }
    </style>
</head>
<body>
    <div class="navbar-custom">
        <div class="navbar-brand-section">
            <span class="navbar-title">
                <i class="fa-solid fa-shield-halved"></i> GatePass Pro
            </span>
            <span class="threat-pill threat-<?php echo strtolower($threat_level ?? 'green'); ?>">
                <span class="status-dot"></span> THREAT: <?php echo htmlspecialchars($threat_level ?? 'GREEN'); ?>
            </span>
            <span class="edge-status-pill">
                <i class="fa-solid fa-satellite-dish"></i> EDGE: ONLINE
            </span>
        </div>
        <div class="navbar-user-section">
            <span class="clock-display" id="globalClock">--:--:--</span>
            <div class="admin-badge">
                <i class="fa-solid fa-user-shield"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_id'] ?? 'COMMANDER'); ?></span>
            </div>
            <form action="logout.php" method="post" style="display:inline; margin:0;">
                <button type="submit" class="btn-top-logout" title="Sign out of console">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </button>
            </form>
        </div>
    </div>
    <script>
    function updateGlobalClock() {
        const el = document.getElementById('globalClock');
        if (el) {
            const now = new Date();
            el.innerText = now.toTimeString().split(' ')[0] + ' LOC';
        }
    }
    setInterval(updateGlobalClock, 1000);
    updateGlobalClock();
    </script>
