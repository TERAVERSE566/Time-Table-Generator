<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$themeClass = isset($_SESSION['user_role']) ? 'theme-' . $_SESSION['user_role'] : '';

$stmt = $conn->prepare("SELECT name, email, role, phone, department, program_level, created_at, profile_photo FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

$name = $user['name'] ?? 'Unknown User';
$email = $user['email'] ?? '';
$role = $user['role'] ?? 'student';
$phone = $user['phone'] ?? '+1 555 0000';
$dept = $user['department'] ?? 'General';
$join_date = date("M Y", strtotime($user['created_at']));
$profile_photo = $user['profile_photo'] ?? '';

$initials = strtoupper(substr($name, 0, 1));
if (strpos($name, ' ') !== false) {
    $parts = explode(' ', $name);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings · TimetableGen</title>
    <!-- Font Awesome 6 & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="premium.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ====================================================================
           MODERN ANIMATED PROFILE CSS — Complete Rewrite
           ==================================================================== */

        /* ── Keyframes ─────────────────────────────────────────────────────── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.92); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(79,70,229,0.35); }
            50%      { box-shadow: 0 0 18px 6px rgba(79,70,229,0.12); }
        }
        @keyframes shimmer {
            0%   { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        @keyframes progressGrow {
            from { width: 0%; }
            to   { width: 75%; }
        }
        @keyframes avatarRingSpin {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes toastSlideIn {
            from { transform: translateX(120%); opacity: 0; }
            to   { transform: translateX(0); opacity: 1; }
        }
        @keyframes toastSlideOut {
            from { transform: translateX(0); opacity: 1; }
            to   { transform: translateX(120%); opacity: 0; }
        }
        @keyframes strengthPulse {
            0%, 100% { opacity: 1; }
            50%      { opacity: 0.7; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-6px); }
        }
        @keyframes borderGlow {
            0%, 100% { border-color: rgba(79,70,229,0.3); }
            50%      { border-color: rgba(99,102,241,0.7); }
        }
        @keyframes verifiedPulse {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.2); }
        }
        @keyframes bgPan {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* ── Reset & Base ──────────────────────────────────────────────────── */
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
            --bg-light: #f4f7fc;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-800: #1e293b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-md: 0 12px 30px -8px rgba(10,59,91,0.12);
            --shadow-lg: 0 20px 45px -12px rgba(10,59,91,0.18);
            --shadow-glow: 0 0 30px -5px rgba(79,70,229,0.25);
            --border-radius: 2rem;
            --transition-smooth: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bounce: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            --glass-bg: rgba(255,255,255,0.72);
            --glass-border: rgba(255,255,255,0.45);
        }

        body {
            background: var(--bg-light);
            background-image:
                radial-gradient(ellipse at 20% 20%, rgba(79,70,229,0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(245,158,11,0.05) 0%, transparent 50%);
            background-attachment: fixed;
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease-out both;
        }

        /* ── Back Link ─────────────────────────────────────────────────────── */
        .container > a:first-of-type {
            display: inline-flex !important;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.4rem;
            border-radius: 50px;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--navy) !important;
            text-decoration: none !important;
            transition: var(--transition-smooth);
            animation: fadeInLeft 0.5s ease-out both;
        }
        .container > a:first-of-type:hover {
            background: var(--navy);
            color: white !important;
            transform: translateX(-4px);
            box-shadow: var(--shadow-md);
        }
        .container > a:first-of-type i {
            transition: transform 0.3s ease;
        }
        .container > a:first-of-type:hover i {
            transform: translateX(-3px);
        }

        /* ── Header ────────────────────────────────────────────────────────── */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
            gap: 1.5rem;
            animation: fadeInUp 0.6s 0.1s ease-out both;
        }
        .header-title h1 {
            font-size: 2.6rem;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--navy) 0%, #6366f1 60%, var(--gold) 100%);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: bgPan 6s ease-in-out infinite;
        }
        .header-title p {
            color: var(--gray-600);
            font-size: 1.05rem;
            margin-top: 0.3rem;
        }

        /* ── Progress Bar ──────────────────────────────────────────────────── */
        .progress-bar {
            width: 220px;
            height: 10px;
            background: var(--gray-200);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            width: 75%;
            height: 100%;
            background: linear-gradient(90deg, var(--navy), #6366f1, var(--gold));
            background-size: 200% 100%;
            border-radius: 20px;
            animation: progressGrow 1.4s cubic-bezier(0.4,0,0.2,1) both, shimmer 2.5s linear infinite;
            position: relative;
        }
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            background-size: 200% 100%;
            animation: shimmer 2s linear infinite;
        }

        /* ── Profile Overview Card ─────────────────────────────────────────── */
        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 2.5rem;
            padding: 2.5rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.7s 0.15s ease-out both;
            transition: var(--transition-smooth);
        }
        .profile-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(79,70,229,0.06), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .profile-card:hover {
            box-shadow: var(--shadow-glow);
            transform: translateY(-3px);
        }

        /* ── Avatar ────────────────────────────────────────────────────────── */
        .avatar-large {
            width: 130px;
            height: 130px;
            background: linear-gradient(135deg, var(--navy), #6366f1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            position: relative;
            background-size: cover;
            background-position: center;
            flex-shrink: 0;
            transition: var(--transition-smooth);
            box-shadow: 0 8px 25px -5px rgba(79,70,229,0.35);
            z-index: 1;
        }
        .avatar-large::before {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, var(--navy), #6366f1, var(--gold), #10b981, var(--navy));
            z-index: -1;
            animation: avatarRingSpin 8s linear infinite;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .profile-card:hover .avatar-large::before {
            opacity: 1;
        }
        .avatar-large:hover {
            transform: scale(1.05);
        }

        .upload-overlay {
            position: absolute;
            bottom: 2px;
            right: 2px;
            background: linear-gradient(135deg, var(--gold), #fbbf24);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            transition: var(--transition-bounce);
            box-shadow: 0 4px 12px rgba(245,158,11,0.4);
        }
        .upload-overlay:hover {
            transform: scale(1.15) rotate(10deg);
            box-shadow: 0 6px 18px rgba(245,158,11,0.55);
        }
        .upload-overlay i {
            font-size: 0.95rem;
            color: var(--gray-800);
        }

        /* ── Profile Info Text ─────────────────────────────────────────────── */
        .profile-info h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.3px;
        }
        .profile-info p {
            color: var(--gray-600);
            margin-top: 0.35rem;
            font-size: 0.98rem;
            line-height: 1.7;
        }
        .profile-info p i {
            margin-right: 0.3rem;
            color: var(--navy-light);
            transition: var(--transition-smooth);
        }
        .profile-info p:hover i {
            color: var(--gold);
        }

        .verified {
            color: var(--success);
            font-size: 1rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        .verified i {
            animation: verifiedPulse 2s ease-in-out infinite;
            display: inline-block;
        }

        /* ── Settings Tabs ─────────────────────────────────────────────────── */
        .settings-tabs {
            display: flex;
            gap: 0.5rem;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 0.5rem;
            border-radius: 60px;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            box-shadow: var(--shadow-sm);
            animation: fadeInUp 0.7s 0.2s ease-out both;
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            color: var(--gray-600);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.01em;
        }
        .tab-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--navy), #6366f1);
            border-radius: 50px;
            transform: scale(0);
            opacity: 0;
            transition: var(--transition-smooth);
            z-index: -1;
        }
        .tab-btn:hover {
            color: var(--navy);
            background: rgba(79,70,229,0.06);
        }
        .tab-btn.active {
            color: white;
            background: transparent;
        }
        .tab-btn.active::before {
            transform: scale(1);
            opacity: 1;
        }
        .tab-btn i {
            margin-right: 0.4rem;
            font-size: 0.85rem;
            transition: transform 0.3s ease;
        }
        .tab-btn:hover i {
            transform: scale(1.15);
        }

        /* ── Tab Pane ──────────────────────────────────────────────────────── */
        .tab-pane {
            display: none;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 2.5rem;
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        .tab-pane::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--navy), #6366f1, var(--gold));
            border-radius: 4px 4px 0 0;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .tab-pane.active {
            display: block;
            animation: fadeInScale 0.45s ease-out both;
        }
        .tab-pane.active::after {
            opacity: 1;
        }

        .tab-pane h3 {
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid var(--gray-200);
            position: relative;
        }
        .tab-pane h3::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--navy), #6366f1);
            border-radius: 3px;
        }

        /* ── Form Grid ─────────────────────────────────────────────────────── */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            animation: fadeInUp 0.5s ease-out both;
            position: relative;
        }
        .form-group:nth-child(1) { animation-delay: 0.05s; }
        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.15s; }
        .form-group:nth-child(4) { animation-delay: 0.2s; }
        .form-group:nth-child(5) { animation-delay: 0.25s; }
        .form-group:nth-child(6) { animation-delay: 0.3s; }

        .form-group label {
            font-weight: 600;
            color: var(--navy);
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.85rem 1.4rem;
            border-radius: 16px;
            border: 2px solid var(--gray-200);
            margin-top: 0;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--gray-800);
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(4px);
            transition: var(--transition-smooth);
            outline: none;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99,102,241,0.12), 0 4px 14px rgba(99,102,241,0.08);
            transform: translateY(-1px);
        }
        .form-group input:hover,
        .form-group select:hover {
            border-color: var(--gray-300);
            box-shadow: var(--shadow-sm);
        }
        .form-group input[readonly] {
            background: var(--gray-100);
            color: var(--gray-600);
            cursor: default;
            border-style: dashed;
        }

        /* ── Toggle Switch ─────────────────────────────────────────────────── */
        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.2rem 0;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            background: var(--gray-100);
            transition: var(--transition-smooth);
            cursor: default;
            animation: fadeInUp 0.5s 0.1s ease-out both;
        }
        .toggle-switch:hover {
            background: rgba(99,102,241,0.06);
            transform: translateX(4px);
        }
        .toggle-switch i {
            font-size: 1.2rem;
            color: var(--navy);
            width: 24px;
            text-align: center;
        }
        .toggle-switch input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 52px;
            height: 28px;
            background: var(--gray-300);
            border-radius: 14px;
            position: relative;
            cursor: pointer;
            transition: var(--transition-smooth);
            margin-left: auto;
            flex-shrink: 0;
        }
        .toggle-switch input[type="checkbox"]::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 22px;
            height: 22px;
            background: white;
            border-radius: 50%;
            transition: var(--transition-smooth);
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .toggle-switch input[type="checkbox"]:checked {
            background: linear-gradient(135deg, var(--navy), #6366f1);
        }
        .toggle-switch input[type="checkbox"]:checked::after {
            left: calc(100% - 25px);
        }

        /* ── Badge ─────────────────────────────────────────────────────────── */
        .badge {
            background: linear-gradient(135deg, var(--navy-light), #6366f1);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            box-shadow: 0 2px 8px rgba(79,70,229,0.25);
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }
        .badge::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }
        .badge:hover {
            transform: scale(1.05);
        }

        /* ── Action Bar & Buttons ──────────────────────────────────────────── */
        .action-bar {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
            animation: fadeInUp 0.5s 0.3s ease-out both;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--navy), #4f46e5);
            color: white;
            border: none;
            padding: 0.85rem 2.2rem;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.01em;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(79,70,229,0.3);
        }
        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #6366f1, #818cf8);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79,70,229,0.45);
        }
        .btn-primary:hover::before {
            opacity: 1;
        }
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(79,70,229,0.3);
        }

        .btn-outline {
            background: var(--glass-bg);
            backdrop-filter: blur(6px);
            border: 2px solid var(--gray-300);
            color: var(--navy);
            padding: 0.85rem 2.2rem;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }
        .btn-outline:hover {
            border-color: var(--navy);
            background: var(--navy);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .btn-outline:active {
            transform: translateY(0);
        }

        /* ── Strength Meter ────────────────────────────────────────────────── */
        .strength-meter {
            display: flex;
            gap: 6px;
            margin: 0.8rem 0;
        }
        .strength-bar {
            height: 10px;
            width: 33%;
            background: var(--gray-200);
            border-radius: 10px;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }
        .strength-bar::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
            background-size: 200% 100%;
        }
        .strength-bar.weak {
            background: linear-gradient(90deg, #ef4444, #f87171);
            animation: strengthPulse 1.5s ease-in-out infinite;
        }
        .strength-bar.weak::after { animation: shimmer 2s linear infinite; }
        .strength-bar.medium {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }
        .strength-bar.medium::after { animation: shimmer 2s 0.3s linear infinite; }
        .strength-bar.strong {
            background: linear-gradient(90deg, #10b981, #34d399);
        }
        .strength-bar.strong::after { animation: shimmer 2s 0.6s linear infinite; }

        /* ── Toast ──────────────────────────────────────────────────────────── */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, var(--navy), #4f46e5);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
            z-index: 2000;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(79,70,229,0.35);
            animation: toastSlideIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
            backdrop-filter: blur(10px);
        }

        /* ── Bottom Grid ───────────────────────────────────────────────────── */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
            animation: fadeInUp 0.7s 0.4s ease-out both;
        }
        .bottom-grid > .tab-pane {
            display: block !important;
            animation: fadeInUp 0.6s ease-out both;
            transition: var(--transition-smooth);
        }
        .bottom-grid > .tab-pane:first-child { animation-delay: 0.45s; }
        .bottom-grid > .tab-pane:last-child  { animation-delay: 0.55s; }
        .bottom-grid > .tab-pane:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .bottom-grid h4 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--navy);
        }

        /* ── Activity Log ──────────────────────────────────────────────────── */
        .container > .tab-pane:last-of-type {
            animation: fadeInUp 0.7s 0.55s ease-out both;
        }
        .container > .tab-pane[style*="display:block"] {
            transition: var(--transition-smooth);
        }
        .container > .tab-pane[style*="display:block"]:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        .container > .tab-pane[style*="display:block"] p {
            padding: 0.8rem 1rem;
            margin: 0.3rem 0;
            border-radius: 12px;
            transition: var(--transition-smooth);
            border-left: 3px solid transparent;
        }
        .container > .tab-pane[style*="display:block"] p:hover {
            background: rgba(99,102,241,0.04);
            border-left-color: #6366f1;
            transform: translateX(4px);
        }

        /* ── Notification List ─────────────────────────────────────────────── */
        #notifList li {
            padding: 0.6rem 0;
            transition: var(--transition-smooth);
            border-radius: 10px;
            padding-left: 0.5rem;
        }
        #notifList li:hover {
            background: rgba(99,102,241,0.04);
            transform: translateX(6px);
        }

        /* ── Accent Color Picker ───────────────────────────────────────────── */
        input[type="color"] {
            border-radius: 12px !important;
            border: 2px solid var(--gray-200) !important;
            cursor: pointer !important;
            transition: var(--transition-smooth) !important;
            padding: 2px !important;
        }
        input[type="color"]:hover {
            transform: scale(1.1) !important;
            box-shadow: var(--shadow-md) !important;
        }

        /* ── Two-Factor & Session Text ─────────────────────────────────────── */
        #security p {
            margin: 1rem 0;
            padding: 0.8rem 1.2rem;
            background: var(--gray-100);
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
            transition: var(--transition-smooth);
        }
        #security p:hover {
            background: rgba(99,102,241,0.05);
        }
        #security a {
            color: #6366f1;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition-smooth);
            border-bottom: 2px solid transparent;
        }
        #security a:hover {
            border-bottom-color: #6366f1;
        }

        /* ── Privacy Tab Buttons ───────────────────────────────────────────── */
        #privacy .btn-primary[style*="danger"] {
            transition: var(--transition-smooth) !important;
        }

        /* ── Scrollbar ─────────────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--gray-600); }

        /* ── Responsive ────────────────────────────────────────────────────── */
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .profile-card {
                flex-direction: column;
                text-align: center;
                padding: 2rem 1.5rem;
                border-radius: 2rem;
            }
            .profile-card:hover { transform: none; }
            .bottom-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .settings-tabs {
                gap: 0.3rem;
                padding: 0.4rem;
                border-radius: 20px;
            }
            .tab-btn {
                padding: 0.6rem 1rem;
                font-size: 0.82rem;
            }
            .header-title h1 { font-size: 2rem; }
            .action-bar { justify-content: center; flex-wrap: wrap; }
            .tab-pane { border-radius: 1.5rem; padding: 1.5rem; }
            .bottom-grid > .tab-pane:hover { transform: none; }
        }

        @media (max-width: 480px) {
            .tab-btn { padding: 0.5rem 0.7rem; font-size: 0.75rem; }
            .tab-btn i { display: none; }
            .avatar-large { width: 100px; height: 100px; font-size: 2.2rem; }
            .profile-info h2 { font-size: 1.5rem; }
            .btn-primary, .btn-outline { padding: 0.7rem 1.5rem; font-size: 0.88rem; }
        }
    </style>
