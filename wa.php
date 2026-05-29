<?php
session_start();

// Debug mode untuk development (nonaktifkan di production)
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php'; // Pastikan path ini benar

$error = '';
$success_message = '';

// Cek jika sudah login, redirect ke PESAN.PHP
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: pesan.php");
    exit();
}

// Cek jika ada pesan logout
if (isset($_SESSION['logout_message'])) {
    $success_message = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $conn->prepare("SELECT id, username, password FROM login_event WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['role'] = 'user';
                    
                    header("Location: pesan.php");
                    exit();
                } else {
                    $error = "Password salah!";
                }
            } else {
                $error = "Username tidak ditemukan!";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Terjadi kesalahan sistem";
            error_log("Login error: " . $e->getMessage());
        }
    } else {
        $error = "Username dan password harus diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- ✅ FIX: Viewport yang benar untuk mobile & desktop -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#f0f9ff">
    <title>Login | Reqra WA - Pesan Cerdas</title>
    <link rel="icon" type="image/png" href="LOGOJWD.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ✅ FIX: Reset & Base Styles */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        :root {
            --color-primary: #4f46e5;
            --color-primary-dark: #4338ca;
            --color-secondary: #c241ff;
            --color-bg-start: #eef2ff;
            --color-bg-mid: #fae8ff;
            --color-bg-end: #fff0f0;
            --color-text-dark: #0f172a;
            --color-text-muted: #64748b;
            --color-border: #e2e8f0;
            --radius-lg: 1.5rem;
            --radius-xl: 2rem;
            --shadow-lg: 0 20px 40px -20px rgba(0, 0, 0, 0.12);
            --shadow-xl: 0 32px 55px -20px rgba(0, 0, 0, 0.18);
            --transition-fast: 150ms ease;
            --transition-normal: 250ms ease;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--color-bg-start) 0%, var(--color-bg-mid) 50%, var(--color-bg-end) 100%);
            min-height: 100vh;
            min-height: 100dvh; /* ✅ FIX: Support dynamic viewport height untuk mobile */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(0.75rem, 3vw, 1.5rem);
            position: relative;
            overflow-x: hidden;
        }

        /* ✅ FIX: Blob background dengan fallback & performa lebih baik */
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.45;
            z-index: 0;
            pointer-events: none;
            will-change: transform;
        }
        .bg-blob-1 {
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, #a78bfa 0%, #f472b6 70%);
            top: -60px;
            left: -60px;
            animation: floatBlob 20s infinite alternate ease-in-out;
        }
        .bg-blob-2 {
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, #38bdf8 0%, #818cf8 80%);
            bottom: -80px;
            right: -50px;
            animation: floatBlob2 24s infinite alternate ease-in-out;
        }
        @keyframes floatBlob {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, 25px) scale(1.15); }
        }
        @keyframes floatBlob2 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-25px, -30px) scale(1.2); }
        }
        /* ✅ FIX: Fallback untuk browser yang tidak support backdrop-filter */
        @supports not (backdrop-filter: blur(10px)) {
            .glass { backdrop-filter: none !important; background: rgba(255,255,255,0.98) !important; }
        }

        /* ✅ FIX: Layout utama dengan flex yang responsif */
        .login-wrapper {
            width: 100%;
            max-width: 1280px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: clamp(1rem, 4vw, 2.5rem);
            position: relative;
            z-index: 10;
        }

        /* ✅ FIX: Hero section - stacking di mobile */
        .hero-section {
            flex: 1 1 320px;
            max-width: 520px;
            background: rgba(255, 255, 255, 0.55);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: var(--radius-xl);
            padding: clamp(1.25rem, 3vw, 2rem);
            border: 1px solid rgba(255, 255, 255, 0.75);
            box-shadow: var(--shadow-lg);
        }

        .chip-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(110deg, #ffffff, #f3e8ff);
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #6d28d9;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(139, 92, 246, 0.25);
        }

        .hero-title {
            font-size: clamp(1.75rem, 5vw, 2.75rem); /* ✅ FIX: Responsive font size */
            font-weight: 800;
            letter-spacing: -0.02em;
            background: linear-gradient(125deg, #0c0a2a, var(--color-primary), #db2777);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1.15;
            margin-bottom: 0.875rem;
        }

        .hero-desc {
            font-size: 0.95rem;
            color: var(--color-text-muted);
            font-weight: 450;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            background: rgba(255,255,255,0.6);
            padding: 0.65rem 0.9rem;
            border-radius: 1rem;
            border: 1px solid rgba(255,255,255,0.9);
            transition: transform var(--transition-fast);
        }
        .feature-item:active { transform: scale(0.99); }

        .feature-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(145deg, #ffffff, #f1f5f9);
            border-radius: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #7c3aed;
            flex-shrink: 0;
        }

        .feature-text strong { display: block; font-size: 0.85rem; color: var(--color-text-dark); }
        .feature-text span { font-size: 0.65rem; color: var(--color-text-muted); }

        /* ✅ FIX: Login card - mobile first approach */
        .login-card {
            flex: 1 1 340px;
            max-width: 480px;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 2.25rem;
            box-shadow: var(--shadow-lg), 0 0 0 1px rgba(255,255,255,0.9);
            border: 1px solid rgba(255,255,255,0.95);
            transition: transform var(--transition-normal), box-shadow var(--transition-normal);
            overflow: hidden;
        }
        .login-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl), 0 0 0 1px rgba(255,255,255,0.95);
        }

        .card-header {
            padding: clamp(1.25rem, 3vw, 1.75rem) clamp(1.25rem, 3vw, 1.75rem) 0.75rem;
            text-align: center;
            border-bottom: 1px solid #ede9fe;
        }

        .logo-wrapper {
            width: 68px;
            height: 68px;
            background: white;
            margin: 0 auto 0.75rem;
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px -10px rgba(99, 102, 241, 0.35);
            border: 1px solid #e0e7ff;
        }
        .logo-wrapper i {
            font-size: 2.2rem;
            background: linear-gradient(145deg, #2563eb, #a855f7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-text-dark);
            margin-bottom: 0.25rem;
        }
        .card-header p {
            color: var(--color-text-muted);
            font-size: 0.8rem;
        }
        .card-header p strong { color: var(--color-primary); }

        .card-body {
            padding: clamp(1.25rem, 3vw, 1.875rem);
        }

        .alert-box {
            padding: 0.75rem 1rem;
            border-radius: 0.875rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.825rem;
            font-weight: 500;
            animation: slideIn 0.25s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-error { background: #fef2f2; border-left: 4px solid #ef4444; color: #b91c1c; }
        .alert-success { background: #ecfeff; border-left: 4px solid #06b6d4; color: #0e7490; }

        .form-group { margin-bottom: 1.25rem; }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--color-text-dark);
            margin-bottom: 0.45rem;
        }
        .form-label i { color: var(--color-primary); }

        .input-wrapper { position: relative; }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.4rem; /* ✅ FIX: Padding kiri untuk icon */
            border-radius: 1rem;
            border: 1.5px solid var(--color-border);
            background: #ffffff;
            font-size: 0.95rem; /* ✅ FIX: Font size minimal 16px untuk hindari zoom iOS */
            font-weight: 500;
            transition: all var(--transition-fast);
            font-family: inherit;
        }
        @media (max-width: 375px) {
            .form-input { font-size: 1rem; } /* ✅ FIX: Prevent iOS zoom on focus */
        }
        .form-input:focus {
            border-color: #c084fc;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.12);
            outline: none;
        }
        .form-input::placeholder { color: #94a3b8; }

        .icon-input {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
            pointer-events: none;
        }

        .toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color var(--transition-fast);
        }
        .toggle-password:hover, .toggle-password:focus { color: var(--color-primary); outline: none; }
        .toggle-password:active { transform: translateY(-50%) scale(0.95); }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.8rem;
            color: var(--color-text-muted);
            cursor: pointer;
            user-select: none;
        }
        .checkbox-label input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--color-primary);
            cursor: pointer;
        }

        .forgot-link {
            font-size: 0.775rem;
            font-weight: 600;
            color: var(--color-primary);
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        .forgot-link:hover { color: var(--color-primary-dark); text-decoration: underline; }

        .btn-submit {
            width: 100%;
            background: linear-gradient(98deg, var(--color-primary), var(--color-secondary));
            border: none;
            padding: 0.875rem 1rem;
            border-radius: 1.75rem;
            font-weight: 700;
            font-size: 0.975rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            box-shadow: 0 8px 20px -8px rgba(139, 92, 246, 0.45);
        }
        .btn-submit:hover:not(:disabled) {
            filter: brightness(1.05);
            box-shadow: 0 10px 24px -8px rgba(139, 92, 246, 0.55);
        }
        .btn-submit:active:not(:disabled) { transform: scale(0.985); }
        .btn-submit:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            filter: grayscale(0.2);
        }

        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            margin-top: 1.375rem;
            background: #faf5ff;
            padding: 0.55rem 0.875rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            color: var(--color-text-muted);
            font-weight: 500;
        }

        .footer-note {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.65rem;
            color: #94a3b8;
            line-height: 1.4;
        }

        /* ✅ FIX: Responsive breakpoints yang lebih granular */
        @media (max-width: 900px) {
            .login-wrapper { gap: 1.75rem; }
            .hero-section { padding: 1.5rem; }
        }

        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; }
            .hero-section, .login-card { max-width: 100%; }
            .hero-title { font-size: 2.1rem; }
            .form-actions { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .forgot-link { margin-left: auto; }
        }

        @media (max-width: 480px) {
            body { padding: 0.625rem; }
            .hero-section, .login-card { border-radius: var(--radius-lg); }
            .card-header { padding: 1.125rem 1.125rem 0.5rem; }
            .card-body { padding: 1.125rem; }
            .hero-title { font-size: 1.875rem; }
            .chip-badge { font-size: 0.65rem; padding: 0.3rem 0.875rem; }
            .feature-item { padding: 0.55rem 0.8rem; }
            .feature-icon { width: 34px; height: 34px; font-size: 1rem; }
            .btn-submit { padding: 0.8125rem; font-size: 0.95rem; }
        }

        @media (max-width: 360px) {
            .hero-title { font-size: 1.7rem; }
            .form-input { padding-left: 2.25rem; font-size: 1rem; }
            .btn-submit { font-size: 0.925rem; }
        }

        /* ✅ FIX: Utility classes untuk animasi & aksesibilitas */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .fade-out {
            animation: fadeOut 0.3s ease-out forwards;
        }
        @keyframes fadeOut {
            to { opacity: 0; transform: translateY(-5px); }
        }

        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ✅ FIX: Reduce motion untuk pengguna yang sensitif */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            .bg-blob { animation: none !important; }
        }
    </style>
