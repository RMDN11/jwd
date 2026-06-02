<?php
// dashboard.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Daftarkan seluruh halaman pilihan Anda (Testimoni TETAP DIHAPUS sesuai permintaan sebelumnya)
$tabs = [
    'wa' => [
        'title' => 'WhatsApp Gateway',
        'file'  => 'wa.php',
        'icon'  => 'ph-bold ph-whatsapp-logo',
        'color' => 'bg-[#25D366]' // Signature Color per modul untuk aksen ikon yang colorful
    ],
    'pesan' => [
        'title' => 'Kirim Pesan Massal',
        'file'  => 'pesan.php',
        'icon'  => 'ph-bold ph-paper-plane-tilt',
        'color' => 'bg-indigo-500'
    ],
    'cuti' => [
        'title' => 'Manajemen Cuti',
        'file'  => 'cuti.php',
        'icon'  => 'ph-bold ph-calendar-blank',
        'color' => 'bg-rose-500'
    ],
    'grafik' => [
        'title' => 'Grafik Analitik',
        'file'  => 'grafik.php',
        'icon'  => 'ph-bold ph-chart-pie-slice',
        'color' => 'bg-amber-500'
    ],
    'format' => [
        'title' => 'Format Pesan',
        'file'  => 'format.php',
        'icon'  => 'ph-bold ph-text-columns',
        'color' => 'bg-cyan-500'
    ],
    'templates' => [
        'title' => 'Kelola Template',
        'file'  => 'manage_templates.php',
        'icon'  => 'ph-bold ph-layout',
        'color' => 'bg-fuchsia-500'
    ]
];

// 2. Ambil tab aktif saat ini dari parameter URL
$activeKey = $_GET['tab'] ?? 'wa';
if (!array_key_exists($activeKey, $tabs)) {
    $activeKey = 'wa';
}