</head>
<body class="<?= htmlspecialchars($themeClass) ?>">
<div class="container">
    <?php
        $dashLink = 'studentD.php';
        if ($role === 'faculty') $dashLink = 'facultyD.php';
        if ($role === 'admin') $dashLink = 'admin.php';
    ?>
    <a href="<?= $dashLink ?>" style="display:inline-block; margin-bottom:1.5rem; text-decoration:none; color:inherit; font-weight:600; font-size:1.1rem; transition:0.2s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <!-- header -->
    <div class="header-row">
        <div class="header-title">
            <h1>⚙️ Profile Settings</h1>
            <p>Manage your account and preferences</p>
        </div>
        <div style="display:flex; align-items:center; gap:1rem;">
            <span>Profile completeness</span>
            <div class="progress-bar"><div class="progress-fill"></div></div>
        </div>
    </div>

    <!-- profile overview card -->
    <div class="profile-card">
        <div class="avatar-large" id="avatarImage" style="background-image: <?= $profile_photo ? "url('$profile_photo')" : 'none' ?>;">
            <?= $profile_photo ? '' : htmlspecialchars($initials) ?>
            <div class="upload-overlay" id="uploadOverlay"><i class="fas fa-camera"></i></div>
            <input type="file" id="photoInput" accept="image/jpeg, image/png, image/webp" style="display:none;">
        </div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($name) ?> <span class="verified"><i class="fas fa-check-circle"></i> Verified</span></h2>
            <p><?= ucfirst(htmlspecialchars($role)) ?> · <?= htmlspecialchars($dept) ?></p>
            <p><i class="far fa-envelope"></i> <?= htmlspecialchars($email) ?> · <i class="fas fa-phone"></i> <?= htmlspecialchars($phone) ?></p>
            <p>Member since: <?= $join_date ?></p>
        </div>
    </div>

    <!-- settings tabs -->
    <div class="settings-tabs">
        <button class="tab-btn active" data-tab="personal"><i class="fas fa-user"></i> Personal</button>
        <button class="tab-btn" data-tab="academic"><i class="fas fa-graduation-cap"></i> Academic</button>
        <button class="tab-btn" data-tab="preferences"><i class="fas fa-sliders-h"></i> Preferences</button>
        <button class="tab-btn" data-tab="security"><i class="fas fa-lock"></i> Security</button>
        <button class="tab-btn" data-tab="notifications"><i class="fas fa-bell"></i> Notifications</button>
        <button class="tab-btn" data-tab="privacy"><i class="fas fa-shield-alt"></i> Privacy</button>
    </div>

    <!-- PERSONAL INFO TAB -->
    <div id="personal" class="tab-pane active">
        <h3>Personal Information</h3>
        <div class="form-grid">
            <div class="form-group"><label>Full name</label><input value="<?= htmlspecialchars($name) ?>"></div>
            <div class="form-group"><label>Email</label><input value="<?= htmlspecialchars($email) ?>" readonly></div>
            <div class="form-group"><label>Phone</label><input value="<?= htmlspecialchars($phone) ?>"></div>
            <div class="form-group"><label>Date of birth</label><input type="date" value="1985-06-15"></div>
            <div class="form-group"><label>Gender</label>
                <select>
                    <option value="female">Female</option>
                    <option value="male">Male</option>
                    <option value="nonbinary">Non-binary</option>
                    <option value="prefer_not_to_say">Prefer not to say</option>
                </select>
            </div>
        </div>
        <div class="action-bar"><button class="btn-primary">Save changes</button></div>
    </div>

    <!-- ACADEMIC TAB (role-specific) -->
    <div id="academic" class="tab-pane">
        <?php if ($role === 'admin'): ?>
        <h3>Administrative Details</h3>
        <div class="form-grid">
            <div class="form-group"><label>Role</label><input value="<?= ucfirst(htmlspecialchars($role)) ?>" readonly></div>
            <div class="form-group"><label>Department</label><input value="<?= htmlspecialchars($dept) ?>" readonly></div>
            <div class="form-group"><label>Member since</label><input value="<?= $join_date ?>" readonly></div>
        </div>
        <?php elseif ($role === 'faculty'): ?>
        <h3>Academic Details (Faculty)</h3>
        <div class="form-grid">
            <div class="form-group"><label>Department</label><input value="<?= htmlspecialchars($dept) ?>" readonly></div>
            <div class="form-group"><label>Designation</label><input value="Faculty Member"></div>
            <div class="form-group"><label>Member since</label><input value="<?= $join_date ?>" readonly></div>
            <div class="form-group"><label>Phone</label><input value="<?= htmlspecialchars($phone) ?>"></div>
        </div>
        <?php else: ?>
        <h3>Academic Details (Student)</h3>
        <div class="form-grid">
            <div class="form-group"><label>Program Level</label><input value="<?= htmlspecialchars($user['program_level'] ?? 'Not set') ?>" readonly></div>
            <div class="form-group"><label>Department</label><input value="<?= htmlspecialchars($dept) ?>" readonly></div>
            <div class="form-group"><label>Phone</label><input value="<?= htmlspecialchars($phone) ?>"></div>
            <div class="form-group"><label>Enrolled since</label><input value="<?= $join_date ?>" readonly></div>
        </div>
        <?php endif; ?>
        <div class="action-bar"><button class="btn-primary">Save changes</button></div>
    </div>

    <!-- PREFERENCES TAB -->
    <div id="preferences" class="tab-pane">
        <h3>Preferences</h3>
        <div class="form-grid">
            <div class="form-group"><label>Language</label><select><option>English</option></select></div>
            <div class="form-group"><label>Timezone</label><select><option>Asia/Kolkata</option></select></div>
            <div class="form-group"><label>Date format</label><select><option>DD/MM/YYYY</option></select></div>
            <div class="form-group"><label>Week start</label><select><option>Monday</option></select></div>
        </div>
        <div class="toggle-switch"><i class="fas fa-envelope"></i> Email notifications <input type="checkbox" checked></div>
        <div class="toggle-switch"><i class="fas fa-moon"></i> Dark mode <input type="checkbox" id="darkModeToggle"></div>
        <div class="action-bar"><button class="btn-primary">Save preferences</button></div>
    </div>

    <!-- SECURITY TAB -->
    <div id="security" class="tab-pane">
        <h3>Change password</h3>
        <div class="form-group"><label>Current password</label><input type="password"></div>
        <div class="form-group"><label>New password</label><input type="password"></div>
        <div class="strength-meter">
            <div class="strength-bar weak"></div><div class="strength-bar"></div><div class="strength-bar"></div>
        </div>
        <div class="form-group"><label>Confirm password</label><input type="password"></div>
        <p>Two-factor authentication: <button class="btn-outline">Enable</button></p>
        <p>Active sessions: 2 · <a href="feature_preview.php?feature=Log+Out+All+Sessions">Log out all</a></p>
        <div class="action-bar"><button class="btn-primary">Update security</button></div>
    </div>

    <!-- NOTIFICATIONS TAB -->
    <div id="notifications" class="tab-pane">
        <h3>Notification history</h3>
        <ul id="notifList" style="margin: 1rem 0; padding-left: 1.5rem; line-height:2;">
            <li>Schedule change: CS301 moved to LH-101 <span class="badge">1h ago</span></li>
            <li>Leave request approved <span class="badge">2d ago</span></li>
        </ul>
        <button class="btn-outline" id="markReadBtn">Mark all read</button>
    </div>

    <!-- PRIVACY TAB -->
    <div id="privacy" class="tab-pane">
        <h3>Data & privacy</h3>
        <div class="form-grid" style="margin-top: 1.5rem; margin-bottom: 2rem;">
            <div class="form-group">
                <label>Profile visibility</label>
                <select id="profileVisibility">
                    <option value="public">Public - visible to everyone</option>
                    <option value="private">Private - visible only to connections</option>
                </select>
            </div>
        </div>
        <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
            <button class="btn-outline" id="downloadDataBtn"><i class="fas fa-download"></i> Download my data (.zip)</button>
            <button class="btn-primary" style="background:var(--danger); border:none;" id="deleteAccountBtn"><i class="fas fa-trash-alt"></i> Delete account</button>
        </div>
    </div>

    <!-- connected accounts & appearance -->
    <div class="bottom-grid">
        <div class="tab-pane" style="display:block;">
            <h4>🔗 Connected accounts</h4>
            <p style="margin:1rem 0;">Google Calendar <span class="badge">connected</span> <button class="btn-outline" style="padding:0.3rem 1rem; font-size:0.8rem; margin-left:0.5rem;">Sync now</button></p>
            <p>Microsoft 365 <span class="badge">connected</span></p>
        </div>
        <div class="tab-pane" style="display:block;">
            <h4>🎨 Appearance</h4>
            <div style="margin-top:1.5rem;">
                <label style="display:flex; align-items:center; gap:1rem; font-weight:600;">Accent color: <input type="color" id="accentColorPicker" value="#0a3b5b" style="width:60px; height:40px; padding:0; border:none; border-radius:10px; cursor:pointer; background:transparent;"></label>
            </div>
            <p style="margin-top:1rem; font-size:0.9rem; color:var(--gray-600);">Changes update immediately across the entire dashboard.</p>
        </div>
    </div>

    <!-- activity log -->
    <div class="tab-pane" style="display:block; margin-top:2rem;">
        <h3>📋 Recent activity</h3>
        <p>Changed password · 2 days ago</p>
        <p>Updated profile photo · 1 week ago</p>
    </div>
