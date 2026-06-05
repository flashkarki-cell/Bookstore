<?php
require 'config.php';
if (isLoggedIn()) redirect('index.php');
$error = $success = '';
if (isset($_POST['register'])) {
    $name     = clean($conn, $_POST['name']);
    $email    = clean($conn, $_POST['email']);
    $phone    = clean($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            if (mysqli_query($conn, "INSERT INTO users (name, email, password, phone) VALUES ('$name','$email','$hash','$phone')")) {
                $success = "Registration successful! You can now log in.";
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}
if (isset($_POST['login'])) {
    $email    = clean($conn, $_POST['email']);
    $password = $_POST['password'];
    $result   = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $user     = mysqli_fetch_assoc($result);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php');
    } else {
        $error = "Invalid email or password.";
    }
}
$show_register = isset($_POST['register']) && $error ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BookStore — Login & Register</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 50%,#7c3aed 100%);display:flex;align-items:center;justify-content:center;font-family:'Segoe UI',Arial,sans-serif;overflow:hidden}

/* Floating book particles */
.particle{position:fixed;font-size:20px;animation:float linear infinite;opacity:.15;pointer-events:none;z-index:0}
@keyframes float{0%{transform:translateY(110vh) rotate(0deg)}100%{transform:translateY(-10vh) rotate(360deg)}}

/* Main card */
.card{position:relative;z-index:10;width:100%;max-width:440px;margin:20px;background:rgba(255,255,255,.97);border-radius:24px;box-shadow:0 25px 60px rgba(0,0,0,.3);overflow:hidden}

/* Top banner */
.card-banner{background:linear-gradient(135deg,#2563eb,#7c3aed);padding:28px 32px 20px;text-align:center;position:relative;overflow:hidden}
.card-banner::before{content:'';position:absolute;top:-40px;right:-40px;width:120px;height:120px;background:rgba(255,255,255,.1);border-radius:50%}
.card-banner::after{content:'';position:absolute;bottom:-30px;left:-30px;width:90px;height:90px;background:rgba(255,255,255,.08);border-radius:50%}
.logo-icon{font-size:40px;margin-bottom:8px;display:block;animation:bounceIn .6s ease}
.logo-text{font-size:22px;font-weight:700;color:#fff;letter-spacing:.5px}
.logo-sub{font-size:13px;color:rgba(255,255,255,.75);margin-top:3px}
@keyframes bounceIn{0%{transform:scale(0) rotate(-10deg);opacity:0}60%{transform:scale(1.15) rotate(3deg)}100%{transform:scale(1) rotate(0deg);opacity:1}}

/* Tab switcher */
.tab-switch{display:flex;background:#f1f5f9;margin:20px 24px 0;border-radius:12px;padding:4px;gap:4px}
.tab-btn{flex:1;padding:9px;border:none;background:transparent;border-radius:9px;font-size:14px;font-weight:500;color:#64748b;cursor:pointer;transition:all .25s}
.tab-btn.active{background:#fff;color:#2563eb;box-shadow:0 2px 8px rgba(0,0,0,.1)}

/* Forms wrapper - sliding panels */
.forms-wrap{overflow:hidden;position:relative}
.form-panel{padding:20px 24px 24px;transition:all .4s cubic-bezier(.4,0,.2,1)}
.form-panel.hidden{display:none}
.form-panel.slide-in{animation:slideIn .35s cubic-bezier(.4,0,.2,1)}
.form-panel.slide-in-rev{animation:slideInRev .35s cubic-bezier(.4,0,.2,1)}
@keyframes slideIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}
@keyframes slideInRev{from{opacity:0;transform:translateX(-40px)}to{opacity:1;transform:translateX(0)}}

/* Alerts */
.alert{padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:14px;display:flex;align-items:center;gap:8px;animation:fadeDown .3s ease}
@keyframes fadeDown{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}

/* Input groups */
.input-group{margin-bottom:14px;position:relative}
.input-group label{display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em}
.input-wrap{position:relative}
.input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:16px;color:#94a3b8;pointer-events:none}
.input-group input{width:100%;padding:11px 12px 11px 38px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;background:#f8fafc;transition:all .2s;outline:none}
.input-group input:focus{border-color:#2563eb;background:#fff;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.input-group input:focus ~ .input-icon{color:#2563eb}

/* Show/hide password toggle */
.eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:#94a3b8;padding:0}
.eye-btn:hover{color:#2563eb}

/* Submit button */
.submit-btn{width:100%;padding:13px;border:none;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;margin-top:6px;position:relative;overflow:hidden;transition:all .2s;letter-spacing:.3px}
.submit-btn.login-btn{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.4)}
.submit-btn.login-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,.5)}
.submit-btn.register-btn{background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;box-shadow:0 4px 14px rgba(124,58,237,.4)}
.submit-btn.register-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(124,58,237,.5)}
.submit-btn:active{transform:translateY(0)}

/* Ripple */
.ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,.4);transform:scale(0);animation:rippleAnim .6s linear;pointer-events:none}
@keyframes rippleAnim{to{transform:scale(4);opacity:0}}

/* Switch text */
.switch-text{text-align:center;font-size:13px;color:#64748b;margin-top:16px}
.switch-text a{color:#2563eb;font-weight:600;cursor:pointer;text-decoration:none}
.switch-text a:hover{text-decoration:underline}

/* Input row for 2 cols */
.input-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:400px){.input-row{grid-template-columns:1fr}}

/* Password strength */
.strength-bar{height:3px;border-radius:2px;margin-top:5px;background:#e2e8f0;overflow:hidden}
.strength-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0}
.strength-label{font-size:11px;margin-top:3px}
</style>
</head>
<body>

<!-- Floating particles -->
<script>
const emojis = ['📚','📖','📕','📗','📘','✏️','🔖'];
for(let i=0;i<12;i++){
  const p=document.createElement('div');
  p.className='particle';
  p.textContent=emojis[Math.floor(Math.random()*emojis.length)];
  p.style.cssText=`left:${Math.random()*100}%;animation-duration:${8+Math.random()*12}s;animation-delay:${-Math.random()*15}s;font-size:${16+Math.random()*20}px`;
  document.body.appendChild(p);
}
</script>

<div class="card">
  <!-- Banner -->
  <div class="card-banner">
    <span class="logo-icon">📚</span>
    <div class="logo-text">BookStore</div>
    <div class="logo-sub">Your favourite books, one click away</div>
  </div>

  <!-- Tab switcher -->
  <div class="tab-switch">
    <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">🔑 Login</button>
    <button class="tab-btn" id="tab-register" onclick="switchTab('register')">✨ Register</button>
  </div>

  <div class="forms-wrap">

    <!-- Alerts -->
    <div style="padding:0 24px">
      <?php if ($error): ?>
        <div class="alert alert-error" style="margin-top:14px">⚠️ <?= $error ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success" style="margin-top:14px">✅ <?= $success ?></div>
      <?php endif; ?>
    </div>

    <!-- LOGIN PANEL -->
    <div class="form-panel" id="panel-login">
      <form method="POST" id="login-form">
        <div class="input-group">
          <label>Email Address</label>
          <div class="input-wrap">
            <span class="input-icon">📧</span>
            <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
          </div>
        </div>
        <div class="input-group">
          <label>Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" id="login-pass" placeholder="Enter your password" required>
            <button type="button" class="eye-btn" onclick="togglePass('login-pass',this)">👁️</button>
          </div>
        </div>
        <button type="submit" name="login" class="submit-btn login-btn" onclick="ripple(event,this)">
          Login to BookStore
        </button>
      </form>
      <div class="switch-text">
        Don't have an account? <a onclick="switchTab('register')">Create one free →</a>
      </div>
    </div>

    <!-- REGISTER PANEL -->
    <div class="form-panel hidden" id="panel-register">
      <form method="POST" id="register-form">
        <div class="input-row">
          <div class="input-group">
            <label>Full Name</label>
            <div class="input-wrap">
              <span class="input-icon">👤</span>
              <input type="text" name="name" placeholder="Your name" required>
            </div>
          </div>
          <div class="input-group">
            <label>Phone</label>
            <div class="input-wrap">
              <span class="input-icon">📱</span>
              <input type="text" name="phone" placeholder="98XXXXXXXX">
            </div>
          </div>
        </div>
        <div class="input-group">
          <label>Email Address</label>
          <div class="input-wrap">
            <span class="input-icon">📧</span>
            <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
          </div>
        </div>
        <div class="input-group">
          <label>Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" id="reg-pass" placeholder="Min 6 characters" required oninput="checkStrength(this.value)">
            <button type="button" class="eye-btn" onclick="togglePass('reg-pass',this)">👁️</button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
          <div class="strength-label" id="strength-label" style="color:#94a3b8"></div>
        </div>
        <div class="input-group">
          <label>Confirm Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔐</span>
            <input type="password" name="confirm" id="reg-confirm" placeholder="Repeat password" required>
            <button type="button" class="eye-btn" onclick="togglePass('reg-confirm',this)">👁️</button>
          </div>
        </div>
        <button type="submit" name="register" class="submit-btn register-btn" onclick="ripple(event,this)">
          Create My Account
        </button>
      </form>
      <div class="switch-text">
        Already have an account? <a onclick="switchTab('login')">← Login here</a>
      </div>
    </div>

  </div>
</div>

<script>
// Switch tabs with slide animation
function switchTab(tab) {
  const loginPanel    = document.getElementById('panel-login');
  const registerPanel = document.getElementById('panel-register');
  const loginBtn      = document.getElementById('tab-login');
  const registerBtn   = document.getElementById('tab-register');

  if (tab === 'login') {
    registerPanel.classList.add('hidden');
    loginPanel.classList.remove('hidden');
    loginPanel.classList.add('slide-in-rev');
    setTimeout(() => loginPanel.classList.remove('slide-in-rev'), 400);
    loginBtn.classList.add('active');
    registerBtn.classList.remove('active');
  } else {
    loginPanel.classList.add('hidden');
    registerPanel.classList.remove('hidden');
    registerPanel.classList.add('slide-in');
    setTimeout(() => registerPanel.classList.remove('slide-in'), 400);
    registerBtn.classList.add('active');
    loginBtn.classList.remove('active');
  }
}

// Show/hide password
function togglePass(id, btn) {
  const input = document.getElementById(id);
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈';
  } else {
    input.type = 'password';
    btn.textContent = '👁️';
  }
}

// Password strength checker
function checkStrength(val) {
  const fill  = document.getElementById('strength-fill');
  const label = document.getElementById('strength-label');
  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    {w:'0%',   c:'#e2e8f0', t:''},
    {w:'25%',  c:'#ef4444', t:'Weak'},
    {w:'50%',  c:'#f97316', t:'Fair'},
    {w:'75%',  c:'#eab308', t:'Good'},
    {w:'90%',  c:'#22c55e', t:'Strong'},
    {w:'100%', c:'#16a34a', t:'Very Strong'},
  ];
  const lvl = levels[Math.min(score, 5)];
  fill.style.width      = lvl.w;
  fill.style.background = lvl.c;
  label.textContent     = lvl.t;
  label.style.color     = lvl.c;
}

// Ripple effect on button click
function ripple(e, btn) {
  const r   = document.createElement('span');
  const rect = btn.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  r.className = 'ripple';
  r.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
  btn.appendChild(r);
  setTimeout(() => r.remove(), 600);
}

// Auto switch to register tab if register error
<?php if ($show_register === 'true'): ?>
switchTab('register');
<?php endif; ?>
</script>
</body>
</html>