</head>
<body>

<!-- ✅ FIX: Blob background dipisah agar lebih ringan -->
<div class="bg-blob bg-blob-1"></div>
<div class="bg-blob bg-blob-2"></div>

<div class="login-wrapper">
    <!-- LEFT: Hero Section -->
    <div class="hero-section glass">
        <div class="chip-badge">
            <i class="fa-regular fa-message" style="color:#a855f7"></i> 
            <span>WhatsApp Business Platform</span>
        </div>
        <h1 class="hero-title">Pesan &<br>Promosi<br>Tanpa Batas</h1>
        <p class="hero-desc">
            Akses dashboard <strong style="color:#7c3aed">Reqra Pesan</strong> — kirim broadcast, template, dan analitik instan.
        </p>
        <div class="feature-list">
            <div class="feature-item">
                <div class="feature-icon"><i class="fa-regular fa-paper-plane"></i></div>
                <div class="feature-text"><strong>Broadcast Massal</strong><span>+ personalisasi kontak</span></div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fa-regular fa-clock"></i></div>
                <div class="feature-text"><strong>Jadwal Pintar</strong><span>Otomatis & realtime</span></div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fa-regular fa-chart-line"></i></div>
                <div class="feature-text"><strong>Laporan Lengkap</strong><span>terkirim & dibaca</span></div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Login Card -->
    <div class="login-card glass">
        <div class="card-header">
            <div class="logo-wrapper">
                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
            </div>
            <h2>Reqra WA</h2>
            <p>Masuk ke <strong>Pesan & Marketing</strong></p>
        </div>
        <div class="card-body">
            <div id="liveAlert" role="alert" aria-live="polite"></div>

            <form id="loginForm" method="POST" novalidate>
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa-regular fa-user" aria-hidden="true"></i> Username
                    </label>
                    <div class="input-wrapper">
                        <i class="fa-regular fa-at icon-input" aria-hidden="true"></i>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            class="form-input" 
                            placeholder="nama pengguna" 
                            autocomplete="username" 
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            required
                            minlength="3"
                            aria-required="true"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa-regular fa-lock" aria-hidden="true"></i> Kata Sandi
                    </label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-key icon-input" aria-hidden="true"></i>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-input" 
                            placeholder="••••••••" 
                            autocomplete="current-password"
                            required
                            minlength="6"
                            aria-required="true"
                        >
                        <button 
                            type="button" 
                            class="toggle-password" 
                            id="togglePwd" 
                            aria-label="Tampilkan atau sembunyikan kata sandi"
                            aria-pressed="false"
                        >
                            <i class="fa-regular fa-eye" id="eyeIcon" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember"> 
                        <span>Ingat saya</span>
                    </label>
                    <a href="#" id="forgotLink" class="forgot-link">Lupa password?</a>
                </div>

                <button type="submit" class="btn-submit" id="loginSubmitBtn">
    <!-- ✅ FIX: Ganti fa-regular → fa-solid -->
    <i class="fa-solid fa-arrow-right-to-bracket" id="btnIcon" aria-hidden="true"></i>
    <span id="btnText">Masuk ke Pesan</span>
