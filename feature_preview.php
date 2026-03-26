<?php
session_start();
$feature = isset($_GET['feature']) ? htmlspecialchars($_GET['feature']) : 'New Feature';
$role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

$dashUrl = 'home.php';
if ($role === 'admin') $dashUrl = 'admin.php';
elseif ($role === 'faculty') $dashUrl = 'facultyD.php';
elseif ($role === 'student') $dashUrl = 'studentD.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $feature ?> - Coming Soon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="premium.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        :root { --navy: #0a3b5b; --gold: #f4c542; }
        body { 
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }
        .preview-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 4rem 3rem;
            border-radius: 3rem;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            animation: floatUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes floatUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .icon {
            font-size: 5rem;
            color: var(--gold);
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        h1 {
            color: var(--navy);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        p {
            color: #475569;
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }
        .btn-primary {
            background: var(--navy);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px -5px rgba(10,59,91,0.3);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px -5px rgba(10,59,91,0.4);
        }
        .badge {
            background: #fef3c7;
            color: #b45309;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 2rem;
        }
        @media (max-width: 768px) {
            .preview-card { padding: 3rem 1.5rem; }
            h1 { font-size: 2rem; }
            .icon { font-size: 4rem; }
        }
    </style>
</head>
<body class="<?= 'theme-' . $role ?>">
    <div class="preview-card">
        <div class="badge">Coming Soon</div>
        <div class="icon"><i class="fas fa-rocket"></i></div>
        <h1><?= $feature ?></h1>
        <p>We're actively working on bringing <strong><?= $feature ?></strong> to life! Our engineers are crafting a premium experience that will be available in the next major update.</p>
        <a href="<?= $dashUrl ?>" class="btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>