</div>

<!-- toast -->
<div id="toast" class="toast">✅ Changes saved</div>

<script>
    (function() {
        // tab switching
        const tabs = document.querySelectorAll('.tab-btn');
        const panes = document.querySelectorAll('.tab-pane');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                panes.forEach(p => p.classList.remove('active'));
                document.getElementById(target).classList.add('active');
            });
        });

        // save buttons simulate toast
        document.querySelectorAll('.btn-primary').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const toast = document.getElementById('toast');
                toast.style.display = 'flex';
                setTimeout(() => toast.style.display = 'none', 2000);
            });
        });

        // upload photo logic
        const photoInput = document.getElementById('photoInput');
        const overlay = document.getElementById('uploadOverlay');
        const avatarBox = document.getElementById('avatarImage');
        const toast = document.getElementById('toast');

        overlay.addEventListener('click', () => {
            photoInput.click();
        });

        photoInput.addEventListener('change', (e) => {
            if(e.target.files.length > 0) {
                const fd = new FormData();
                fd.append('profile_photo', e.target.files[0]);
                
                fetch('upload_photo.php', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        toast.innerHTML = '✅ Photo updated successfully';
                        toast.style.display = 'flex';
                        avatarBox.style.backgroundImage = `url('${data.path}')`;
                        avatarBox.innerText = ''; // clear initials
                        avatarBox.appendChild(overlay);
                        avatarBox.appendChild(photoInput);
                        setTimeout(() => toast.style.display = 'none', 2000);
                    } else {
                        alert('Upload failed: ' + data.message);
                    }
                })
                .catch(err => {
                    alert('Error reaching server API.');
                });
            }
        });

        // password strength meter dummy
        // (static demo, no real logic)

        // Privacy Tab Actions
        const visibilitySelect = document.getElementById('profileVisibility');
        if(visibilitySelect) {
            visibilitySelect.addEventListener('change', () => {
                const toast = document.getElementById('toast');
                toast.innerText = '✅ Profile visibility strictly updated';
                toast.style.display = 'flex';
                setTimeout(() => { toast.style.display = 'none'; toast.innerText = '✅ Changes saved'; }, 2500);
            });
        }

        const downloadBtn = document.getElementById('downloadDataBtn');
        if(downloadBtn) {
            downloadBtn.addEventListener('click', () => {
                alert('📥 Compiling your personal data... A secure download link will be emailed to your registered address shortly.');
            });
        }

        const deleteBtn = document.getElementById('deleteAccountBtn');
        if(deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                const conf = confirm('⚠️ WARNING: This action is irreversible. Are you absolutely sure you want to permanently delete your account and all associated timetables/data?');
                if(conf) {
                    alert('Your account deletion request has been submitted to system administrators for processing.');
                }
            });
        }

        // Accent Color picker logic
        const colorPicker = document.getElementById('accentColorPicker');
        if (colorPicker) {
            colorPicker.value = localStorage.getItem('themeColor') || '#0a3b5b';
            colorPicker.addEventListener('input', (e) => {
                const val = e.target.value;
                // Force onto body with !important to crush role-based CSS lock
                document.body.style.setProperty('--navy', val, 'important');
                document.body.style.setProperty('--primary', val, 'important');
                document.body.style.setProperty('--navy-light', val, 'important');
                document.body.style.setProperty('--primary-light', val, 'important');
                localStorage.setItem('themeColor', val);
            });
        }

        // Notification logic
        const markReadBtn = document.getElementById('markReadBtn');
        const notifList = document.getElementById('notifList');
        if (markReadBtn && notifList) {
            markReadBtn.addEventListener('click', () => {
                notifList.innerHTML = '<li style="color:var(--gray-600); list-style:none; margin-left:-1.5rem;"><i class="fas fa-check-circle" style="color:var(--success);"></i> All caught up! No new notifications.</li>';
                markReadBtn.style.display = 'none';
            });
        }

        // Dark mode logic
        const darkToggle = document.getElementById('darkModeToggle');
        if (darkToggle) {
            darkToggle.checked = localStorage.getItem('darkMode') === 'true';
            darkToggle.addEventListener('change', (e) => {
                const isDark = e.target.checked;
                document.body.classList.toggle('dark-mode', isDark);
                localStorage.setItem('darkMode', isDark);
                
                // Keep the top faculty dark mode toggle in sync if it exists (for other pages, purely defensive code here)
            });
        }
    })();
</script>
<script src="theme.js"></script>
</body>
</html>

