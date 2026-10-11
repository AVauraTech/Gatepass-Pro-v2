<?php
// index.php - Admin Login Page
session_start();
include('db.php');

$error = '';
$success = '';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("location: dashboard.php");
    exit;
}

// Handle flash success message
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = md5($_POST['password']); // For production, consider password_hash & password_verify

    $sql = "SELECT id FROM admin WHERE username = '$username' AND password = '$password' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['success_msg'] = "Welcome back, {$username}!";
        header("location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>🚀 GatePass Pro | Admin Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap');

    /* Reset some basics */
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
      min-height: 100vh;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #f4f7f6;
      user-select: none;
    }

    .login-container {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(12px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.7);
      border-radius: 20px;
      width: 100%;
      max-width: 420px;
      padding: 45px 40px;
      text-align: center;
      position: relative;
      transition: transform 0.4s ease, box-shadow 0.4s ease;
    }

    .login-container:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.9);
    }

    h1.logo {
      font-size: 3rem;
      margin-bottom: 0.15em;
      cursor: pointer;
      user-select: text;
      color: #00ffcc;
      font-weight: 900;
      letter-spacing: 0.15em;
      transition: color 0.3s ease;
    }
    h1.logo:hover {
      color: #00ffaa;
    }

    p.subtitle {
      font-weight: 400;
      color: #b0d9d9;
      letter-spacing: 0.05em;
      margin-bottom: 1.8rem;
      font-size: 1.1rem;
    }

    form {
      margin-top: 1.2rem;
    }

    .form-floating > input,
    .form-floating > label {
      border-radius: 8px;
      background: rgba(255,255,255,0.17);
    }

    .form-floating > input {
      border: none;
      color: #e0f7f7;
      font-weight: 500;
    }

    .form-floating > input:focus {
      background: rgba(255,255,255,0.3);
      color: #fff;
      border: 1.5px solid #00ffcc;
      outline: 0;
      box-shadow: 0 0 8px #00ffccaa;
      font-weight: 600;
    }

    .form-floating > label {
      color: #a2d6d6;
      padding-left: 0.8rem;
      user-select: none;
    }

    button[type="submit"] {
      background: #00ffc3;
      color: #053027;
      font-weight: 700;
      font-size: 1.1rem;
      padding: 12px 0;
      border-radius: 12px;
      border: none;
      box-shadow: 0 4px 18px #00ffc380;
      transition: background-color 0.25s ease;
      user-select: none;
      cursor: pointer;
      width: 100%;
    }

    button[type="submit"]:hover,
    button[type="submit"]:focus {
      background-color: #00bfa1;
      box-shadow: 0 6px 22px #00bfa180;
      outline: none;
    }

    .alert {
      margin-top: 20px;
      font-weight: 600;
      border-radius: 10px;
      font-size: 0.95rem;
    }

    /* Easter Egg: Magical sparkles animation */
    .sparkles {
      pointer-events: none;
      position: absolute;
      width: 5px;
      height: 5px;
      background: #00ffcc;
      border-radius: 50%;
      box-shadow:
        0 0 6px 2px #00ffc3,
        0 0 12px 4px #00ffc3aa;
      animation: sparkle 1.2s infinite alternate;
      opacity: 0;
    }

    @keyframes sparkle {
      0% {transform: scale(1); opacity: 1;}
      100% {transform: scale(1.7) translateY(-10px); opacity: 0;}
    }

    /* Responsive adjustments */
    @media (max-width: 480px) {
      .login-container {
        padding: 35px 28px;
        max-width: 100%;
        margin: 12px;
      }
      h1.logo {
        font-size: 2.4rem;
      }
    }

  </style>
</head>
<body>
  <main class="login-container" role="main" aria-labelledby="login-title">
    <img src="logo.jpg" alt="GatePass Pro Logo" style="width:80px;display:block;margin:0 auto 12px auto;">

    <h1 id="login-title" class="logo" tabindex="0" title="Click me 5 times!">GPMS</h1>
    <p class="subtitle">Gate Pass Management System</p>
    <h2 class="mb-4 fs-4" style="color:#00ffcc; font-weight:600;">Admin Portal</h2>

    <?php if ($success): ?>
      <div class="alert alert-success" role="alert" aria-live="polite" aria-atomic="true">
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert" aria-live="assertive" aria-atomic="true">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form action="" method="post" novalidate>
      <div class="form-floating mb-3">
        <input type="text" 
               class="form-control" 
               id="username" 
               name="username" 
               placeholder="Username" 
               required 
               autocomplete="username" 
               aria-describedby="usernameHelp"
               autofocus>
        <label for="username">Username</label>
      </div>

      <div class="form-floating mb-3">
        <input type="password" 
               class="form-control" 
               id="password" 
               name="password" 
               placeholder="Password" 
               required 
               autocomplete="current-password"
               aria-describedby="passwordHelp"
               >
        <label for="password">Password</label>
      </div>

      <button type="submit" aria-label="Login to Admin Portal">Login</button>
    </form>
  </main>

  <script>
    // Easter egg logic: Clicking the logo 5 times triggers sparkle effect & alert
    (function() {
      const logo = document.querySelector('h1.logo');
      let clickCount = 0;
      let timeout;

      logo.addEventListener('click', () => {
        clickCount++;

        if (clickCount === 5) {
          triggerEasterEgg();
          clickCount = 0;
          clearTimeout(timeout);
          return;
        }

        // Reset counter if not clicked 5 times in 2 sec
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          clickCount = 0;
        }, 2000);
      });

      function triggerEasterEgg() {
        // Create sparkles
        for (let i = 0; i < 25; i++) {
          const sparkle = document.createElement('div');
          sparkle.classList.add('sparkles');
          sparkle.style.left = (Math.random() * logo.clientWidth) + 'px';
          sparkle.style.top = (Math.random() * logo.clientHeight) + 'px';
          logo.appendChild(sparkle);

          sparkle.style.opacity = 1;
          sparkle.style.animationDelay = (Math.random() * 0.5) + 's';

          setTimeout(() => sparkle.remove(), 1500);
        }
        // Friendly hidden message
        alert("🎉 You found the secret! Welcome to the GatePass Pro Easter Egg! 🎉");
      }
    })();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
