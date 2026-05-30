<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 1. PENGATURAN LOG ERROR
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// 2. PROTEKSI FILE AUTHENTIKASI
if (!file_exists('auth_checkwa.php')) {
    error_log("CRITICAL ERROR: File auth_checkwa.php hilang.");
    die("Sistem dihentikan: File otentikasi tidak ditemukan.");
}
require_once 'auth_checkwa.php';

// 3. PROTEKSI KONEKSI DATABASE
try {
    if (!file_exists('config.php')) throw new Exception("File config.php tidak ditemukan di server.");
    require_once 'config.php';
    if (!isset($conn)) throw new Exception("Variabel \$conn tidak terdeteksi di dalam file config.php.");
    if ($conn->connect_error) throw new Exception("Koneksi Database Gagal: " . $conn->connect_error);
} catch (Throwable $e) { 
    error_log("DATABASE ERROR: " . $e->getMessage());
    die("<div style='padding:30px; text-align:center; color:#333;'><h2 style='color:#e11d48;'>Sistem Mengalami Gangguan ⚠️</h2><p>Mohon maaf, sistem tidak dapat terhubung ke database. Cek file php_errors.log</p></div>");
}

mysqli_set_charset($conn, "utf8mb4");

// ==================================================================
// AMBIL SEMUA NOMOR PENGAMPU UNTUK DI-EXCLUDE
// ==================================================================
$pengampuNumbers = [];
$pengampuResult = $conn->query("SELECT nowa FROM pengampu WHERE nowa IS NOT NULL AND nowa != ''");
if ($pengampuResult) {
    while ($p = $pengampuResult->fetch_assoc()) {
        $cleanNum = preg_replace('/\D/', '', $p['nowa']);
        if (strpos($cleanNum, '0') === 0) $cleanNum = '62' . substr($cleanNum, 1);
        $pengampuNumbers[$cleanNum] = true;
        $pengampuNumbers[$p['nowa']] = true; // Simpan juga format asli
    }
}

// ==================================================================
// ==================================================================
// FILTER & PENCARIAN
// ==================================================================
$f_start = $_GET['from'] ?? '';
$f_end = $_GET['to'] ?? '';
$f_status = $_GET['status'] ?? ''; 
$search = $_GET['search'] ?? '';

// BUILD QUERY UTAMA (DI-OPTIMASI UNTUK MENCEGAH ERROR 500/TIMEOUT)
$sql = "
    SELECT 
        lw.id,
        lw.nowa,
        lw.nama,
        lw.message,
        lw.created_at,
        p.halaqoh as peserta_halaqoh,
        pg1.nama as nama_pengajar,
        pg1.halaqoh as pengajar_halaqoh,
        pg2.nama as pengajar_langsung
    FROM log_wa lw
    LEFT JOIN peserta p ON p.nowa = lw.nowa
    LEFT JOIN pengampu pg1 ON (p.halaqoh = pg1.halaqoh AND p.halaqoh != '')
    LEFT JOIN pengampu pg2 ON (pg2.nowa = lw.nowa AND pg2.nowa != '')
    WHERE (
        lw.message LIKE '%cuti%' 
        OR lw.message LIKE '%tidak lanjut%'
        OR lw.message LIKE '%gak lanjut%'
        OR lw.message LIKE '%berhenti%'
        OR lw.message LIKE '%pause%'
        OR lw.message LIKE '%izin tidak%'
    )
";

/// Jika tidak ada filter tanggal, JANGAN load semua data (Pencegah Error 500)
// Setel ke 30 hari terakhir agar ringan
if (empty($f_start) && empty($f_end)) {
    $f_start = date('Y-m-d', strtotime('-30 days'));
}

// Tambah filter tanggal
if ($f_start) {
    $sql .= " AND DATE(lw.created_at) >= '" . $conn->real_escape_string($f_start) . "'";
}
if ($f_end) {
    $sql .= " AND DATE(lw.created_at) <= '" . $conn->real_escape_string($f_end) . "'";
}

// Tambah filter status
if ($f_status === 'cuti') {
    $sql .= " AND (LOWER(lw.message) LIKE '%cuti%' OR LOWER(lw.message) LIKE '%pause%' OR LOWER(lw.message) LIKE '%izin%')";
} elseif ($f_status === 'tidak_lanjut') {
    $sql .= " AND (LOWER(lw.message) LIKE '%tidak lanjut%' OR LOWER(lw.message) LIKE '%gak lanjut%' OR LOWER(lw.message) LIKE '%berhenti%')";
}