$currentTab = $tabs[$activeKey];
$cache_buster = time();
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - JWD Platform</title>
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
          fontFamily: { 
            sans: ['Raleway', 'sans-serif'] 
          }
        }
      }
    }
  </script>
  
  <style>
    body {
      background-color: #0c0f1d; /* Deep Space Navy Blue netral */
      color: #f1f5f9;
      overflow: hidden;
    }

    /* Kurva transisi animasi organik ala Emil Kowalski */
    .smooth-trans {
      transition: all 300ms cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    /* Animasi teks entrance & penjelajah halaman */
    .animate-vibrant-text {
      opacity: 0;
      animation: textReveal 500ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }
    
    .animate-viewport-fade {
      opacity: 0;
      animation: frameEntrance 450ms cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
    }

    @keyframes textReveal {
      0% { opacity: 0; transform: translateY(10px); filter: blur(3px); }
      100% { opacity: 1; transform: translateY(0); filter: blur(0); }
    }

    @keyframes frameEntrance {
      0% { opacity: 0; transform: scale(0.995) translateY(4px); }
      100% { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Aksesibilitas navigasi keyboard instan */
    *:focus-visible {
      transition: none !important;
      outline: 2px solid #25D366;
      outline-offset: 2px;
    }

    /* Custom Scrollbar tipis untuk Sidebar */
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #1e264a; border-radius: 99px; }
  </style>
</head>
<body class="h-full font-sans antialiased flex flex-col md:flex-row">

  <div class="md:hidden bg-[#11162d] border-b border-slate-800/50 p-4 flex items-center justify-between z-50 shadow-md">
    <div class="flex items-center space-x-3">
      <img src="LOGOJWD.png?v=<?= $cache_buster ?>" alt="Logo" class="w-8 h-8 object-contain rounded-lg">
      <span class="font-extrabold text-white tracking-wider text-sm uppercase">JWD Engine</span>
    </div>
    <button id="mobile-menu-btn" class="text-slate-300 hover:text-white text-2xl focus:outline-none p-1 transition-transform active:scale-95">
      <i class="ph-bold ph-list"></i>
    </button>
  </div>

  <aside id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 w-64 bg-[#11162d] border-r border-slate-800/50 flex flex-col z-40 smooth-trans pt-16 md:pt-0 shadow-2xl">
    
    <div class="p-6 border-b border-slate-800/50 hidden md:flex items-center space-x-3">
      <div class="w-10 h-10 rounded-xl bg-[#171e3d] border border-slate-700/40 flex items-center justify-center p-1.5 shadow-md">
        <img src="LOGOJWD.png?v=<?= $cache_buster ?>" alt="Logo JWD" class="w-full h-full object-contain">
      </div>
      <div>
        <h2 class="text-xs font-extrabold text-white tracking-widest uppercase">JWD Core</h2>
        <p class="text-[11px] text-slate-400 font-medium tracking-wide mt-0.5">Control Application</p>
      </div>
    </div>

    <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto">
      <?php foreach ($tabs as $key => $data): 
          $isActive = ($key === $activeKey);
      ?>
        <a href="?tab=<?= $key ?>" 
           class="smooth-trans flex items-center space-x-3 px-4 py-3 rounded-xl text-xs font-bold tracking-wider group relative <?= $isActive ? 'bg-[#182042] text-white border border-slate-700/30' : 'text-slate-400 hover:text-slate-200 hover:bg-[#141b3a]' ?>">
          
          <div class="w-8 h-8 rounded-lg smooth-trans flex items-center justify-center shadow-sm <?= $isActive ? $data['color'] . ' text-white scale-105' : 'bg-[#171e3a] text-slate-400 group-hover:text-slate-200' ?>">
            <i class="<?= $data['icon'] ?> text-base"></i>
          </div>
          
          <span class="uppercase pl-1"><?= htmlspecialchars($data['title']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="p-4 border-t border-slate-800/50 bg-[#0d1226] text-center">
      <p class="text-[9px] text-slate-500 tracking-widest uppercase font-bold">© 2026 JWD Ecosystem</p>
    </div>
  </aside>

  <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden transition-opacity duration-300"></div>

  <main class="flex-1 flex flex-col overflow-hidden min-w-0 bg-[#0c0f1d]">
    
    <div class="bg-[#0c0f1d] border-b border-slate-800/40 px-6 py-4 hidden md:flex items-center justify-between">
      <div class="flex items-center space-x-2 animate-vibrant-text">
        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Active Module</span>
        <span class="text-slate-700 text-xs">/</span>
        <h1 class="text-xs font-extrabold text-white tracking-widest uppercase flex items-center">
            <span class="inline-block w-2 h-2 rounded-full mr-2 <?= $currentTab['color'] ?>"></span>
            <?= htmlspecialchars($currentTab['title']) ?>
        </h1>
      </div>
      <div class="flex items-center space-x-2 bg-[#121833] border border-slate-800/60 px-3 py-1.5 rounded-full">
        <span class="w-1.5 h-1.5 rounded-full bg-[#25D366] animate-pulse"></span>
        <span class="text-[10px] font-mono text-slate-300 font-bold tracking-wider uppercase">Engine Live</span>
      </div>
    </div>

    <div class="flex-1 w-full h-full relative p-2 sm:p-4 bg-[#0a0d1a] animate-viewport-fade">
      <iframe src="<?= htmlspecialchars($currentTab['file']) ?>" 
              class="absolute inset-0 w-full h-full border-0 m-0 p-0 rounded-none md:rounded-t-2xl md:border-l md:border-t md:border-slate-800/40"
              title="<?= htmlspecialchars($currentTab['title']) ?>"
              id="app-content-frame"></iframe>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const mobileMenuBtn = document.getElementById('mobile-menu-btn');
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebar-overlay');
      let isOpen = false;

      function toggleMenu() {
        isOpen = !isOpen;
        if (isOpen) {
          sidebar.classList.remove('-translate-x-full');
          overlay.classList.remove('hidden');
        } else {
          sidebar.classList.add('-translate-x-full');
          overlay.classList.add('hidden');
        }
      }

      mobileMenuBtn.addEventListener('click', toggleMenu);
      overlay.addEventListener('click', toggleMenu);
    });
  </script>
</body>
</html>