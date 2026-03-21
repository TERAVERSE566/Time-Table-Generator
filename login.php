<?php
$error_msg = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid_password') $error_msg = 'Incorrect password. Please try again.';
    else if ($_GET['error'] === 'user_not_found') $error_msg = 'No account found with that email.';
    else $error_msg = 'An error occurred during login.';
}
$success_msg = '';
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success_msg = 'Account created successfully! Please log in.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetableGen · Secure login</title>
    <!-- Font Awesome 6 for icons (Google, Microsoft, eye, etc) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* CSS Variables – academic navy & gold */
        :root {
            --navy: #0a3b5b;
            --navy-light: #1e4f6e;
            --gold: #f4c542;
            --gold-light: #f8d775;
            --white: #ffffff;
            --off-white: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --error: #dc2626;
            --shadow-sm: 0 8px 20px rgba(0,0,0,0.06);
            --shadow-md: 0 20px 30px -10px rgba(10,59,91,0.2);
            --transition: all 0.2s ease;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #e8eef4 0%, #dbe4ed 100%);
            padding: 1.2rem;
        }

        /* main split card */
        .login-card {
            display: flex;
            max-width: 1150px;
            width: 100%;
            min-height: 700px;
            background: var(--white);
            border-radius: 3rem;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        /* LEFT INSPIRATIONAL SIDE (image + overlay) */
        .brand-side {
            flex: 1.1;
            background-image: url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80');
            background-size: cover;
            background-position: center 20%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .brand-side::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(10,59,91,0.85) 0%, rgba(10,59,91,0.6) 100%);
            z-index: 1;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            padding: 2rem;
            max-width: 500px;
            animation: fadeInUp 1s ease;
        }

        .brand-content h2 {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1rem;
            text-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .brand-content p {
            font-size: 1.3rem;
            opacity: 0.95;
            margin-bottom: 3rem;
            font-weight: 300;
        }

        .floating-icons {
            display: flex;
            gap: 2.5rem;
            justify-content: center;
            font-size: 3.2rem;
            filter: drop-shadow(0 10px 8px rgba(0,0,0,0.3));
            flex-wrap: wrap;
        }

        .floating-icons span {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            padding: 0.8rem 1.2rem;
            border-radius: 100px;
            border: 1px solid rgba(255,255,255,0.3);
            transform: rotate(2deg);
            transition: 0.2s;
        }

        .floating-icons span:nth-child(even) { transform: rotate(-3deg); }

        /* RIGHT LOGIN SIDE */
        .form-side {
            flex: 1;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3.5rem 3rem;
            position: relative;
            animation: fadeIn 0.8s ease;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 0.5rem;
        }

        .welcome {
            color: var(--gray-600);
            margin-bottom: 2rem;
            font-size: 1.2rem;
        }

        /* role tabs */
        .role-tabs {
            display: flex;
            gap: 0.6rem;
            background: var(--gray-100);
            padding: 0.5rem;
            border-radius: 60px;
            margin-bottom: 2.2rem;
        }

        .role-tab {
            flex: 1;
            padding: 0.8rem 0.3rem;
            border: none;
            background: transparent;
            border-radius: 40px;
            font-weight: 600;
            font-size: 1rem;
            color: var(--gray-600);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .role-tab.active {
            background: white;
            color: var(--navy);
            box-shadow: var(--shadow-sm);
        }

        .role-tab i { font-size: 1.2rem; }

        /* form group */
        .input-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.3rem;
            color: var(--gray-600);
            pointer-events: none;
        }

        .input-field {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1.5px solid var(--gray-300);
            border-radius: 50px;
            font-size: 1rem;
            outline: none;
            transition: var(--transition);
            background: var(--off-white);
        }

        .input-field:focus {
            border-color: var(--navy);
            background: white;
            box-shadow: 0 0 0 4px rgba(10,59,91,0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--gray-600);
            cursor: pointer;
        }

        /* inline validation */
        .error-message {
            font-size: 0.85rem;
            color: var(--error);
            margin-top: 0.4rem;
            margin-left: 1.2rem;
            min-height: 1.4rem;
        }

        /* row flex */
        .row-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.2rem 0 1.8rem;
        }

        .checkbox label {
            color: var(--gray-600);
            cursor: pointer;
        }

        .forgot-link {
            color: var(--navy);
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px dashed var(--navy);
        }

        /* login button */
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: var(--navy);
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 10px 20px -5px rgba(10,59,91,0.4);
            margin-bottom: 1.5rem;
        }

        .login-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }

        .login-btn:not(:disabled):hover {
            background: var(--navy-light);
            transform: scale(1.02);
        }

        .spinner {
            width: 1.2rem;
            height: 1.2rem;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .divider {
            text-align: center;
            color: var(--gray-600);
            position: relative;
            margin: 1.8rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-300);
        }

        .divider span {
            background: white;
            padding: 0 1rem;
        }

        .social-buttons {
            display: flex;
            gap: 1rem;
        }

        .social-btn {
            flex: 1;
            padding: 0.8rem;
            border: 1.5px solid var(--gray-300);
            background: white;
            border-radius: 50px;
            font-weight: 600;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: 0.2s;
        }

        .social-btn:hover {
            background: var(--gray-100);
            border-color: var(--navy);
        }

        .register-link {
            text-align: center;
            margin: 2rem 0 0;
            font-size: 1rem;
        }

        .register-link a {
            color: var(--navy);
            font-weight: 700;
            text-decoration: none;
        }

        /* chat bubble */
        .chat-bubble {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--navy);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            transition: 0.2s;
            z-index: 99;
        }
        .chat-bubble:hover { transform: scale(1.1); background: var(--navy-light); }

        /* modal (forgot password) */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            padding: 2.5rem;
            border-radius: 2rem;
            max-width: 400px;
            text-align: center;
            animation: fadeInUp 0.3s;
        }
        .modal-content button {
            margin-top: 1rem;
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 40px;
            font-size: 1rem;
            cursor: pointer;
        }

        /* keyframes */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* slide effect for role */
        .slide-effect {
            animation: slideShake 0.3s ease;
        }
        @keyframes slideShake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-3px); }
            75% { transform: translateX(3px); }
            100% { transform: translateX(0); }
        }

        /* responsive */
        @media (max-width: 800px) {
            .login-card { flex-direction: column; border-radius: 2rem; }
            .brand-side { min-height: 280px; }
            .brand-content h2 { font-size: 2.3rem; }
            .floating-icons { font-size: 2.5rem; gap: 1rem; }
            .form-side { padding: 2rem; }
            .chat-bubble { bottom: 1rem; right: 1rem; }
        }

        .alert-banner {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: fadeIn 0.4s ease;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body>
    <div class="login-card">
        <!-- left side: inspirational with overlay -->
        <div class="brand-side">
            <div class="brand-content">
                <h2>🎓 Welcome Back to TimetableGen</h2>
                <p>Access your academic schedule anytime, anywhere</p>
                <div class="floating-icons">
                    <span>📚</span> <span>📅</span> <span>⏰</span> <span>👨‍🎓</span>
                </div>
            </div>
        </div>

        <!-- right side: login form -->
        <div class="form-side" id="loginFormContainer">
            <div class="logo">📅 TimetableGen</div>
            <div class="welcome">Login to your account</div>

            <?php if ($error_msg): ?>
            <div class="alert-banner alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?>
            </div>
            <?php endif; ?>

            <?php if ($success_msg): ?>
            <div class="alert-banner alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?>
            </div>
            <?php endif; ?>

            <!-- role tabs with emojis -->
            <div class="role-tabs" id="roleTabs">
                <button class="role-tab active" data-role="admin"><i>👨‍💼</i> Admin</button>
                <button class="role-tab" data-role="faculty"><i>👨‍🏫</i> Faculty</button>
                <button class="role-tab" data-role="student"><i>👩‍🎓</i> Student</button>
            </div>

            <!-- login form -->
            <form id="loginForm" action="login_action.php" method="POST">
                <input type="hidden" name="role" id="roleInput" value="admin">
                <!-- email field with icon -->
                <div class="input-group">
                    <span class="input-icon">📧</span>
                    <input type="email" name="email" class="input-field" id="email" placeholder="Email address" value="" required>
                </div>
                <div class="error-message" id="emailError"></div>

                <!-- password field + show/hide -->
                <div class="input-group password-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" class="input-field" id="password" placeholder="Password" required>
                    <button type="button" class="toggle-password" id="togglePassword"><i class="far fa-eye"></i></button>
                </div>
                <div class="error-message" id="passwordError"></div>

                <!-- remember me & forgot -->
                <div class="row-flex">
                    <div class="checkbox">
                        <label><input type="checkbox" id="rememberMe" name="rememberMe"> Remember me</label>
                    </div>
                    <a href="#" class="forgot-link" id="forgotLink">Forgot password?</a>
                </div>

                <!-- login button with spinner -->
                <button type="submit" class="login-btn" id="loginBtn" disabled>
                    <span>🔓 Sign In</span>
                    <span class="spinner" style="display: none;"></span>
                </button>
            </form>

            <!-- divider -->
            <div class="divider"><span>or login with</span></div>

            <!-- social login -->
            <div class="social-buttons">
                <button class="social-btn"><i class="fab fa-google"></i> Google</button>
                <button class="social-btn"><i class="fab fa-microsoft"></i> Microsoft</button>
            </div>

            <!-- register link -->
            <div class="register-link">
                New here? <a href="register.php">Create account ✨</a>
            </div>
        </div>
    </div>

    <!-- forgot password modal -->
    <div class="modal" id="forgotModal">
        <div class="modal-content">
            <h3>📧 Reset password</h3>
            <p>A reset link will be sent to your registered email.</p>
            <button id="closeModal">Got it</button>
        </div>
    </div>

    <!-- need help chat bubble -->
    <div class="chat-bubble" title="Need help?">💬</div>

    <!-- embedded JavaScript -->
    <script>
        (function() {
            // DOM elements
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');
            const loginBtn = document.getElementById('loginBtn');
            const loginForm = document.getElementById('loginForm');
            const togglePassword = document.getElementById('togglePassword');
            const rememberMeCheck = document.getElementById('rememberMe');
            const forgotLink = document.getElementById('forgotLink');
            const modal = document.getElementById('forgotModal');
            const closeModal = document.getElementById('closeModal');
            const roleTabs = document.querySelectorAll('.role-tab');
            const formContainer = document.getElementById('loginFormContainer');
            const roleInput = document.getElementById('roleInput'); // New: hidden input for role

            // Load saved email from localStorage if "remember me" was checked
            if (localStorage.getItem('rememberedEmail')) {
                emailInput.value = localStorage.getItem('rememberedEmail');
                rememberMeCheck.checked = true;
            }

            // Validation functions
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            function validatePassword(pass) {
                return pass.length >= 8;
            }

            function updateFormValidity() {
                const emailValid = validateEmail(emailInput.value);
                const passValid = validatePassword(passwordInput.value);

                // show inline errors
                if (!emailValid && emailInput.value !== '') {
                    emailError.textContent = 'Enter a valid email address';
                } else {
                    emailError.textContent = '';
                }

                if (!passValid && passwordInput.value !== '') {
                    passwordError.textContent = 'Minimum 8 characters';
                } else {
                    passwordError.textContent = '';
                }

                // enable/disable login button
                if (emailValid && passValid) {
                    loginBtn.disabled = false;
                } else {
                    loginBtn.disabled = true;
                }
            }

            emailInput.addEventListener('input', updateFormValidity);
            passwordInput.addEventListener('input', updateFormValidity);

            // Password visibility toggle
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
            });

            // Role tabs: change placeholder and add slide effect
            roleTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // remove active class
                    roleTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // Update hidden role input
                    roleInput.value = this.dataset.role;

                    // slide animation
                    formContainer.classList.add('slide-effect');
                    setTimeout(() => formContainer.classList.remove('slide-effect'), 300);

                    // change email placeholder based on role
                    const role = this.dataset.role;
                    if (role === 'admin') emailInput.placeholder = 'admin@college.edu';
                    else if (role === 'faculty') emailInput.placeholder = 'faculty@college.edu';
                    else emailInput.placeholder = 'student@college.edu';
                });
            });

            // Forgot password modal
            forgotLink.addEventListener('click', (e) => {
                e.preventDefault();
                modal.classList.add('active');
            });
            closeModal.addEventListener('click', () => {
                modal.classList.remove('active');
            });
            window.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('active');
            });

            // Save to localStorage if remember me checked
            function saveRememberMe() {
                if (rememberMeCheck.checked && validateEmail(emailInput.value)) {
                    localStorage.setItem('rememberedEmail', emailInput.value);
                } else {
                    localStorage.removeItem('rememberedEmail');
                }
            }

            // form submission validation
            loginForm.addEventListener('submit', function(e) {
                if (loginBtn.disabled) {
                    e.preventDefault(); // Prevent submission if button is disabled due to validation
                    return;
                }

                // show spinner
                const spinner = loginBtn.querySelector('.spinner');
                const btnText = loginBtn.querySelector('span:first-child');
                spinner.style.display = 'inline-block';
                btnText.style.opacity = '0.7';
                loginBtn.disabled = true; // Disable button to prevent multiple submissions

                // Save remember me state before form submission
                saveRememberMe();

                // The form will now submit naturally to login_action.php
                // No need for preventDefault or simulated redirect here.
            });

            // validate on load
            updateFormValidity();

            // extra: if remembered email exists, revalidate
        })();
    </script>
</body>
</html>