// Tambah pencarian
if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $sql .= " AND (LOWER(lw.nama) LIKE '%" . strtolower($search_escaped) . "%' 
                OR lw.nowa LIKE '%" . $search_escaped . "%'
                OR p.halaqoh LIKE '%" . $search_escaped . "%'
                OR LOWER(pg1.nama) LIKE '%" . strtolower($search_escaped) . "%'
                OR LOWER(pg2.nama) LIKE '%" . strtolower($search_escaped) . "%')";
}

// Tutup query SATU KALI SAJA di bagian paling akhir
$sql .= " ORDER BY lw.created_at DESC LIMIT 500";

$result = $conn->query($sql);
$dataRows = [];
$countCuti = 0;
$countTidakLanjut = 0;
$summaryHalaqoh = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cleanNowa = preg_replace('/\D/', '', $row['nowa'] ?? '');
        if (strpos($cleanNowa, '0') === 0) $cleanNowa = '62' . substr($cleanNowa, 1);
        
        if (isset($pengampuNumbers[$cleanNowa]) || isset($pengampuNumbers[$row['nowa']])) {
            continue;
        }
        
        $msgLower = strtolower($row['message'] ?? '');
        
        $kategori = 'Lainnya';
        if (strpos($msgLower, 'cuti') !== false || strpos($msgLower, 'pause') !== false || strpos($msgLower, 'izin') !== false) {
            $kategori = 'Cuti';
            $countCuti++;
        } elseif (strpos($msgLower, 'tidak lanjut') !== false || strpos($msgLower, 'gak lanjut') !== false || strpos($msgLower, 'berhenti') !== false) {
            $kategori = 'Tidak Lanjut';
            $countTidakLanjut++;
        }
        
        $halaqoh = $row['peserta_halaqoh'] ?? $row['pengajar_halaqoh'] ?? '-';
        // Tentukan nama pengajar yang terdeteksi
        $nama_pengajar_final = $row['nama_pengajar'] ?? $row['pengajar_langsung'] ?? '-';
        
        if (!isset($summaryHalaqoh[$halaqoh])) {
            $summaryHalaqoh[$halaqoh] = ['cuti' => 0, 'tidak_lanjut' => 0, 'total' => 0];
        }
        if ($kategori === 'Cuti') {
            $summaryHalaqoh[$halaqoh]['cuti']++;
        } else if ($kategori === 'Tidak Lanjut') {
            $summaryHalaqoh[$halaqoh]['tidak_lanjut']++;
        }
        $summaryHalaqoh[$halaqoh]['total']++;
        
        $dataRows[] = [
            'id' => $row['id'],
            'nama' => $row['nama'] ?? '-',
            'nowa' => $row['nowa'] ?? '-',
            'halaqoh' => $halaqoh,
            'pengajar' => $nama_pengajar_final,
            'message' => $row['message'] ?? '-',
            'created_at' => $row['created_at'],
            'kategori' => $kategori
        ];
    }
}

