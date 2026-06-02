<?php
// dashboard.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Daftarkan seluruh halaman pilihan Anda (Testimoni TELAH DIHAPUS sesuai permintaan)
$tabs = [
    'wa' => [
        'title' => 'WhatsApp Gateway',
        'file'  => 'wa.php',
        'icon'  => 'ph-whatsapp-logo-bold',
        'color' => 'from-emerald-500 to-teal-500' // Gradasi warna colorful per modul
    ],
    'pesan' => [
        'title' => 'Kirim Pesan Massal',
        'file'  => 'pesan.php',
        'icon'  => 'ph-paper-plane-tilt-bold',
        'color' => 'from-indigo-500 to-purple-500'
    ],
    'cuti' => [
        'title' => 'Manajemen Cuti',
        'file'  => 'cuti.php',
        'icon'  => 'ph-calendar-blank-bold',
        'color' => 'from-rose-500 to-pink-500'
    ],
    'grafik' => [
        'title' => 'Grafik Analitik',
        'file'  => 'grafik.php',
        'icon'  => 'ph-chart-pie-slice-bold',
        'color' => 'from-amber-500 to-orange-500'
    ],
    'format' => [
        'title' => 'Format Pesan',
        'file'  => 'format.php',
        'icon'  => 'ph-text-columns-bold',
        'color' => 'from-cyan-500 to-blue-500'
    ],
    'templates' => [
        'title' => 'Kelola Template',
        'file'  => 'manage_templates.php',
        'icon'  => 'ph-layout-bold',
        'color' => 'from-violet-500 to-fuchsia-500'
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
  <title>JWD Vibrant Dashboard</title>
  <link rel="icon" href="LOGOJWD.png?v=<?= $cache_buster ?>" type="image/png">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/phosphor-icons/1.4.2/css/phosphor.css" rel="stylesheet">
  
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
      background-color: #0c0f1d; /* Deep Space Navy Blue untuk memperkuat warna kontras */
      color: #f1f5f9;
      overflow: hidden;
    }

    /* Kurva transisi animasi organik ala Emil Kowalski */
    .smooth-trans {
      transition: all 300ms cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    /* Animasi teks entrance & tabel */
    .animate-vibrant-text {
      animation: textReveal 450ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }
    
    .animate-viewport-fade {
      animation: frameEntrance 400ms cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
    }

    @keyframes textReveal {
      0% { opacity: 0; transform: translateY(12px); filter: blur(4px); }
      100% { opacity: 1; transform: translateY(0); filter: blur(0); }
    }

    @keyframes frameEntrance {
      0% { opacity: 0; transform: scale(0.99) translateY(4px); }
      100% { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Aksesibilitas navigasi keyboard instan */
    *:focus-visible {
      transition: none !important;
      outline: 3px solid #6366f1;
      outline-offset: 2px;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 99px; }
    ::-webkit-scrollbar-thumb:hover { background: #334155; }
  </style>
</head>
<body class="h-full font-sans antialiased flex flex-col md:flex-row">

  <div class="md:hidden bg-[#131930] border-b border-[#1e264a] p-4 flex items-center justify-between z-50 shadow-md">
    <div class="flex items-center space-x-3">
      <img src="LOGOJWD.png?v=<?= $cache_buster ?>" alt="Logo" class="w-8 h-8 object-contain">
      <span class="font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 tracking-wide text-sm">JWD Engine</span>
    </div>
    <button id="mobile-menu-btn" class="text-slate-300 hover:text-white text-2xl focus:outline-none p-1 transition-transform active:scale-95">
      <i class="ph-list-bold"></i>
    </button>
  </div>

  <aside id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 w-64 bg-[#11162d] border-r border-[#1c2345] flex flex-col z-40 smooth-trans pt-16 md:pt-0 shadow-2xl">
    
    <div class="p-6 border-b border-[#1c2345] hidden md:flex items-center space-x-4">
      <div class="w-10 h-10 rounded-xl bg-[#171e3d] border border-[#242f5e] flex items-center justify-center p-1.5 shadow-md">
        <img src="LOGOJWD.png?v=<?= $cache_buster ?>" alt="Logo JWD" class="w-full h-full object-contain">
      </div>
      <div>
        <h2 class="text-sm font-extrabold text-white tracking-wide">JWD Core Platform</h2>
        <p class="text-[11px] text-slate-400 font-medium">Administration Module</p>
      </div>
    </div>

    <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto">
      <?php foreach ($tabs as $key => $data): 
          $isActive = ($key === $activeKey);
      ?>
        <a href="?tab=<?= $key ?>" 
           class="smooth-trans flex items-center space-x-3 px-4 py-3.5 rounded-xl text-xs font-semibold tracking-wider group relative overflow-hidden <?= $isActive ? 'bg-[#182042] text-white shadow-lg border border-[#29356f]' : 'text-slate-400 hover:text-slate-200 hover:bg-[#141b3a]' ?>">
          
          <?php if ($isActive): ?>
            <span class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b <?= $data['color'] ?>"></span>
          <?php endif; ?>

          <div class="p-1.5 rounded-lg smooth-trans <?= $isActive ? 'bg-gradient-to-br ' . $data['color'] . ' text-white shadow-md' : 'bg-[#171e3a] text-slate-400 group-hover:text-slate-200' ?>">
            <i class="<?= $data['icon'] ?> text-base"></i>
          </div>
          <span class="uppercase"><?= htmlspecialchars($data['title']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="p-4 border-t border-[#1c2345] bg-[#0d1226] text-center">
      <p class="text-[9px] text-slate-500 tracking-widest uppercase font-bold">© 2026 JWD Ecosystem</p>
    </div>
  </aside>

  <div id="sidebar-overlay" class="fixed inset-0 bg-black/70 z-30 hidden md:hidden transition-opacity duration-300"></div>

  <main class="flex-1 flex flex-col overflow-hidden min-w-0 bg-[#0c0f1d]">
    
    <div class="bg-[#0c0f1d] border-b border-[#161d3a] px-6 py-4 hidden md:flex items-center justify-between">
      <div class="flex items-center space-x-2 animate-vibrant-text">
        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Active Screen</span>
        <span class="text-slate-700 text-xs">/</span>
        <h1 class="text-xs font-extrabold text-transparent bg-clip-text bg-gradient-to-r <?= $currentTab['color'] ?> tracking-widest uppercase">
            <?= htmlspecialchars($currentTab['title']) ?>
        </h1>
      </div>
      <div class="flex items-center space-x-2 bg-[#121833] border border-[#1d2754] px-3 py-1.5 rounded-full shadow-inner">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="text-[10px] font-mono text-slate-300 font-bold tracking-wider uppercase">Live Connection</span>
      </div>
    </div>

    <div class="flex-1 w-full h-full relative p-2 sm:p-4 bg-[#0a0d1a] animate-viewport-fade">
      <iframe src="<?= htmlspecialchars($currentTab['file']) ?>" 
              class="absolute inset-0 w-full h-full border-0 m-0 p-0 rounded-none md:rounded-t-2xl md:border-l md:border-t md:border-[#192142]"
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
          setTimeout(() => overlay.classList.add('opacity-100'), 20);
        } else {
          sidebar.classList.add('-translate-x-full');
          overlay.classList.remove('opacity-100');
          setTimeout(() => overlay.classList.add('hidden'), 300);
        }
      }

      mobileMenuBtn.addEventListener('click', toggleMenu);
      overlay.addEventListener('click', toggleMenu);
    });
  </script>
</body>
</html>