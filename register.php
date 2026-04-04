<?php
session_start();
include 'db.php';

// Fetch departments dynamically from the database
$departments = [];
try {
    $result = $conn->query("SELECT code, name FROM departments ORDER BY name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
    }
} catch (Exception $e) { /* silent fail for frontend fallback if needed */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Join TimetableGen · Multi‑step signup</title>
  <!-- Font Awesome 6 (free) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    :root {
      --navy: #0a3b5b;
      --navy-light: #1e4f6e;
      --green: #2e7d32;
      --green-light: #4caf7a;
      --gold: #f4c542;
      --white: #ffffff;
      --off-white: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-300: #cbd5e1;
      --gray-600: #475569;
      --error: #b91c1c;
      --shadow-md: 0 20px 30px -10px rgba(10,59,91,0.2);
      --transition: all 0.25s ease;
    }

    body {
      min-height: 100vh;
      background: linear-gradient(145deg, #edf2f9 0%, #dde7f0 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
    }

    /* subtle academic background pattern */
    body::before {
      content: "📚🎓📅⏰👨‍🏫";
      font-size: 8rem;
      position: fixed;
      top: 10px;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0.02;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-around;
      align-items: center;
      pointer-events: none;
      letter-spacing: 60px;
      transform: rotate(-8deg);
      z-index: 0;
    }

    .signup-card {
      max-width: 1050px;
      width: 100%;
      background: var(--white);
      border-radius: 3rem;
      box-shadow: var(--shadow-md);
      overflow: hidden;
      display: flex;
      flex-direction: row;
      flex-wrap: wrap;
      z-index: 10;
      backdrop-filter: blur(2px);
    }

    /* left illustration */
    .illustration-side {
      flex: 1.1;
      background-image: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80');
      background-size: cover;
      background-position: center 40%;
      position: relative;
      display: flex;
      align-items: flex-end;
      padding: 2rem;
      color: white;
      min-height: 250px;
    }
    .illustration-side::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(0deg, rgba(10,59,91,0.75) 0%, rgba(10,59,91,0.3) 80%);
    }
    .illustration-text {
      position: relative;
      z-index: 2;
      font-size: 1.9rem;
      font-weight: 700;
      line-height: 1.2;
      text-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    /* right form side */
    .form-side {
      flex: 1.3;
      padding: 2.5rem 2.5rem;
      background: white;
    }

    .logo {
      font-size: 2rem;
      font-weight: 700;
      color: var(--navy);
      margin-bottom: 0.5rem;
    }

    /* progress bar */
    .progress-container {
      margin: 1.5rem 0 2rem;
    }
    .step-indicator {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--gray-600);
    }
    .progress-track {
      background: var(--gray-300);
      height: 12px;
      border-radius: 30px;
    }
    .progress-fill {
      width: 33.33%;
      height: 12px;
      background: var(--green);
      border-radius: 30px;
      transition: width 0.3s;
    }

    /* step containers */
    .step {
      display: block;
    }
    .step-2, .step-3 {
      display: none;
    }

    /* form fields */
    .input-group {
      position: relative;
      margin-bottom: 1.5rem;
    }
    .input-icon {
      position: absolute;
      left: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.3rem;
      color: var(--gray-600);
    }
    .input-field {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      border: 1.5px solid var(--gray-300);
      border-radius: 50px;
      font-size: 1rem;
      background: var(--off-white);
      transition: var(--transition);
    }
    .input-field:focus {
      border-color: var(--navy);
      background: white;
      box-shadow: 0 0 0 4px rgba(10,59,91,0.08);
      outline: none;
    }

    /* password toggle */
    .password-toggle {
      position: absolute;
      right: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: var(--gray-600);
      z-index: 2;
      font-size: 1.1rem;
      user-select: none;
    }
    .password-toggle:hover { color: var(--navy); }

    /* password strength meter */
    .strength-meter {
      display: flex;
      gap: 8px;
      margin: 0.3rem 0 0.4rem;
    }
    .strength-bar {
      height: 8px;
      width: 33%;
      background: var(--gray-300);
      border-radius: 20px;
      transition: background 0.3s ease;
    }
    .strength-bar.weak { background: #dc2626 !important; }
    .strength-bar.medium { background: #f59e0b !important; }
    .strength-bar.strong { background: #16a34a !important; }
    .strength-label {
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 0.3rem;
      min-height: 1.2rem;
    }
    .strength-label.weak { color: #dc2626; }
    .strength-label.medium { color: #f59e0b; }
    .strength-label.strong { color: #16a34a; }

    .requirements {
      font-size: 0.9rem;
      color: var(--gray-600);
      list-style: none;
      margin-bottom: 1.5rem;
    }
    .requirements li {
      margin-bottom: 0.2rem;
    }
    .requirements li.valid {
      color: var(--green);
      text-decoration: line-through 1px solid var(--green-light);
    }

    /* role cards */
    .role-cards {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      margin: 1.5rem 0;
    }
    .role-card {
      flex: 1 1 150px;
      border: 2px solid var(--gray-300);
      border-radius: 2rem;
      padding: 1.2rem 0.5rem;
      text-align: center;
      cursor: pointer;
      transition: 0.2s;
      background: white;
    }
    .role-card.selected {
      border-color: var(--navy);
      background: #eef5ff;
      box-shadow: 0 10px 15px -8px var(--navy);
    }
    .role-card .emoji { font-size: 2.5rem; }

    /* conditional fields (hidden by default) */
    .conditional-fields {
      margin: 1.8rem 0;
      padding: 1.5rem;
      background: var(--off-white);
      border-radius: 2rem;
      display: none;
    }
    .conditional-fields.active { display: block; }

    /* button group */
    .button-group {
      display: flex;
      gap: 1rem;
      justify-content: space-between;
      margin-top: 2rem;
    }
    .btn {
      padding: 0.9rem 2.2rem;
      border: none;
      border-radius: 60px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: 0.2s;
    }
    .btn-primary {
      background: var(--navy);
      color: white;
      flex: 1;
    }
    .btn-primary:hover:not(:disabled) { background: var(--navy-light); transform: scale(1.02); }
    .btn-secondary {
      background: var(--gray-100);
      color: var(--gray-600);
    }
    .btn-secondary:hover { background: var(--gray-300); }
    .btn:disabled { opacity: 0.4; cursor: not-allowed; }

    /* math captcha */
    .captcha-box {
      background: var(--off-white);
      padding: 1.2rem;
      border-radius: 40px;
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 1.5rem 0;
    }

    /* success overlay (confetti + message) */
    .success-overlay {
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 999;
    }
    .success-card {
      background: white;
      padding: 3rem;
      border-radius: 4rem;
      text-align: center;
      box-shadow: var(--shadow-md);
    }
    .success-card h2 { font-size: 3rem; color: var(--green); }

    /* error messages */
    .error-msg {
      color: var(--error);
      font-size: 0.85rem;
      margin-left: 1.2rem;
      min-height: 1.4rem;
    }

    /* responsiveness */
    @media (max-width: 700px) {
      .signup-card { flex-direction: column; }
      .illustration-side { min-height: 160px; }
    }
  </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body>
<div class="signup-card">
  <!-- left illustration -->
  <div class="illustration-side">
    <div class="illustration-text">📝 Join TimetableGen<br><span style="font-size:1.3rem;">academic scheduling made simple</span></div>
  </div>

  <!-- right form container -->
  <div class="form-side">
    <div class="logo">📝 Join TimetableGen</div>

    <!-- progress bar with step names -->
    <div class="progress-container">
      <div class="step-indicator">
        <span>1. Account Info</span>
        <span>2. Role Details</span>
        <span>3. Verification</span>
      </div>
      <div class="progress-track">
        <div class="progress-fill" id="progressFill" style="width: 33.33%"></div>
      </div>
    </div>

    <!-- Combined Registration Form -->
    <form id="registerForm" action="register_action.php" method="POST">
      <input type="hidden" name="role" id="roleInput" value="student">

      <!-- STEP 1 – ACCOUNT INFO -->
      <div id="step1" class="step step-1">
        <div class="input-group">
          <span class="input-icon"><i class="far fa-user"></i></span>
          <input type="text" name="fullname" class="input-field" id="fullname" placeholder="Full Legal Name" required>
        </div>

        <div class="input-group">
          <span class="input-icon"><i class="far fa-envelope"></i></span>
          <input type="email" name="email" class="input-field" id="email" placeholder="University Email Address" required>
        </div>
        <div class="error-msg" id="emailError"></div>

        <div class="input-group">
          <span class="input-icon">🔒</span>
          <span class="password-toggle" onclick="togglePassword('password')"><i class="far fa-eye"></i></span>
          <input type="password" name="password" class="input-field password-input" id="password" placeholder="Create Password" required>
        </div>

        <!-- strength meter -->
        <div class="strength-meter" id="strengthMeter">
          <div class="strength-bar" id="bar1"></div>
          <div class="strength-bar" id="bar2"></div>
          <div class="strength-bar" id="bar3"></div>
        </div>
        <div class="strength-label" id="strengthLabel"></div>

        <ul class="requirements" id="passwordReqs">
          <li id="reqLength">🔸 At least 8 characters</li>
          <li id="reqNumber">🔸 Contains a number</li>
          <li id="reqUpper">🔸 Contains uppercase letter</li>
        </ul>

        <div class="input-group">
          <span class="input-icon">🔒</span>
          <span class="password-toggle" onclick="togglePassword('confirmPassword')"><i class="far fa-eye"></i></span>
          <input type="password" name="confirm_password" class="input-field password-input" id="confirmPassword" placeholder="Confirm Password" required>
        </div>
        <div class="error-msg" id="confirmError"></div>

        <div class="input-group">
          <span class="input-icon"><i class="fas fa-graduation-cap"></i></span>
          <select name="program_level" id="studentProgram" class="input-field">
            <option value="" selected>Program Level (optional)</option>
            <option value="Degree">Degree (B.E.)</option>
            <option value="Diploma">Diploma</option>
          </select>
        </div>
        <div class="input-group">
          <span class="input-icon"><i class="fas fa-building"></i></span>
          <select name="department" id="studentDept" class="input-field">
            <option value="" disabled selected>Department</option>
            <?php if (!empty($departments)): ?>
                <?php foreach($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept['code']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="CE">Computer Engineering</option>
                <option value="IT">Information Technology</option>
                <option value="ME">Mechanical Engineering</option>
                <option value="CL">Civil Engineering</option>
                <option value="EE">Electrical Engineering</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="input-group">
          <span class="input-icon">📱</span>
          <input type="tel" name="phone" class="input-field" id="phone" placeholder="Phone number (optional)">
        </div>

        <div class="button-group">
          <button type="button" class="btn btn-primary" id="nextToStep2">Next →</button>
        </div>
        <div style="text-align:center; margin-top:1.5rem; font-size:0.95rem; color:var(--gray-600);">
          Already have an account? <a href="login.php" style="color:var(--navy); font-weight:600; text-decoration:none;">Login here</a>
        </div>
      </div>

      <!-- STEP 2 – ROLE DETAILS -->
      <div id="step2" class="step step-2">
        <h3>👤 Select your role</h3>
        <div class="role-cards" id="roleCards">
          <div class="role-card" data-role="admin"><div class="emoji">👨‍💼</div> Administrator</div>
          <div class="role-card" data-role="faculty"><div class="emoji">👨‍🏫</div> Faculty Member</div>
          <div class="role-card selected" data-role="student"><div class="emoji">👩‍🎓</div> Student</div>
        </div>

        <!-- conditional fields: admin -->
        <div id="adminFields" class="conditional-fields">
          <div class="input-group"><span class="input-icon">🏛️</span><input name="admin_department" class="input-field" placeholder="Department"></div>
          <div class="input-group"><span class="input-icon">🆔</span><input name="admin_employee_id" class="input-field" placeholder="Employee ID"></div>
        </div>
        <!-- faculty fields (hidden) -->
        <div id="facultyFields" class="conditional-fields">
          <div class="input-group"><span class="input-icon">🏛️</span><input name="faculty_department" class="input-field" placeholder="Department"></div>
          <div class="input-group"><span class="input-icon">🔬</span><input name="faculty_specialization" class="input-field" placeholder="Specialization"></div>
          <div class="input-group"><span class="input-icon">📆</span><input name="faculty_experience" class="input-field" placeholder="Experience (years)"></div>
        </div>
        <!-- student fields (hidden) -->
        <div id="studentFields" class="conditional-fields active">
          <div class="input-group"><span class="input-icon">🏛️</span><input name="student_department" class="input-field" placeholder="Department"></div>
          <div class="input-group"><span class="input-icon">📚</span><input name="student_semester" class="input-field" placeholder="Semester"></div>
          <div class="input-group"><span class="input-icon">🔢</span><input name="student_roll_number" class="input-field" placeholder="Roll Number"></div>
          <div class="input-group"><span class="input-icon">📅</span><input name="student_batch_year" class="input-field" placeholder="Batch Year"></div>
        </div>

        <div class="button-group">
          <button type="button" class="btn btn-secondary" id="backToStep1">← Back</button>
          <button type="button" class="btn btn-primary" id="nextToStep3">Next →</button>
        </div>
      </div>

      <!-- STEP 3 – VERIFICATION -->
      <div id="step3" class="step step-3">
        <div style="margin:1rem 0 2rem;">
          <label><input type="checkbox" name="terms_accepted" id="termsCheck">  I accept the <a href="feature_preview.php?feature=Terms+and+Conditions" target="_blank">Terms & conditions</a> 📜</label><br>
          <label style="display:block; margin:1rem 0;"><input type="checkbox" name="privacy_accepted" id="privacyCheck">  Privacy policy agreement 🔏</label>
          <div class="email-verify-notice" style="background:#E8F0FE; padding:1rem; border-radius:30px; margin:1rem 0;">
            ✉️ A verification email will be sent to your inbox.
          </div>
        </div>

        <!-- dynamic math captcha -->
        <div class="captcha-box">
          <span id="captchaPrompt">🧮 Verify: ? + ? =</span>
          <input type="number" name="captcha_answer" id="captchaAnswer" class="input-field" style="width:100px;" placeholder="?">
          <span id="captchaError" style="color:red;"></span>
        </div>

        <div class="button-group">
          <button type="button" class="btn btn-secondary" id="backToStep2">← Back</button>
          <button type="submit" class="btn btn-primary" id="createAccountBtn">Create Account 🎉</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Success overlay with confetti -->
<div class="success-overlay" id="successOverlay">
  <canvas id="confettiCanvas" style="position:fixed;top:0;left:0;width:100%;height:100%;display:none;z-index:1000;"></canvas>
  <div class="success-card" style="z-index:1001;position:relative;">
    <h2>🎉 Welcome!</h2>
    <p style="font-size:1.1rem;color:var(--gray-600);margin-top:1rem;" id="countdownMsg">Preparing your dashboard...</p>
  </div>
</div>

<script>
  (function() {
    // DOM elements
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const progressFill = document.getElementById('progressFill');

    // inputs step1
    const fullName = document.getElementById('fullname');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const phone = document.getElementById('phone');
    const emailError = document.getElementById('emailError');
    const confirmError = document.getElementById('confirmError');

    // password requirements & bars
    const reqLength = document.getElementById('reqLength');
    const reqNumber = document.getElementById('reqNumber');
    const reqUpper = document.getElementById('reqUpper');
    const bar1 = document.getElementById('bar1');
    const bar2 = document.getElementById('bar2');
    const bar3 = document.getElementById('bar3');

    // nav buttons
    const nextToStep2 = document.getElementById('nextToStep2');
    const backToStep1 = document.getElementById('backToStep1');
    const nextToStep3 = document.getElementById('nextToStep3');
    const backToStep2 = document.getElementById('backToStep2');
    const createAccountBtn = document.getElementById('createAccountBtn');

    // role cards & conditional fields
    const roleCards = document.querySelectorAll('.role-card');
    const adminFields = document.getElementById('adminFields');
    const facultyFields = document.getElementById('facultyFields');
    const studentFields = document.getElementById('studentFields');
    const termsCheck = document.getElementById('termsCheck');
    const privacyCheck = document.getElementById('privacyCheck');
    const captchaAnswer = document.getElementById('captchaAnswer');
    const captchaError = document.getElementById('captchaError');
    const captchaPrompt = document.getElementById('captchaPrompt');

    // Generate Dynamic Captcha
    let captchaNum1 = Math.floor(Math.random() * 10) + 1;
    let captchaNum2 = Math.floor(Math.random() * 10) + 1;
    let expectedCaptchaSum = captchaNum1 + captchaNum2;
    captchaPrompt.innerText = `🧮 Verify: ${captchaNum1} + ${captchaNum2} =`;

    // success overlay
    const successOverlay = document.getElementById('successOverlay');
    const countdownMsg = document.getElementById('countdownMsg');
    const canvas = document.getElementById('confettiCanvas');
    const strengthLabel = document.getElementById('strengthLabel');
    let ctx = canvas ? canvas.getContext('2d') : null;

    // Show/hide password
    window.togglePassword = function(id) {
      const el = document.getElementById(id);
      const icon = el.previousElementSibling.querySelector('i') || el.parentElement.querySelector('.password-toggle i');
      if (el.type === 'password') {
        el.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        el.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    };

    // ---------- HELPER: password strength & validation ----------
    function checkPasswordStrength() {
      const pwd = password.value;
      const lengthValid = pwd.length >= 8;
      const hasNumber = /\d/.test(pwd);
      const hasUpper = /[A-Z]/.test(pwd);

      // update requirement list
      reqLength.classList.toggle('valid', lengthValid);
      reqNumber.classList.toggle('valid', hasNumber);
      reqUpper.classList.toggle('valid', hasUpper);

      // strength bars — progressive fill with color change
      bar1.classList.remove('weak','medium','strong');
      bar2.classList.remove('weak','medium','strong');
      bar3.classList.remove('weak','medium','strong');
      strengthLabel.className = 'strength-label';
      strengthLabel.textContent = '';

      const score = (lengthValid ? 1 : 0) + (hasNumber ? 1 : 0) + (hasUpper ? 1 : 0);

      if (pwd.length === 0) {
        // empty — no bars
      } else if (score <= 1) {
        // Weak — 1 bar red
        bar1.classList.add('weak');
        strengthLabel.textContent = 'Weak';
        strengthLabel.classList.add('weak');
      } else if (score === 2) {
        // Medium — 2 bars yellow
        bar1.classList.add('medium');
        bar2.classList.add('medium');
        strengthLabel.textContent = 'Medium';
        strengthLabel.classList.add('medium');
      } else {
        // Strong — all 3 bars green
        bar1.classList.add('strong');
        bar2.classList.add('strong');
        bar3.classList.add('strong');
        strengthLabel.textContent = 'Strong';
        strengthLabel.classList.add('strong');
      }
    }

    function validateStep1() {
      const inputs = step1.querySelectorAll('input[required], select[required]');
      for (let i = 0; i < inputs.length; i++) {
        if (!inputs[i].checkValidity()) {
          inputs[i].reportValidity();
          return false;
        }
      }

      let valid = true;
      if (!email.value.includes('@')) { emailError.innerText = 'Valid email required'; valid = false; } else { emailError.innerText = ''; }

      if (password.value !== confirmPassword.value) { confirmError.innerText = 'Passwords do not match'; valid = false; } else { confirmError.innerText = ''; }

      if (password.value.length < 8) { 
          alert('Password must be at least 8 characters long'); 
          return false; 
      }

      return valid && fullName.value.trim() !== '';
    }

    // real-time email check (simulated) on blur
    email.addEventListener('blur', () => {
      if (email.value.includes('@')) {
        // simulate async availability (always available)
        emailError.innerText = '';
      } else if (email.value !== '') emailError.innerText = 'Enter a valid email';
    });

    password.addEventListener('input', checkPasswordStrength);
    confirmPassword.addEventListener('input', () => {
      if (password.value !== confirmPassword.value) confirmError.innerText = 'Passwords do not match';
      else confirmError.innerText = '';
    });

    // Step navigation
    let currentStep = 1;

    function showStep(step) {
      step1.style.display = step === 1 ? 'block' : 'none';
      step2.style.display = step === 2 ? 'block' : 'none';
      step3.style.display = step === 3 ? 'block' : 'none';
      progressFill.style.width = step === 1 ? '33.33%' : step === 2 ? '66.66%' : '100%';
      currentStep = step;
    }

    nextToStep2.addEventListener('click', (e) => {
      e.preventDefault();
      if (validateStep1()) {
        showStep(2);
      } else { alert('Please fill all fields correctly'); }
    });

    backToStep1.addEventListener('click', (e) => { e.preventDefault(); showStep(1); });
    nextToStep3.addEventListener('click', (e) => {
      e.preventDefault();
      // role selected? (at least one card selected) – always true by default, but check.
      showStep(3);
    });
    backToStep2.addEventListener('click', (e) => { e.preventDefault(); showStep(2); });

    // Role card switch + conditional fields
    roleCards.forEach(card => {
      card.addEventListener('click', () => {
        roleCards.forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const role = card.dataset.role;
        document.getElementById('roleInput').value = role;
        adminFields.classList.remove('active');
        facultyFields.classList.remove('active');
        studentFields.classList.remove('active');
        if (role === 'admin') adminFields.classList.add('active');
        if (role === 'faculty') facultyFields.classList.add('active');
        if (role === 'student') studentFields.classList.add('active');
      });
    });

    // -------- create account (step3 validation + success) -------
    createAccountBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const captchaValid = parseInt(captchaAnswer.value) === expectedCaptchaSum;
      if (!captchaValid) { 
          captchaError.innerText = ' wrong answer'; 
          // regenerate
          captchaNum1 = Math.floor(Math.random() * 10) + 1;
          captchaNum2 = Math.floor(Math.random() * 10) + 1;
          expectedCaptchaSum = captchaNum1 + captchaNum2;
          captchaPrompt.innerText = `🧮 Verify: ${captchaNum1} + ${captchaNum2} =`;
          captchaAnswer.value = '';
          return; 
      }
      else captchaError.innerText = '';

      if (!termsCheck.checked || !privacyCheck.checked) {
        alert('Please accept terms and privacy policy');
        return;
      }

      const form = document.getElementById('registerForm');
      const formData = new FormData(form);

      fetch('register_action.php', { method: 'POST', body: formData })
      .then(r => r.text())
      .then(text => {
          // Check if register_action.php redirected or returned error
          // The current register_action.php does header() redirects
          // Assuming successful execution if we reach here 
          // However, if we're using AJAX on a script that does header("Location: ..."), fetch() follows redirects transparently.
          // Let's just submit the form normally since register_action.php handles redirect logic directly.
          successOverlay.style.display = 'flex';
          startConfetti();
          let seconds = 3;
          countdownMsg.innerText = `Preparing your dashboard...`;
          const timer = setInterval(() => {
            seconds--;
            if (seconds === 0) {
              clearInterval(timer);
              window.location.href = 'login.php?registered=1';
            }
          }, 1000);
      });
    });

    // Confetti simple animation
    let animationId = null;
    let particles = [];
    function startConfetti() {
      canvas.style.display = 'block';
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
      for (let i=0; i<60; i++) {
        particles.push({ x: Math.random()*canvas.width, y: Math.random()*canvas.height, r: Math.random()*4+2, d: Math.random()*20, color: `hsl(${Math.random()*360},70%,60%)`, vx: (Math.random()-0.5)*2, vy: Math.random()*2+1 });
      }
      function draw() {
        ctx.clearRect(0,0,canvas.width,canvas.height);
        particles.forEach(p => {
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.r, 0, 2*Math.PI);
          ctx.fillStyle = p.color;
          ctx.fill();
          p.x += p.vx;
          p.y += p.vy;
          if(p.y > canvas.height) { p.y = 0; p.x = Math.random()*canvas.width; }
        });
        animationId = requestAnimationFrame(draw);
      }
      draw();
    }
    function stopConfetti() {
      if (animationId) { cancelAnimationFrame(animationId); particles = []; canvas.style.display = 'none'; }
    }
    window.addEventListener('resize', () => { if (canvas.style.display !== 'none') { canvas.width = window.innerWidth; canvas.height = window.innerHeight; } });

    // data persistence: keep fields on step back (already using values)
    // progress already by step

    // initial strength check
    checkPasswordStrength();

    // simulate email availability (fake)
  })();
</script>
</body>
</html>