$totalRows = count($dataRows);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Cuti & Tidak Lanjut | JWD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .custom-scroll { max-height: 600px; overflow-y: auto; scrollbar-width: thin; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .vibrant-card { border-radius: 24px; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.5); }
        .btn-glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 14px; transition: all 0.3s ease; }
        .btn-glass:hover { background: rgba(255, 255, 255, 1); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 0; border-radius: 24px; width: 90%; max-width: 600px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: modalSlideIn 0.3s ease-out; }
        @keyframes modalSlideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close-modal { color: #94a3b8; float: right; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s; }
        .close-modal:hover { color: #ef4444; }
        .chat-bubble { background: #f1f5f9; padding: 16px; border-radius: 16px; margin: 12px 0; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body class="p-4 md:p-8">

<div class="max-w-[1400px] mx-auto">
    
    <!-- HEADER -->
    <header class="bg-white vibrant-card p-6 mb-6 flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-gradient-to-br from-rose-500 to-orange-500 p-4 rounded-2xl text-white shadow-lg">
                <i class="fas fa-clipboard-list text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800">Laporan Cuti & Tidak Lanjut</h1>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Monitoring status peserta</p>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <a href="pesan.php" class="btn-glass px-5 py-2.5 text-sm font-bold text-slate-600 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </header>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total -->
        <div class="bg-gradient-to-br from-slate-700 to-slate-900 vibrant-card p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider opacity-80 mb-1">Total Catatan</p>
                    <h3 class="text-4xl font-black"><?= $totalRows ?></h3>
                    <p class="text-[10px] opacity-70 mt-2">Semua periode</p>
                </div>
                <div class="bg-white/10 p-4 rounded-2xl">
                    <i class="fas fa-database text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Cuti -->
        <div class="bg-gradient-to-br from-amber-400 to-orange-500 vibrant-card p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider opacity-90 mb-1"><i class="fas fa-calendar-times mr-1"></i> Cuti</p>
                    <h3 class="text-4xl font-black"><?= $countCuti ?></h3>
                    <p class="text-[10px] opacity-90 mt-2">Peserta minta jeda</p>
                </div>
                <div class="bg-white/20 p-4 rounded-2xl">
                    <i class="fas fa-user-clock text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Tidak Lanjut -->
        <div class="bg-gradient-to-br from-rose-500 to-pink-600 vibrant-card p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider opacity-90 mb-1"><i class="fas fa-user-times mr-1"></i> Tidak Lanjut</p>
                    <h3 class="text-4xl font-black"><?= $countTidakLanjut ?></h3>
                    <p class="text-[10px] opacity-90 mt-2">Peserta berhenti</p>
                </div>
                <div class="bg-white/20 p-4 rounded-2xl">
                    <i class="fas fa-user-minus text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- RINGKASAN PER HALAQOH -->
    <?php if (!empty($summaryHalaqoh)): ?>
    <div class="vibrant-card bg-white p-6 mb-6">
        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-indigo-500"></i> Ringkasan Per Halaqoh
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach($summaryHalaqoh as $halaqohName => $counts): ?>
            <div class="bg-gradient-to-br from-slate-50 to-slate-100 p-4 rounded-xl border border-slate-200 hover:shadow-md transition-shadow">
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2"><?= htmlspecialchars($halaqohName) ?></div>
                <div class="flex items-center justify-between">
                    <div class="text-center">
                        <div class="text-lg font-black text-amber-600"><?= $counts['cuti'] ?></div>
                        <div class="text-[8px] text-slate-500">Cuti</div>
                    </div>
                    <div class="w-px h-8 bg-slate-300"></div>
                    <div class="text-center">
                        <div class="text-lg font-black text-rose-600"><?= $counts['tidak_lanjut'] ?></div>
                        <div class="text-[8px] text-slate-500">Tidak Lanjut</div>
                    </div>
                    <div class="w-px h-8 bg-slate-300"></div>
                    <div class="text-center">
                        <div class="text-lg font-black text-slate-700"><?= $counts['total'] ?></div>
                        <div class="text-[8px] text-slate-500">Total</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- FILTER SECTION -->
    <div class="vibrant-card bg-white p-6 mb-6">
        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-indigo-500"></i> Filter Data
        </h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">Dari Tanggal</label>
                <input type="date" name="from" value="<?= htmlspecialchars($f_start) ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-200">
            </div>
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">Sampai Tanggal</label>
                <input type="date" name="to" value="<?= htmlspecialchars($f_end) ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-200">
            </div>
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">Status</label>
                <select name="status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-200">
                    <option value="">Semua Status</option>
                    <option value="cuti" <?= $f_status === 'cuti' ? 'selected' : '' ?>>📅 Cuti</option>
                    <option value="tidak_lanjut" <?= $f_status === 'tidak_lanjut' ? 'selected' : '' ?>>❌ Tidak Lanjut</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">Pencarian</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama / Halaqoh / Pengajar" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-200">
            </div>
            <div class="md:col-span-4 flex gap-3">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-all shadow-lg shadow-indigo-500/30">
                    <i class="fas fa-search mr-2"></i>Terapkan Filter
                </button>
                <a href="laporan_cuti.php" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-2.5 rounded-xl font-bold text-sm transition-all">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <!-- TABLE SECTION -->
    <div class="vibrant-card bg-white overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-table text-indigo-500"></i> Detail Data
                <span class="bg-indigo-100 text-indigo-700 px-2.5 py-0.5 rounded-md text-[10px] font-bold"><?= $totalRows ?> Records</span>
            </h3>
        </div>
        
        <div class="custom-scroll">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 text-[11px] uppercase font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-4 w-16 text-center">No</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Nama Peserta</th>
                        <th class="p-4">Nomor WA</th>
                        <th class="p-4">Halaqoh</th>
                        <th class="p-4">Pengajar</th>
                        <th class="p-4">Pesan</th>
                        <th class="p-4">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($dataRows)): ?>
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-400">
                            <i class="fas fa-inbox text-4xl mb-3 opacity-30"></i>
                            <p class="text-sm font-medium">Tidak ada data yang ditemukan</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach($dataRows as $row): 
                            $isCuti = ($row['kategori'] === 'Cuti');
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="showDetail(<?= htmlspecialchars(json_encode($row)) ?>)">
                            <td class="p-4 text-center font-bold text-slate-400"><?= $no++ ?></td>
                            <td class="p-4">
                                <?php if($isCuti): ?>
                                    <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-700 px-3 py-1.5 rounded-full text-[10px] font-bold">
                                        <i class="fas fa-calendar-times"></i> Cuti
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 bg-rose-100 text-rose-700 px-3 py-1.5 rounded-full text-[10px] font-bold">
                                        <i class="fas fa-user-times"></i> Tidak Lanjut
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($row['nama']) ?></div>
                            </td>
                            <td class="p-4">
                                <div class="text-xs text-slate-500 font-mono"><?= htmlspecialchars($row['nowa']) ?></div>
                                <a href="https://wa.me/<?= preg_replace('/\D/', '', $row['nowa']) ?>" target="_blank" class="text-[10px] text-emerald-600 hover:underline" onclick="event.stopPropagation()">
                                    <i class="fab fa-whatsapp mr-1"></i>Chat
                                </a>
                            </td>
                            <td class="p-4">
                                <span class="bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg text-[10px] font-bold">
                                    <?= htmlspecialchars($row['halaqoh']) ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="text-xs font-semibold text-slate-700">
                                    <?= htmlspecialchars($row['pengajar']) ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="text-xs text-slate-600 max-w-xs truncate" title="Klik untuk lihat detail">
                                    <i class="fas fa-eye text-indigo-400 mr-1"></i> <?= htmlspecialchars(substr($row['message'], 0, 40)) ?><?= strlen($row['message']) > 40 ? '...' : '' ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="text-xs text-slate-500">
                                    <?= date('d/m/Y', strtotime($row['created_at'])) ?>
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    <?= date('H:i', strtotime($row['created_at'])) ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="mt-6 text-center text-xs text-slate-400">
        <p>© <?= date('Y') ?> JWD - Laporan Cuti & Tidak Lanjut</p>
    </div>

</div>

<!-- MODAL DETAIL CHAT -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="p-6 border-b border-slate-200 flex justify-between items-center bg-gradient-to-r from-indigo-50 to-purple-50 rounded-t-2xl">
            <div>
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-comments text-indigo-600"></i> Detail Chat
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Pesan lengkap dari peserta</p>
            </div>
            <span class="close-modal" onclick="closeModal()">&times;</span>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Peserta</div>
                <div class="font-bold text-slate-800 text-lg" id="modalNama">-</div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nomor WA</div>
                    <div class="text-sm text-slate-600 font-mono" id="modalNowa">-</div>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Halaqoh</div>
                    <div class="text-sm text-slate-600" id="modalHalaqoh">-</div>
                </div>
            </div>
            <div class="mb-4">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Pengajar</div>
                <div class="text-sm text-slate-600" id="modalPengajar">-</div>
            </div>
            <div class="mb-4">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal</div>
                <div class="text-sm text-slate-600" id="modalTanggal">-</div>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Isi Pesan</div>
                <div class="chat-bubble bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-100 text-slate-700" id="modalPesan">
                    -
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <a href="#" id="modalWaLink" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-xl font-bold text-sm transition-all text-center">
                    <i class="fab fa-whatsapp mr-2"></i>Buka WhatsApp
                </a>
                <button onclick="closeModal()" class="px-6 py-3 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl font-bold text-sm transition-all">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal Functions
function showDetail(rowData) {
    document.getElementById('modalNama').textContent = rowData.nama;
    document.getElementById('modalNowa').textContent = rowData.nowa;
    document.getElementById('modalHalaqoh').textContent = rowData.halaqoh;
    document.getElementById('modalPengajar').textContent = rowData.pengajar;
    document.getElementById('modalTanggal').textContent = new Date(rowData.created_at).toLocaleString('id-ID', { 
        day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' 
    });
    document.getElementById('modalPesan').textContent = rowData.message;
    
    // Set WhatsApp link
    const cleanNumber = rowData.nowa.replace(/\D/g, '');
    document.getElementById('modalWaLink').href = 'https://wa.me/' + cleanNumber;
    
    // Show modal
    document.getElementById('detailModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('detailModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});
</script>

</body>
</html>