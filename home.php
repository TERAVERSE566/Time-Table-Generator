<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetableGen · Academic scheduling</title>
    <!-- Font Awesome 6 (free) for icons & socials -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* CSS Variables – academic blues & whites */
        :root {
            --primary: #0a3b5b;       /* deep navy */
            --primary-light: #2b5f8a;
            --secondary: #4f9da6;      /* muted teal */
            --accent: #f4c542;         /* soft gold */
            --white: #ffffff;
            --off-white: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-600: #475569;
            --shadow-sm: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
            --shadow-md: 0 20px 30px -10px rgba(10, 59, 91, 0.15);
            --border-radius: 2rem;
        }

        body {
            background-color: var(--white);
            color: #1e293b;
            line-height: 1.5;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }

        /* container */
        .wrapper {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* navigation */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 0;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 1.9rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--primary);
        }

        .nav-links {
            display: flex;
            gap: 2.2rem;
            align-items: center;
        }

        .nav-links a {
            font-weight: 500;
            color: var(--gray-600);
            transition: 0.2s;
        }
        .nav-links a:hover { color: var(--primary); }

        .auth-buttons {
            display: flex;
            gap: 0.8rem;
        }
        .btn-outline {
            border: 1.5px solid var(--primary-light);
            color: var(--primary-light);
            padding: 0.5rem 1.4rem;
            border-radius: 40px;
            font-weight: 600;
            background: transparent;
            transition: 0.2s;
        }
        .btn-outline:hover {
            background: var(--primary-light);
            color: white;
        }
        .btn-solid {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.6rem;
            border-radius: 40px;
            font-weight: 600;
            border: none;
            transition: 0.2s;
            box-shadow: var(--shadow-sm);
        }
        .btn-solid:hover {
            background: var(--primary-light);
            transform: scale(1.02);
        }

        /* mobile menu */
        .hamburger {
            display: none;
            font-size: 2rem;
            cursor: pointer;
            color: var(--primary);
        }
        .mobile-menu {
            display: none;
            flex-direction: column;
            width: 100%;
            background: white;
            padding: 1rem 0;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
        }
        .mobile-menu a {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        /* hero */
        .hero {
            position: relative;
            border-radius: 2.5rem;
            overflow: hidden;
            margin: 2rem 0 4rem;
            background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80');
            background-size: cover;
            background-position: center 30%;
            min-height: 550px;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow-md);
        }
        .hero-overlay {
            background: linear-gradient(90deg, rgba(10,59,91,0.9) 20%, rgba(10,59,91,0.5) 90%);
            width: 100%;
            height: 100%;
            padding: 4rem 3rem;
            backdrop-filter: brightness(0.95);
            display: flex;
            align-items: center;
        }
        .hero-content {
            max-width: 650px;
            color: white;
        }
        .hero-content h1 {
            font-size: 3.3rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        .hero-content p {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 2.2rem;
        }
        .cta-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: var(--accent);
            color: var(--primary);
            padding: 0.9rem 2.2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 12px 18px -8px rgba(0,0,0,0.2);
            transition: 0.2s;
            border: none;
        }
        .btn-primary:hover { background: #f5b83d; transform: translateY(-3px); }
        .btn-outline-light {
            border: 2px solid white;
            background: transparent;
            padding: 0.9rem 2.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            transition: 0.2s;
        }
        .btn-outline-light:hover { background: rgba(255,255,255,0.15); }

        .floating-icon {
            position: absolute;
            bottom: 30px;
            right: 40px;
            font-size: 7rem;
            opacity: 0.2;
            color: white;
            transform: rotate(5deg);
        }

        /* sections */
        .section-title {
            font-size: 2.4rem;
            font-weight: 700;
            color: var(--primary);
            text-align: center;
            margin-bottom: 2rem;
        }
        .section-subtitle {
            text-align: center;
            color: var(--gray-600);
            max-width: 700px;
            margin: 0 auto 3rem;
            font-size: 1.2rem;
        }

        /* features grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }
        .feature-card {
            background: var(--off-white);
            padding: 2.2rem 1.8rem;
            border-radius: 2rem;
            transition: 0.2s;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.02);
        }
        .feature-card:hover { transform: translateY(-8px); background: white; box-shadow: var(--shadow-md); }
        .feature-card i, .feature-card .emoji { font-size: 2.8rem; margin-bottom: 1rem; color: var(--primary); }

        /* steps */
        .steps-container {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            margin: 3rem 0 5rem;
        }
        .step {
            background: white;
            border-radius: 3rem;
            padding: 2.5rem 2rem;
            text-align: center;
            flex: 1 1 200px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }
        .step .emoji { font-size: 3.5rem; }

        /* testimonials slider */
        .testimonial-slider {
            background: var(--off-white);
            padding: 3rem 1rem;
            border-radius: 3rem;
            margin: 4rem 0;
            position: relative;
        }
        .slider-container {
            display: flex;
            transition: transform 0.4s ease;
        }
        .testimonial-card {
            min-width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 1.5rem 2rem;
        }
        .testimonial-card img {
            width: 100px;
            height: 100px;
            border-radius: 100px;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1rem;
        }
        .stars { color: #f4b740; font-size: 1.3rem; letter-spacing: 4px; margin: 0.5rem 0; }
        .slider-dots {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            margin-top: 2rem;
        }
        .dot {
            width: 12px; height: 12px;
            background: #cbd5e1;
            border-radius: 20px;
            cursor: pointer;
            transition: 0.2s;
        }
        .dot.active { background: var(--primary); width: 32px; }

        /* stats counter */
        .stats-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            background: var(--primary);
            color: white;
            padding: 3.5rem 2rem;
            border-radius: 4rem;
            margin: 4rem 0;
        }
        .stat-item {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-item span { font-size: 1.1rem; font-weight: 400; display: block; opacity: 0.8; }

        /* CTA */
        .cta-section {
            background: linear-gradient(rgba(10,59,91,0.85), rgba(10,59,91,0.85)), url('https://images.unsplash.com/photo-1506784365847-bbad939e9335?ixlib=rb-4.0.3&auto=format&fit=crop&w=1168&q=80');
            background-size: cover;
            background-position: center;
            border-radius: 3rem;
            padding: 5rem 2rem;
            text-align: center;
            color: white;
            margin: 5rem 0;
        }
        .cta-section h2 { font-size: 3rem; margin-bottom: 2rem; }
        .cta-button {
            background: var(--accent);
            border: none;
            padding: 1rem 3rem;
            border-radius: 60px;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            transition: 0.2s;
        }
        .cta-button:hover { background: #f0b832; transform: scale(1.05); cursor: pointer; }

        .footer {
            background: var(--off-white);
            padding: 4rem 2rem 2rem;
            border-top: 1px solid var(--gray-200);
            margin-top: 4rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .footer-col h4 {
            color: var(--primary);
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
        }
        .footer-col a {
            display: block;
            color: var(--gray-600);
            margin-bottom: 0.8rem;
            transition: 0.2s;
        }
        .footer-col a:hover {
            color: var(--primary);
            padding-left: 5px;
        }
        .footer-bottom {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
        }
        .social i { font-size: 1.6rem; margin: 0 0.5rem; color: var(--primary); transition: 0.2s; cursor: pointer; }
        .social i:hover { transform: translateY(-3px); }

        /* responsiveness */
        @media (max-width: 768px) {
            .nav-links, .auth-buttons { display: none; }
            .hamburger { display: block; }
            .hero-content h1 { font-size: 2.5rem; }
            .floating-icon { font-size: 5rem; bottom: 15px; right: 20px; }
            .stats-grid { gap: 2rem; }
        }
        @media (min-width: 769px) {
            .mobile-menu { display: none !important; }
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body>
<div class="wrapper">

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">📚 TimetableGen</div>
        <div class="nav-links">
            <a href="#">Home</a>
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                $dashUrl = 'studentD.php';
                if (isset($_SESSION['user_role'])) {
                    if ($_SESSION['user_role'] === 'admin') $dashUrl = 'admin.php';
                    else if ($_SESSION['user_role'] === 'faculty') $dashUrl = 'facultyD.php';
                }
                ?>
                <button class="btn-outline" onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</button>
                <button class="btn-solid" onclick="window.location.href='<?= $dashUrl ?>'">Dashboard ➡</button>
            <?php else: ?>
                <button class="btn-outline" onclick="window.location.href='login.php'">Log in</button>
                <button class="btn-solid" onclick="window.location.href='register.php'">Register</button>
            <?php endif; ?>
        </div>
        <div class="hamburger" id="hamburgerBtn">☰</div>
    </nav>
    <!-- mobile menu (hidden by default) -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="#">Home</a>
        <a href="#features">Features</a>
        <a href="#about">About</a>
        <a href="#contact">Contact</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="<?= $dashUrl ?>">Dashboard ➡</a>
        <?php else: ?>
            <a href="login.php">Log in</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-overlay">
            <div class="hero-content">
                <h1>📅 Academic Timetable Generator</h1>
                <p>Simplify scheduling for your entire institution with our intelligent timetable management system</p>
                <div class="cta-group">
                    <button class="btn-primary" onclick="window.location.href='register.php'">Get Started 🚀</button>
                    <button class="btn-outline-light" onclick="document.getElementById('features').scrollIntoView({ behavior: 'smooth' });">Watch Demo ▶️</button>
                </div>
            </div>
        </div>
        <div class="floating-icon">⏰</div>
    </section>

    <!-- Features (3-col grid) -->
    <section id="features">
        <h2 class="section-title">⚡ Everything you need</h2>
        <p class="section-subtitle">Smart, intuitive, and built for modern academia.</p>
        <div class="features-grid">
            <div class="feature-card"><span class="emoji">⚡</span> <h3>Smart Generation</h3><p>AI-powered algorithm that creates optimal timetables without conflicts.</p></div>
            <div class="feature-card"><span class="emoji">👥</span> <h3>Role-Based Access</h3><p>Dedicated dashboards for Admin, Faculty, and Students.</p></div>
            <div class="feature-card"><span class="emoji">🔄</span> <h3>Real-time Updates</h3><p>Instant notifications when schedules change.</p></div>
            <div class="feature-card"><span class="emoji">📊</span> <h3>Analytics Dashboard</h3><p>Visual insights into resource utilization.</p></div>
            <div class="feature-card"><span class="emoji">🔔</span> <h3>Conflict Detection</h3><p>Automatic clash resolution.</p></div>
            <div class="feature-card"><span class="emoji">📱</span> <h3>Mobile Friendly</h3><p>Access from any device.</p></div>
        </div>
    </section>

    <!-- How It Works -->
    <h2 class="section-title">⚙️ Simple 3‑step workflow</h2>
    <div class="steps-container">
        <div class="step"><span class="emoji">📝</span><h3>Input Data</h3><p>Add courses, faculty, rooms</p></div>
        <div class="step"><span class="emoji">⚙️</span><h3>Generate</h3><p>Click to create optimized timetable</p></div>
        <div class="step"><span class="emoji">📋</span><h3>View & Share</h3><p>Access schedules instantly</p></div>
    </div>

    <!-- Testimonials Slider (cards) -->
    <div class="testimonial-slider" id="testimonials">
        <div class="slider-container" id="slider">
            <div class="testimonial-card">
                <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="profile">
                <p>“TimetableGen saved us dozens of hours. No more spreadsheets!”</p>
                <div class="stars">★★★★★</div>
                <h4>— Dr. Sarah Chen, Dept. Chair</h4>
            </div>
            <div class="testimonial-card">
                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="profile">
                <p>“Our students love the real‑time updates. Absolutely seamless.”</p>
                <div class="stars">★★★★★</div>
                <h4>— Mark Rivera, Registrar</h4>
            </div>
            <div class="testimonial-card">
                <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="profile">
                <p>“The conflict detection alone is worth it. Highly recommend.”</p>
                <div class="stars">★★★★★</div>
                <h4>— Prof. Lisa Mbeki</h4>
            </div>
        </div>
        <div class="slider-dots" id="sliderDots">
            <span class="dot active" data-index="0"></span>
            <span class="dot" data-index="1"></span>
            <span class="dot" data-index="2"></span>
        </div>
    </div>

    <!-- Statistics Counter (animated) -->
    <div class="stats-grid" id="stats">
        <div class="stat-item"><span class="stat-num" data-target="50">0</span>+ <span>🏛️ Colleges</span></div>
        <div class="stat-item"><span class="stat-num" data-target="1000">0</span>+ <span>👨‍🏫 Faculty</span></div>
        <div class="stat-item"><span class="stat-num" data-target="50000">0</span>+ <span>👩‍🎓 Students</span></div>
        <div class="stat-item"><span class="stat-num" data-target="100000">0</span>+ <span>📚 Classes</span></div>
    </div>

    <!-- Call to Action -->
    <div class="cta-section">
        <h2>Ready to simplify your academic scheduling?</h2>
        <button class="cta-button" onclick="window.location.href='register.php'">Start Free Trial ✨</button>
    </div>

    <!-- Comprehensive Footer -->
    <footer class="footer" id="contact">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>📚 TimetableGen</h4>
                <p style="color:var(--gray-600); margin-bottom:1rem;">Automating academic scheduling with intelligent conflict resolution.</p>
                <div>✉️ hello@timetablegen.edu</div>
                <div>📞 (555) 234-7890</div>
            </div>
            
            <div class="footer-col">
                <h4>Dashboards</h4>
                <a href="admin.php">Admin Panel</a>
                <a href="facultyD.php">Faculty Portal</a>
                <a href="studentD.php">Student Portal</a>
                <a href="profile.php">My Profile</a>
            </div>

            <div class="footer-col">
                <h4>Management (Admin)</h4>
                <a href="departmentM.php">Departments</a>
                <a href="course.php">Courses View</a>
                <a href="roomM.php">Rooms Setup</a>
                <a href="FacultyM.php">Faculty Directory</a>
            </div>

            <div class="footer-col">
                <h4>Tools</h4>
                <a href="generator.php">Timetable Generator</a>
                <a href="table_view.php">Schedule Viewer</a>
                <a href="analysis.php">Analytics Dashboard</a>
                <a href="help.php">Help & Docs</a>
            </div>
        </div>

        <div class="footer-bottom">
            <div>&copy; <?= date("Y") ?> TimetableGen – All rights reserved</div>
            <div class="social">
                <i class="fab fa-twitter"></i>
                <i class="fab fa-linkedin"></i>
                <i class="fab fa-facebook"></i>
                <i class="fab fa-instagram"></i>
            </div>
        </div>
    </footer>
</div>

<!-- embedded JavaScript -->
<script>
    (function() {
        // Smooth scrolling for anchor links (Home, Features, About, Contact)
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Mobile hamburger menu
        const hamburger = document.getElementById('hamburgerBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        if (hamburger) {
            hamburger.addEventListener('click', () => {
                if (mobileMenu.style.display === 'flex') {
                    mobileMenu.style.display = 'none';
                } else {
                    mobileMenu.style.display = 'flex';
                }
            });
        }

        // Testimonial slider with auto-rotate
        const slider = document.getElementById('slider');
        const dots = document.querySelectorAll('.dot');
        let currentIndex = 0;
        const totalSlides = document.querySelectorAll('.testimonial-card').length;
        const autoRotateInterval = 5000; // 5 seconds

        function showSlide(index) {
            if (!slider) return;
            if (index >= totalSlides) index = 0;
            if (index < 0) index = totalSlides - 1;
            slider.style.transform = `translateX(-${index * 100}%)`;
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
            currentIndex = index;
        }

        dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => showSlide(idx));
        });

        // auto rotate
        setInterval(() => {
            showSlide(currentIndex + 1);
        }, autoRotateInterval);

        // Statistics Counter on scroll (animate numbers)
        const statNumbers = document.querySelectorAll('.stat-num');
        let counted = false;

        function startCounting() {
            if (counted) return;
            const statsSection = document.getElementById('stats');
            const rect = statsSection.getBoundingClientRect();
            const isVisible = rect.top <= window.innerHeight - 100 && rect.bottom >= 0;
            if (isVisible) {
                counted = true;
                statNumbers.forEach(numSpan => {
                    const target = parseInt(numSpan.getAttribute('data-target'), 10);
                    let current = 0;
                    const increment = target / 80; // smooth increment
                    const updateCounter = () => {
                        current += increment;
                        if (current < target) {
                            numSpan.innerText = Math.floor(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            numSpan.innerText = target;
                        }
                    };
                    updateCounter();
                });
            }
        }

        window.addEventListener('scroll', startCounting);
        window.addEventListener('load', startCounting); // in case already visible

        // set a fallback if stats never counted (e.g., initial load)
        setTimeout(startCounting, 500);

        // initial active slide
        showSlide(0);
    })();
</script>

<!-- ensure randomuser images exist and fallback for any missing (all good) -->
</body>
</html>

