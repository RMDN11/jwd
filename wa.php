<?php
// wa.php - Halaman Login Utama JWD
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tambahkan koneksi database Anda di bawah ini jika diperlukan, contoh:
// require_once 'config.php';

$error_message = '';

// --- LOGIKA UTAMA: JIKA SUDAH LOGIN, LANGSUNG LEMPAR KE DASHBOARD ---
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// --- CONTOH PROSES FORM POST (Pertahankan fungsi verifikasi asli Anda di sini) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // SILAKAN MASUKKAN ATAU PERTAHANKAN LOGIKA COCOK DATA/PASSWORD ASLI ANDA DI SINI
    // Contoh Sederhana Fungsional:
    if (!empty($username) && !empty($password)) {
        // Jika verifikasi database asli Anda sukses, set session:
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        
        // DIALIKAN UTAMA KE DASHBOARD NEW
        header("Location: dashboard.php");
        exit();
    } else {
        $error_message = "Username dan password wajib diisi!";
    }
}

$cache_buster = time();
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WhatsApp Gateway - JWD Platform</title>
  <link rel="icon" href="LOGOJWD.png?v=<?= $cache_buster ?>" type="image/png">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Raleway', 'sans-serif'] },
          colors: {
            wa: {
              light: '#25D366',
              dark: '#128C7E'
            }
          }
        }
      }
    }
  </script>
  
  <style>
    body {
      background-color: #0c0f1d;
      overflow-x: hidden;
    }

    .smooth-trans {
      transition: all 300ms cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .animate-fade-up {
      opacity: 0;
      animation: fadeUpReveal 500ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }
    
    @keyframes fadeUpReveal {
      0% { opacity: 0; transform: translateY(20px); filter: blur(5px); }
      100% { opacity: 1; transform: translateY(0); filter: blur(0); }
    }

    .delay-1 { animation-delay: 100ms; }
    .delay-2 { animation-delay: 200ms; }
    .delay-3 { animation-delay: 300ms; }

    *:focus-visible {
      transition: none !important;
      outline: 2px solid #25D366;
      outline-offset: 2px;
    }
  </style>
</head>
<body class="h-full font-sans antialiased flex items-center justify-center p-4 selection:bg-[#25D366] selection:text-white">

  <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-72 h-72 bg-[#25D366] rounded-full opacity-10 blur-[120px] pointer-events-none"></div>

  <div class="w-full max-w-md relative z-10">
    
    <div class="bg-[#11162d] border border-slate-800/50 rounded-3xl shadow-2xl p-6 sm:p-10 relative animate-fade-up">
      
      <div class="text-center mb-8">
        
        <div class="flex justify-center items-center space-x-3 mb-5 animate-fade-up delay-1">
          <div class="w-12 h-12 rounded-2xl bg-[#171e3d] border border-slate-700/50 flex items-center justify-center p-1.5 shadow-md">
            <img src="LOGOJWD.png?v=<?= $cache_buster ?>" alt="Logo JWD" class="w-full h-full object-contain">
          </div>
          
          <i class="ph-bold ph-plus text-slate-500"></i>
          
          <div class="w-12 h-12 rounded-2xl bg-[#25D366]/10 border border-[#25D366]/20 flex items-center justify-center shadow-md text-wa-light">
            <i class="ph-fill ph-whatsapp-logo text-3xl"></i>
          </div>
        </div>

        <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-wide uppercase animate-fade-up delay-1">
          WA Gateway Login
        </h1>
        <p class="text-xs text-slate-400 mt-1.5 font-medium tracking-wide animate-fade-up delay-2">Akses core system pengiriman pesan Anda.</p>
      </div>

      <?php if (!empty($error_message)): ?>
        <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold tracking-wide flex items-center space-x-2 animate-fade-up">
          <i class="ph-bold ph-warning-circle text-base"></i>
          <span><?= htmlspecialchars($error_message) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="" class="space-y-5 animate-fade-up delay-2">
        
        <div>
          <label for="username" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Username</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500">
              <i class="ph-bold ph-user text-base"></i>
            </span>
            <input type="text" name="username" id="username" placeholder="Masukkan nama pengguna..." 
                   class="w-full bg-[#0a0d1a] border border-slate-800 rounded-xl py-3.5 pl-11 pr-4 text-slate-200 text-sm smooth-trans focus:border-wa-light focus:ring-1 focus:ring-wa-light placeholder-slate-600" 
                   required autocomplete="off">
          </div>
        </div>

        <div>
          <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Password</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500">
              <i class="ph-bold ph-lock-key text-base"></i>
            </span>
            <input type="password" name="password" id="password" placeholder="••••••••" 
                   class="w-full bg-[#0a0d1a] border border-slate-800 rounded-xl py-3.5 pl-11 pr-4 text-slate-200 text-sm smooth-trans focus:border-wa-light focus:ring-1 focus:ring-wa-light placeholder-slate-600" 
                   required>
          </div>
        </div>

        <div class="pt-3">
          <button type="submit" 
                  class="w-full bg-gradient-to-r from-wa-dark to-wa-light text-white py-3.5 px-4 rounded-xl font-bold text-sm tracking-widest uppercase shadow-lg shadow-[#25D366]/20 smooth-trans hover:opacity-95 active:scale-[0.98] flex items-center justify-center group">
            <span>Masuk ke Dashboard</span>
            <i class="ph-bold ph-arrow-right text-sm ml-2 smooth-trans group-hover:translate-x-1"></i>
          </button>
        </div>

      </form>
    </div>

    <div class="text-center mt-6 animate-fade-up delay-3">
      <p class="text-[10px] text-slate-500 tracking-widest uppercase font-bold">© 2026 JWD WA ENGINE</p>
    </div>

  </div>

</body>
</html>