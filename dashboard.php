<?php
// dashboard.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Daftarkan seluruh halaman yang ada di folder jwd Anda ke dalam sistem tab
$tabs = [
    'wa' => [
        'title' => 'WhatsApp Gateway',
        'file'  => 'wa.php',
        'icon'  => 'ph-whatsapp-logo-bold'
    ],
    'pesan' => [
        'title' => 'Kirim Pesan',
        'file'  => 'pesan.php',
        'icon'  => 'ph-paper-plane-tilt-bold'
    ],
    'cuti' => [
        'title' => 'Manajemen Cuti',
        'file'  => 'cuti.php',
        'icon'  => 'ph-calendar-blank-bold'
    ],
    'grafik' => [
        'title' => 'Grafik Analitik',
        'file'  => 'grafik.php',
        'icon'  => 'ph-chart-pie-slice-bold'
    ],
    'format' => [
        'title' => 'Format Pesan',
        'file'  => 'format.php',
        'icon'  => 'ph-text-columns-bold'
    ],
    'templates' => [
        'title' => 'Kelola Template',
        'file'  => 'manage_templates.php',
        'icon'  => 'ph-layout-bold'
    ],
    'testimoni' => [
        'title' => 'Testimoni User',
        'file'  => 'testimoni.php',
        'icon'  => 'ph-chat-teardrop-text-bold'
    ]
];

// 2. Ambil tab aktif saat ini dari parameter URL, jika tidak ada, arahkan ke 'wa'
$activeKey = $_GET['tab'] ?? 'wa';
if (!array_key_exists($activeKey, $tabs)) {
    $activeKey = 'wa';
}

$currentTab = $tabs[$activeKey];
$cache_buster = time();
?>
<!DOCTYPE html>
<html lang="id" class="dark h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JWD Premium Dashboard</title>
  <link rel="icon" href="LOGOJWD.png?v=<?= $cache_buster ?>" type="image/png">
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@1.0.3/dist/fonts/geist-sans/style.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/phosphor-icons/1.4.2/css/phosphor.css" rel="stylesheet">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: { sans: ['Geist', 'sans-serif'] },
          colors: {
            brand: {
              lime: '#E6FF00',
              darkbg: '#09090B',
              sidebar: '#121214',
              border: '#27272A'
            }
          }
        }
      }
    }
  </script>
  
  <style>
    body {
      background-color: theme('colors.brand.darkbg');
      color: #EDEDED;
      overflow: hidden;
    }
    .smooth-trans {
      transition: all 250ms cubic-bezier(0.32, 0.72, 0, 1);
    }
    /* Sembunyikan scrollbar bawaan untuk sidebar layout */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #27272A; border-radius: 99px; }
  </style>
</head>
<body class="h-full font-sans antialiased flex flex-col md:flex-row">

  <div class="md:hidden bg-brand-sidebar border-b border-brand-border p-4 flex items-center justify-between z-50">
    <div class="flex items-center space-x-3">
      <img src="LOGOJWD.png?v=<?= $cache_buster ?>" alt="Logo" class="w-8 h-8 object-contain">
      <span class="font-bold text-white tracking-tight">JWD Engine</span>
    </div>
    <button id="mobile-menu-btn" class="text-zinc-400 hover:text-white text-2xl focus:outline-none p-1">
      <i class="ph-list-bold"></i>
    </button>
  </div>

  <aside id="sidebar" class="fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 w-64 bg-brand-sidebar border-r border-brand-border flex flex-col z-40 smooth-trans pt-16 md:pt-0">
    
    <div class="p-6 border-b border-brand-border hidden md:flex items-center space-x-4">
      <div class="w-10 h-10 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center p-1.5">
        <img src="LOGOJWD.png?v=<?= $cache_buster ?>" alt="Logo JWD" class="w-full h-full object-contain">
      </div>
      <div>
        <h2 class="text-base font-bold text-white tracking-tight">JWD Platform</h2>
        <p class="text-xs text-zinc-500">Core Administration</p>
      </div>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
      <?php foreach ($tabs as $key => $data): 
          $isActive = ($key === $activeKey);
      ?>
        <a href="?tab=<?= $key ?>" 
           class="smooth-trans flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium tracking-wide group <?= $isActive ? 'bg-zinc-900 text-brand-lime border border-zinc-800 shadow-inner' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900/40' ?>">
          <i class="<?= $data['icon'] ?> text-lg smooth-trans <?= $isActive ? 'text-brand-lime' : 'text-zinc-500 group-hover:text-zinc-300' ?>"></i>
          <span><?= htmlspecialchars($data['title']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="p-4 border-t border-brand-border bg-zinc-900/20 text-center">
      <p class="text-[10px] text-zinc-600 tracking-wider uppercase font-semibold">© 2026 JWD Ecosystem</p>
    </div>
  </aside>

  <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden smooth-trans"></div>

  <main class="flex-1 flex flex-col overflow-hidden min-w-0 bg-brand-darkbg">
    
    <div class="bg-brand-darkbg border-b border-brand-border/60 px-6 py-4 hidden md:flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <span class="text-xs text-zinc-500 font-medium uppercase tracking-widest">Modul Terbuka</span>
        <span class="text-xs text-zinc-700">/</span>
        <h1 class="text-sm font-semibold text-white tracking-tight"><?= htmlspecialchars($currentTab['title']) ?></h1>
      </div>
      <div class="flex items-center space-x-3">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span class="text-xs font-mono text-zinc-400">System Secure</span>
      </div>
    </div>

    <div class="flex-1 w-full h-full relative bg-zinc-900/10">
      <iframe src="<?= htmlspecialchars($currentTab['file']) ?>" 
              class="absolute inset-0 w-full h-full border-0 m-0 p-0"
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