</button>
            </form>

            <div class="security-note">
    <i class="fa-solid fa-shield-halved" style="color:#10b981;" aria-hidden="true"></i>
    <span>Enkripsi 256-bit · Aman & Terpercaya</span>
</div>
            <div class="footer-note">
                Reqra WhatsApp &copy; <?= date('Y') ?> — Dashboard Pesan
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        // ✅ FIX: Escape HTML untuk mencegah XSS
        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // ✅ FIX: Tampilkan alert dengan animasi & auto-hide
        function showAlert(message, type) {
            const alertContainer = document.getElementById('liveAlert');
            if (!alertContainer) return;
            
            // Hapus alert sebelumnya
            const existing = alertContainer.querySelector('.alert-box');
            if (existing) existing.remove();

            const alertEl = document.createElement('div');
            alertEl.className = `alert-box ${type === 'error' ? 'alert-error' : 'alert-success'}`;
            alertEl.setAttribute('role', 'alert');
            const icon = type === 'error' 
                ? '<i class="fa-regular fa-circle-xmark" aria-hidden="true"></i>' 
                : '<i class="fa-regular fa-circle-check" aria-hidden="true"></i>';
            alertEl.innerHTML = `${icon} <span>${escapeHtml(message)}</span>`;
            
            alertContainer.appendChild(alertEl);
            
            // Auto hide setelah 4.5 detik
            setTimeout(() => {
                alertEl.classList.add('fade-out');
                setTimeout(() => alertEl.remove(), 300);
            }, 4500);
        }

        // ✅ FIX: Load pesan dari PHP dengan aman
        const errorFromPHP = <?= json_encode($error, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const successMsg = <?= json_encode($success_message, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        
        if (errorFromPHP) showAlert(errorFromPHP, 'error');
        if (successMsg) showAlert(successMsg, 'success');

        // ✅ FIX: Toggle password dengan aksesibilitas
        const toggleBtn = document.getElementById('togglePwd');
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (toggleBtn && pwdInput) {
            toggleBtn.addEventListener('click', function() {
                const isPassword = pwdInput.type === 'password';
                pwdInput.type = isPassword ? 'text' : 'password';
                eyeIcon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
                toggleBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                toggleBtn.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                pwdInput.focus();
            });
        }

        // ✅ FIX: Form validation & loading state
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('loginSubmitBtn');
        const btnText = document.getElementById('btnText');
        const btnIcon = document.getElementById('btnIcon');

        if (form) {
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('username')?.value.trim();
                const password = document.getElementById('password')?.value.trim();
                
                // Validasi client-side
                if (!username || !password) {
                    e.preventDefault();
                    showAlert('Username dan kata sandi wajib diisi!', 'error');
                    if (!username) document.getElementById('username')?.focus();
                    else document.getElementById('password')?.focus();
                    return false;
                }
                
                if (username.length < 3) {
                    e.preventDefault();
                    showAlert('Username minimal 3 karakter!', 'error');
                    document.getElementById('username')?.focus();
                    return false;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    showAlert('Password minimal 6 karakter!', 'error');
                    document.getElementById('password')?.focus();
                    return false;
                }

                // Loading state
                if (submitBtn) {
                    submitBtn.disabled = true;
                    btnIcon.className = 'fa-regular fa-circle-notch spin';
                    btnText.textContent = 'Memproses...';
                }
                return true;
            });
        }

        // ✅ FIX: Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // ✅ FIX: Forgot password handler
        const forgotLink = document.getElementById('forgotLink');
        if (forgotLink) {
            forgotLink.addEventListener('click', function(e) {
                e.preventDefault();
                showAlert('Silakan hubungi administrator untuk reset password.', 'success');
            });
        }

        // ✅ FIX: Auto-focus hanya di desktop (hindari scroll jump di mobile)
        if (window.matchMedia('(min-width: 769px)').matches) {
            setTimeout(() => {
                const usernameInput = document.getElementById('username');
                if (usernameInput && !usernameInput.value) {
                    usernameInput.focus();
                }
            }, 300);
        }

        // ✅ FIX: Handle viewport resize untuk dynamic height
        function setVh() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        window.addEventListener('resize', setVh);
        window.addEventListener('orientationchange', setVh);
        setVh();

    })();
</script>

</body>
</html>