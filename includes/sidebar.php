
<style>
  .main-content {
    margin-left: 250px;
    margin-top: 62px;
    padding: 24px 34px 60px 34px;
    min-height: calc(100vh - 62px);
    box-sizing: border-box;
    overflow-x: hidden;
    transition: margin-left 0.3s ease;
  }
  @media (max-width: 900px) {
    .main-content {
      margin-left: 70px;
      padding: 16px 14px 40px 14px;
    }
  }
  .sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 250px;
    height: 100vh;
    background: rgba(14, 23, 31, 0.94);
    backdrop-filter: blur(14px);
    box-shadow: 2px 0 18px rgba(0,255,195,0.3);
    color: #00ffc3;
    display: flex; flex-direction: column; z-index: 1100;
    user-select: none; font-family: 'Poppins',sans-serif;
    border-right: 1px solid rgba(0, 255, 195, 0.2);
  }
  .sidebar-header {
    padding: 32px 20px 18px 20px;
    text-align: center;
    border-bottom: 2px solid rgba(0,255,195,0.25);
    display: flex; flex-direction: column; align-items: center;
  }
  .sidebar-logo-wrap {
    width: 86px; height: 86px;
    margin: 0 auto 8px auto;
    background: #2c5364cc;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 16px 2px #24ffe370;
    border: 2.2px solid #24ffe386;
    transition: box-shadow 0.15s;
    cursor: pointer;
  }
  .sidebar-logo-wrap:hover { box-shadow: 0 0 32px 8px #00ffc399;}
  .sidebar-logo {
    display: block;
    width: 74px; height: 74px;
    border-radius: 16px;
    object-fit: cover;
    background-color: #d7ffff;
    border: 1.5px solid #00ffc3;
    transition: box-shadow 0.18s;
  }
  .sidebar-header h3 {
    margin: 10px 0 0 0;
    font-size: 1.35rem;
    font-weight: 900;
    letter-spacing: 0.17em;
    color: #00ffcc;
  }
  .sidebar-header small {
    font-weight:600; font-size:0.97rem;
    color: #00ffaacc; letter-spacing:0.09em;
  }
  ul.nav {
    flex-grow: 1;
    padding: 14px 0;
    margin: 0;
    list-style: none;
  }
  ul.nav li { margin: 6px 0; }
  ul.nav li.mt-auto { margin-top:auto; }
  ul.nav a.nav-link {
    display: flex; align-items: center;
    color: #00ffc3dd;
    padding: 12px 24px;
    font-weight: 700; font-size:1.07rem;
    text-decoration: none;
    border-left: 4px solid transparent;
    transition:
      background-color 0.26s,
      color 0.25s, border-color 0.3s;
    position: relative;
  }
  ul.nav a.nav-link i {
    margin-right: 14px; font-size: 1.23rem;
    min-width: 26px; text-align: center;
  }
  ul.nav a.nav-link:hover,
  ul.nav a.nav-link:focus {
    color: #00ffaa;
    background: rgba(0,255,195,0.14);
    border-left-color: #00ffaa;
    outline: none;
  }
  ul.nav a.nav-link.active {
    background: #00ffaa32;
    color: #00ffaa;
    border-left-color: #00ffaa;
  }
  ul.nav a.nav-link[title]:hover::after,
  ul.nav a.nav-link[title]:focus::after {
    content: attr(title);
    position: absolute;
    top: 50%; left: 110%;
    transform: translateY(-50%);
    background: #00ffc3cc;
    color: #00332a;
    padding: 5px 10px;
    border-radius: 12px;
    white-space: nowrap;
    font-size: 0.86rem;
    box-shadow: 0 0 10px #00ffc3cc;
    z-index: 99;
    pointer-events: none;
  }
  .sidebar-footer {
    padding: 14px 22px;
    font-size: 0.89rem;
    text-align: center;
    color: #00ffccb1;
    border-top: 1px solid rgba(0,255,195,0.25);
    font-weight: 600; letter-spacing: 0.08em;
    user-select: none;
  }
  @media (max-width: 900px) {
    .sidebar {
      width: 70px;
    }
    .sidebar-header h3,
    .sidebar-header small,
    ul.nav a.nav-link span { display: none; }
    ul.nav a.nav-link {
      justify-content: center;
      padding: 12px 0;
    }
    ul.nav a.nav-link i {
      margin-right:0; font-size:1.4rem; min-width: auto;
    }
    .sidebar-footer { font-size: 0; padding:10px 0; }
    .sidebar-logo-wrap { width:42px;height:42px;}
    .sidebar-logo { width:36px; height:36px; }
  }
</style>

<aside class="sidebar" role="navigation" aria-label="Main admin panel navigation menu">
  <div class="sidebar-header" tabindex="0" aria-label="GatePass Pro admin panel branding">
    <span class="sidebar-logo-wrap" id="sidebarLogoWrap" title="GatePass Pro Logo">
      <img src="logo.jpg" alt="GatePass Pro Logo" class="sidebar-logo" id="sidebarLogo">
    </span>
    <h3>GPMS</h3>
    <small>Admin Panel</small>
  </div>
  <ul class="nav flex-column" role="menu" aria-orientation="vertical">
    <li>
      <a class="nav-link <?php echo ($active_page === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php" role="menuitem" title="Dashboard overview">
        <i class="fas fa-tachometer-alt" aria-hidden="true"></i><span>Dashboard</span>
      </a>
    </li>
    <li>
      <a class="nav-link <?php echo ($active_page === 'checkpoint-terminal.php') ? 'active' : ''; ?>" href="checkpoint-terminal.php" role="menuitem" title="Edge AI Checkpoint Terminal">
        <i class="fas fa-shield-alt" aria-hidden="true"></i><span>Checkpoint Alpha</span>
      </a>
    </li>
    <li>
      <a class="nav-link <?php echo ($active_page === 'gatekeeper-sim.php') ? 'active' : ''; ?>" href="gatekeeper-sim.php" role="menuitem" title="Gatekeeper AI Simulation Game">
        <i class="fas fa-gamepad" aria-hidden="true"></i><span>AI Simulator</span>
      </a>
    </li>
    <li>
      <a class="nav-link <?php echo ($active_page === 'self-register.php') ? 'active' : ''; ?>" href="self-register.php" target="_blank" role="menuitem" title="Self-Service Visitor Portal">
        <i class="fas fa-id-badge" aria-hidden="true"></i><span>Visitor Portal</span>
      </a>
    </li>
    <li>
      <a class="nav-link <?php echo ($active_page === 'add-pass.php') ? 'active' : ''; ?>" href="add-pass.php" role="menuitem" title="Add a new gate pass">
        <i class="fas fa-plus-circle" aria-hidden="true"></i><span>Issue Pass</span>
      </a>
    </li>
    <li>
      <a class="nav-link <?php echo ($active_page === 'manage-passes.php') ? 'active' : ''; ?>" href="manage-passes.php" role="menuitem" title="Manage existing passes">
        <i class="fas fa-tasks" aria-hidden="true"></i><span>PassVault</span>
      </a>
    </li>
    <li class="mt-auto">
      <a class="nav-link" href="logout.php" role="menuitem" title="Log out">
        <i class="fas fa-sign-out-alt" aria-hidden="true"></i><span>Logout</span>
      </a>
    </li>
  </ul>
  <div class="sidebar-footer" aria-hidden="true">
      GatePass Pro &copy; <?php echo date('Y'); ?>
  </div>
</aside>

<div class="main-content" tabindex="-1">

<script>
    // Optional: logo Easter egg (add sparkles and secret on 6 fast clicks)
    (function() {
        const logo = document.getElementById('sidebarLogoWrap');
        let clicks = 0, timeout;
        logo.addEventListener('click', function() {
            clicks++;
            if (clicks === 6) {
                activateEgg(this);
                clicks = 0;
                clearTimeout(timeout);
            }
            clearTimeout(timeout);
            timeout = setTimeout(()=>clicks=0, 1300);
        });
        function activateEgg(target) {
            const rect = target.getBoundingClientRect();
            const colors = ['#00ffc3','#00ffaa','#24ffe3','#fff','#00ecff','#ffe97e'];
            for (let i = 0; i < 21; i++) {
                let s = document.createElement('div');
                s.className = 'sparkle';
                s.style.left = (window.scrollX + rect.left + Math.random()*rect.width) + 'px';
                s.style.top = (window.scrollY + rect.top + Math.random()*rect.height) + 'px';
                s.style.background = colors[Math.floor(Math.random()*colors.length)];
                document.body.appendChild(s);
                setTimeout(()=>s.remove(), 1300);
            }
            setTimeout(()=>{alert("✨ Welcome to the GatePass Pro secret zone! ✨");}, 500);
        }
    })();
</script>
