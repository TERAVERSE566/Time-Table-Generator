<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center · TimetableGen</title>
    <!-- Font Awesome 6 & Google Fonts -->
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
            --gold: #f4c542;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-md: 0 12px 30px -8px rgba(10,59,91,0.15);
            --border-radius: 2rem;
        }

        body {
            background: var(--bg-light);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* header */
        .header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .header h1 {
            font-size: 3.5rem;
            color: var(--navy);
        }
        .header p {
            color: var(--gray-600);
            font-size: 1.2rem;
        }
        .search-box {
            max-width: 600px;
            margin: 2rem auto 1rem;
            display: flex;
            background: white;
            border-radius: 60px;
            padding: 0.3rem 0.3rem 0.3rem 2rem;
            box-shadow: var(--shadow-md);
        }
        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 1.1rem;
            background: transparent;
        }
        .search-btn {
            background: var(--navy);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
        }
        .popular-searches {
            color: var(--gray-600);
        }
        .popular-searches span {
            background: var(--gray-100);
            padding: 0.3rem 1rem;
            border-radius: 30px;
            margin: 0 0.2rem;
            cursor: pointer;
        }

        /* quick categories grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px,1fr));
            gap: 1.5rem;
            margin: 3rem 0;
        }
        .category-card {
            background: white;
            padding: 2rem 1rem;
            border-radius: 2rem;
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: 0.2s;
            cursor: pointer;
        }
        .category-card:hover { transform: scale(1.02); background: var(--gold); }
        .category-icon { font-size: 3rem; }

        /* featured articles */
        .section-title {
            font-size: 2rem;
            color: var(--navy);
            margin: 2rem 0 1rem;
        }
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px,1fr));
            gap: 1.5rem;
        }
        .article-card {
            background: white;
            padding: 1.5rem;
            border-radius: 2rem;
            box-shadow: var(--shadow-md);
        }

        /* role-based columns */
        .role-row {
            display: flex;
            gap: 2rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }
        .role-col {
            flex: 1 1 200px;
            background: white;
            border-radius: 2rem;
            padding: 1.5rem;
        }

        /* FAQ accordion */
        .faq-item {
            background: white;
            border-radius: 2rem;
            margin: 1rem 0;
            padding: 1rem 2rem;
            cursor: pointer;
        }
        .faq-question {
            font-weight: 600;
            display: flex;
            justify-content: space-between;
        }
        .faq-answer {
            display: none;
            margin-top: 1rem;
            color: var(--gray-600);
        }
        .faq-item.open .faq-answer { display: block; }

        /* chat widget */
        .chat-widget {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--navy);
            color: white;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            z-index: 1000;
        }

        .toast {
            position: fixed; bottom: 100px; right: 30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body>
<div class="container">
    <!-- header with search -->
    <div class="header">
        <h1>❓ Help Center</h1>
        <p>Find answers and get support</p>
        <div class="search-box">
            <input type="text" placeholder="Search for help..." id="searchInput">
            <button class="search-btn" id="searchBtn">Search</button>
        </div>
        <div class="popular-searches">
            Popular: <span>Generate timetable</span> <span>Add faculty</span> <span>View schedule</span>
        </div>
    </div>

    <!-- quick help categories -->
    <div class="categories-grid">
        <div class="category-card"><div class="category-icon">🚀</div><div>Getting Started</div></div>
        <div class="category-card"><div class="category-icon">📅</div><div>Timetable Mgmt</div></div>
        <div class="category-card"><div class="category-icon">👥</div><div>User Management</div></div>
        <div class="category-card"><div class="category-icon">🔧</div><div>Technical Support</div></div>
        <div class="category-card"><div class="category-icon">❓</div><div>FAQ</div></div>
        <div class="category-card"><div class="category-icon">📞</div><div>Contact Support</div></div>
    </div>

    <!-- featured articles -->
    <h2 class="section-title">📌 Featured Articles</h2>
    <div class="featured-grid">
        <div class="article-card">📄 How to generate your first timetable</div>
        <div class="article-card">⚡ Understanding conflict resolution</div>
        <div class="article-card">👨‍🏫 Faculty availability setup guide</div>
        <div class="article-card">👩‍🎓 Student access and permissions</div>
    </div>

    <!-- role-based help -->
    <h2 class="section-title">👥 Role-Based Help</h2>
    <div class="role-row">
        <div class="role-col"><h3>👑 Admin</h3><ul><li>Setting up departments</li><li>Bulk import tutorials</li><li>Report generation</li></ul></div>
        <div class="role-col"><h3>👨‍🏫 Faculty</h3><ul><li>Managing availability</li><li>Leave request process</li><li>Taking attendance</li></ul></div>
        <div class="role-col"><h3>👩‍🎓 Student</h3><ul><li>Accessing timetable</li><li>Checking attendance</li><li>Mobile app guide</li></ul></div>
    </div>

    <!-- video tutorials & FAQ -->
    <div style="display: flex; gap:2rem; flex-wrap:wrap;">
        <div style="flex:2;">
            <h2 class="section-title">📺 Video Tutorials</h2>
            <div class="featured-grid">
                <div class="article-card">▶️ Timetable in 5 min</div>
                <div class="article-card">▶️ Admin Dashboard</div>
                <div class="article-card">▶️ Faculty Features</div>
            </div>
        </div>
        <div style="flex:3;">
            <h2 class="section-title">❓ Frequently Asked Questions</h2>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">How do I reset my password? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer">Go to login page and click 'Forgot password' – you'll receive an email with reset link.</div>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">Can I export timetable to PDF? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer">Yes, use the export button in the timetable view.</div>
            </div>
        </div>
    </div>

    <!-- contact & community -->
    <div class="role-row" style="margin-top:2rem;">
        <div class="role-col"><h4>📧 Email Support</h4> help@timetablegen.edu</div>
        <div class="role-col"><h4>📞 Phone hours</h4> Mon-Fri 9am-5pm</div>
        <div class="role-col"><h4>💬 Community Forum</h4> <i class="fas fa-comments"></i> Join discussion</div>
    </div>

    <!-- system status -->
    <div style="background: #e6f7e6; border-radius:2rem; padding:1.5rem; margin:2rem 0;">
        ✅ All systems operational · Last incident resolved 3d ago
    </div>

    <!-- feedback form -->
    <div style="background: white; border-radius:2rem; padding:2rem;">
        <h3>📝 Was this helpful? Rate your experience</h3>
        <div style="font-size:2rem; color:var(--gold);">★★★★☆</div>
        <button class="search-btn">Send feedback</button>
    </div>

    <!-- documentation links -->
    <div style="margin:2rem 0; text-align:center;">
        <a href="#">📘 User manual</a> | <a href="#">📚 API docs</a> | <a href="#">📋 Release notes</a>
    </div>
</div>

<!-- live chat widget -->
<div class="chat-widget" id="chatWidget">
    <i class="fas fa-comment"></i>
</div>

<!-- toast -->
<div id="toast" class="toast">🔍 Searching help...</div>

<script>
    function toggleFaq(element) {
        element.classList.toggle('open');
    }

    // search simulation
    document.getElementById('searchBtn').addEventListener('click', () => {
        const query = document.getElementById('searchInput').value;
        const toast = document.getElementById('toast');
        toast.innerText = `🔍 Searching for "${query || 'help'}"...`;
        toast.style.display = 'flex';
        setTimeout(() => toast.style.display = 'none', 2000);
    });

    // chat widget simulation
    document.getElementById('chatWidget').addEventListener('click', () => {
        alert('Live chat: Support available (simulated)');
    });

    // popular search chips
    document.querySelectorAll('.popular-searches span').forEach(span => {
        span.addEventListener('click', () => {
            document.getElementById('searchInput').value = span.innerText;
            document.getElementById('searchBtn').click();
        });
    });
</script>
</body>
</html>

