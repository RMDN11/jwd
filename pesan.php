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

if (!isset($apiUrl) && defined('ONESENDER_API_URL')) $apiUrl = ONESENDER_API_URL;
if (!isset($apiToken) && defined('ONESENDER_API_TOKEN')) $apiToken = ONESENDER_API_TOKEN;

if (!function_exists('curl_init')) die("Sistem Error: Ekstensi PHP 'cURL' belum diaktifkan.");

// AUTO-UPDATE SCHEMA DATABASE
mysqli_set_charset($conn, "utf8mb4");
$cols = [];
$res = $conn->query("SHOW COLUMNS FROM log_wa");
if ($res) while($r = $res->fetch_assoc()) $cols[] = $r['Field'];

if(!in_array('last_followup_at', $cols)) @$conn->query("ALTER TABLE log_wa ADD last_followup_at DATETIME NULL");
if(!in_array('is_form_sent', $cols)) @$conn->query("ALTER TABLE log_wa ADD is_form_sent TINYINT(1) DEFAULT 0");
if(!in_array('last_template_name', $cols)) @$conn->query("ALTER TABLE log_wa ADD last_template_name VARCHAR(150) NULL");

if (!isset($_SESSION['followed_up_today'])) $_SESSION['followed_up_today'] = [];
$notification = $notificationType = '';
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification']; 
    $notificationType = $_SESSION['notificationType'];
    unset($_SESSION['notification'], $_SESSION['notificationType']);
}

// ==================================================================
// FUNGSI API & LOGIKA UTAMA
// ==================================================================
function kirimPesan($recipient, $message, $apiUrl, $apiToken) {
    if (empty($recipient) || empty($message)) return ['status' => 'GAGAL', 'msg' => 'Parameter kosong'];
    $cleanNumber = preg_replace('/\D/', '', $recipient);
    if (strlen($cleanNumber) > 0 && $cleanNumber[0] === '0') $cleanNumber = '62' . substr($cleanNumber, 1);
    
    $data = ["recipient_type" => "individual", "to" => $cleanNumber, "type" => "text", "text" => ["body" => $message]];
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl, CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiToken],
        CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) return ['status' => 'GAGAL', 'msg' => "cURL Error: $curlError"];
    if ($httpCode === 200) return ['status' => 'TERKIRIM', 'msg' => 'Sukses API'];
    return ['status' => 'GAGAL', 'msg' => "API Code: $httpCode"];
}

function classifyMessage($message) {
    if (empty($message)) return 'Data CSV/Manual';
    $m = strtolower($message);
    
    if (strpos($m, 'bingung mau pilih program') !== false || strpos($m, 'saya bingung') !== false) return 'Bingung';
    if (strpos($m, 'ziyadah pemula') !== false) return 'Ziyadah Pemula';
    if (strpos($m, 'ziyadah lanjutan') !== false) return 'Ziyadah Lanjutan';
    if (strpos($m, "muroja'ah") !== false || strpos($m, 'murojaah') !== false) return "Muroja'ah";
    if (strpos($m, 'tahfidz cilik') !== false) return 'Tahfidz Cilik';
    if (strpos($m, 'intensif') !== false) return 'Mode Intensif';
    if (strpos($m, 'normal') !== false) return 'Mode Normal';
    if (strpos($m, 'kak, mau') !== false || strpos($m, 'mau ikut') !== false || strpos($m, 'minat') !== false) return 'Ekspresi Minat';
    if (strpos($m, 'input manual') !== false || strpos($m, 'csv') !== false) return 'Data CSV/Manual';
    return 'Lainnya'; 
}

function getDisqualifiedNumbers($conn) {
    $disq = [];
    if (!$conn) return $disq;
    $tables = ['peserta', 'calon_peserta', 'pengampu'];
    foreach ($tables as $tbl) {
        $res = $conn->query("SHOW TABLES LIKE '$tbl'");
        if ($res && $res->num_rows > 0) {
            $q = $conn->query("SELECT nowa FROM $tbl WHERE nowa IS NOT NULL AND nowa != ''");
            if ($q) while($r = $q->fetch_assoc()) { 
                $n = preg_replace('/\D/', '', $r['nowa']); 
                if(strpos($n,'0')===0) $n = '62'.substr($n,1); 
                if($n) $disq[$n] = true; 
            }
        }
    }
    $q2 = $conn->query("SELECT nowa FROM log_wa WHERE is_form_sent = 1 OR message LIKE '%penempatan halaqoh%'");
    if ($q2) while($r = $q2->fetch_assoc()) {
        $n = preg_replace('/\D/', '', $r['nowa']); 
        if(strpos($n,'0')===0) $n = '62'.substr($n,1); 
        if($n) $disq[$n] = true;
    }
    return $disq;
}

function getBlockedNumbers($conn) {
    $blk = [];
    if(!$conn) return $blk;
    $q = $conn->query("SELECT nowa FROM blocked_peserta");
    if ($q) while($r = $q->fetch_assoc()) {
        $n = preg_replace('/\D/', '', $r['nowa']); 
        if(strpos($n,'0')===0) $n = '62'.substr($n,1); 
        if($n) $blk[$n] = true;
    }
    return $blk;
}

function isAlreadyInOrganic($conn, $number) {
    $n = preg_replace('/\D/', '', $number);
    if(strpos($n,'0')===0) $n = '62'.substr($n,1);
    $check = $conn->prepare("SELECT id FROM log_wa WHERE (nowa = ? OR nowa = ?) AND message != 'Data CSV/Manual' AND message != '' LIMIT 1");
    $check->bind_param("ss", $number, $n);
    $check->execute();
    return $check->get_result()->num_rows > 0;
}

// --- 1. HANDLE AJAX KIRIM ---
if (isset($_POST['ajax_send'])) {
    header('Content-Type: application/json');
    $cid = $_POST['contact_id'] ?? '';
    $tmplId = $_POST['template_id'] ?? '';

    $stT = $conn->prepare("SELECT name, content FROM poloap_templates WHERE id = ?");
    $stT->bind_param("i", $tmplId); $stT->execute(); 
    $tmplData = $stT->get_result()->fetch_assoc();
    $msgTmpl = $tmplData['content'] ?? '';
    $tmplName = $tmplData['name'] ?? '';

    if($msgTmpl && $cid) {
        $isForm = (stripos($msgTmpl, 'penempatan halaqoh') !== false) ? 1 : 0;
        
        // EKSTRAK NAMA DARI DB & MESSAGE UNTUK REPLACE TEMPLATE
        $stN = $conn->prepare("SELECT nama, message FROM log_wa WHERE nowa = ? LIMIT 1");
        $stN->bind_param("s", $cid); $stN->execute(); 
        $dbRow = $stN->get_result()->fetch_assoc();
        
        $nama = trim($dbRow['nama'] ?? 'Kak');
        $msgText = $dbRow['message'] ?? '';
        
        // Deteksi nama di teks pesan
        if (preg_match('/nama saya\s+\*?([^\*\(\n]+)\*?\s*\(/i', $msgText, $m)) {
            $nama = trim($m[1]);
        } elseif (preg_match('/nama saya\s+\*?([^\*\(\n]+)\*?/i', $msgText, $m)) {
            $nama = trim($m[1]);
        }
        if (empty($nama) || strtolower($nama) == 'kak') $nama = 'Kak';

        // Ganti [nama], [NAMA], {nama}, {NAMA}
        $pesan = str_ireplace(['[nama]', '[NAMA]', '{nama}', '{NAMA}'], $nama, $msgTmpl);
        $pesan = str_replace('  ', ' ', $pesan); // Rapikan spasi
        
        $hasilAPI = kirimPesan($cid, $pesan, $apiUrl, $apiToken);
        
        if($hasilAPI['status'] === 'TERKIRIM') {
            $tmplNameSafe = $conn->real_escape_string($tmplName);
            $conn->query("UPDATE log_wa SET last_followup_at = NOW(), is_form_sent = GREATEST(is_form_sent, $isForm), last_template_name = '$tmplNameSafe' WHERE nowa = '$cid'");
            if (!in_array($cid, $_SESSION['followed_up_today'])) $_SESSION['followed_up_today'][] = $cid;
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $hasilAPI['msg']]);
        }
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Template atau kontak tidak valid']);
    }
    exit;
}

// AMBIL DAFTAR HITAM
$disqualified = getDisqualifiedNumbers($conn);
$blocked = getBlockedNumbers($conn);

// --- 2. FUNGSI POST BIASA (PROTEKSI INPUT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_send'])) {
    if (isset($_POST['tambah_prospek'])) {
        $n = preg_replace('/\D/', '', $_POST['nowa_baru']); if (strpos($n, '0') === 0) $n = '62' . substr($n, 1);
        if (isset($disqualified[$n]) || isset($blocked[$n])) {
            $_SESSION['notification'] = "❌ Gagal: Nomor sudah terdaftar / diblokir!"; $_SESSION['notificationType'] = 'error';
        } elseif (isAlreadyInOrganic($conn, $_POST['nowa_baru'])) {
            $_SESSION['notification'] = "⚠️ Nomor sudah ada di Daftar Organik. Manual dibatalkan."; $_SESSION['notificationType'] = 'warning';
        } else {
            $conn->query("INSERT INTO log_wa (nowa, nama, message, created_at) VALUES ('$n', '{$_POST['nama_baru']}', 'Data CSV/Manual', NOW())");
            $_SESSION['notification'] = "✅ Prospek manual ditambahkan!"; $_SESSION['notificationType'] = 'success';
        }
        header("Location: pesan.php"); exit;
    }

    if (isset($_POST['upload_csv']) && isset($_FILES['file_csv'])) {
        set_time_limit(0); 
        ini_set('memory_limit', '256M'); 

        if (($handle = fopen($_FILES['file_csv']['tmp_name'], "r")) !== FALSE) {
            $suksesUpload = 0; $ditolak = 0; $sudahOrganik = 0;
            $stmt = $conn->prepare("INSERT IGNORE INTO log_wa (nowa, nama, message, created_at) VALUES (?, ?, 'Data CSV/Manual', NOW())");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (strtolower($data[0]) === 'nama' || empty($data[0])) continue;
                $n = preg_replace('/\D/', '', $data[1]); if (strpos($n, '0') === 0) $n = '62' . substr($n, 1);
                
                if (isset($disqualified[$n]) || isset($blocked[$n])) { $ditolak++; continue; }
                if (isAlreadyInOrganic($conn, $n)) { $sudahOrganik++; continue; }

                $stmt->bind_param("ss", $n, $data[0]); if($stmt->execute()) $suksesUpload++;
            }
            fclose($handle); 
            $msg = "✅ $suksesUpload Data CSV di-import.";
            if($sudahOrganik > 0) $msg .= " $sudahOrganik ditolak karena sudah masuk via Organik.";
            $_SESSION['notification'] = $msg; $_SESSION['notificationType'] = 'success';
        }
        header("Location: pesan.php"); exit;
    }

    if (isset($_POST['delete_prospect'])) { $conn->query("DELETE FROM log_wa WHERE nowa = '{$_POST['contact_id']}'"); header("Location: pesan.php"); exit; }
    if (isset($_POST['update_block_status'])) {
        if ($_POST['current_status'] === 'unblock') $conn->query("DELETE FROM blocked_peserta WHERE nowa = '{$_POST['contact_id']}'");
        else $conn->query("INSERT IGNORE INTO blocked_peserta (nowa) VALUES ('{$_POST['contact_id']}')");
        header("Location: pesan.php"); exit;
    }
    if (isset($_POST['hapus_semua_blokir'])) { $conn->query("TRUNCATE TABLE blocked_peserta"); header("Location: pesan.php"); exit; }
    if (isset($_POST['clear_fu'])) { $_SESSION['followed_up_today'] = []; header("Location: pesan.php"); exit; }
}

// ==================================================================
// DATA FETCHING & FILTER (DENGAN AUTO-EKSTRAK NAMA & GENDER)
// ==================================================================
$jsTemplates = []; $pesanTemplates = [];
$res = $conn->query("SELECT id, name, content FROM poloap_templates ORDER BY name");
while($r = $res->fetch_assoc()) { $pesanTemplates[] = $r; $jsTemplates[$r['id']] = $r['content']; }

$search = $_GET['search'] ?? ''; $f_start = $_GET['from'] ?? ''; $f_end = $_GET['to'] ?? ''; $f_minat = $_GET['minat'] ?? ''; 

$targetOrganik = []; $targetManual = []; $organikSudahDichat = []; $manualSudahDichat = [];
$processed = []; $statistikMinat = []; $idsToDelete = [];

$sql = "SELECT * FROM log_wa ORDER BY CASE WHEN (message = 'Data CSV/Manual' OR message = '') THEN 1 ELSE 0 END ASC, created_at DESC";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $raw_nowa = $row['nowa'];
    $n_log = preg_replace('/\D/', '', $raw_nowa);
    if(strpos($n_log,'0')===0) $n_log = '62'.substr($n_log,1);
    if(empty($n_log)) continue;

    if(isset($processed[$n_log])) {
        if ($row['message'] === 'Data CSV/Manual') $idsToDelete[] = $row['id']; 
        continue;
    }
    $processed[$n_log] = true;

    if (isset($disqualified[$n_log]) || isset($blocked[$n_log])) continue;
    if ($f_start && date('Y-m-d', strtotime($row['created_at'])) < $f_start) continue;
    if ($f_end && date('Y-m-d', strtotime($row['created_at'])) > $f_end) continue;
    
    if ($search) {
        $s = strtolower($search);
        if (strpos(strtolower($row['nama']), $s) === false && strpos(strtolower($row['nowa']), $s) === false) continue;
    }

    $klas = classifyMessage($row['message']);
    if ($klas === 'Lainnya') continue;

    if (!isset($statistikMinat[$klas])) $statistikMinat[$klas] = 0;
    $statistikMinat[$klas]++;
    if ($f_minat && $klas !== $f_minat) continue;

    $is_fu_today = (in_array($raw_nowa, $_SESSION['followed_up_today']) || in_array($n_log, $_SESSION['followed_up_today']));

    // === EKSTRAKSI NAMA DAN GENDER OTOMATIS ===
    $namaDb = trim($row['nama']);
    $msgRaw = $row['message'] ?? '';
    $extractedName = '';
    $extractedGender = '-';

    // Cari Nama
    if (preg_match('/nama saya\s+\*?([^\*\(\n]+)\*?\s*\(/i', $msgRaw, $m)) {
        $extractedName = trim($m[1]);
    } elseif (preg_match('/nama saya\s+\*?([^\*\(\n]+)\*?/i', $msgRaw, $m)) {
        $extractedName = trim($m[1]);
    }

    // Cari Gender
    if (preg_match('/\((ikhwan|akhwat|laki-laki|perempuan|laki|pr)\)/i', $msgRaw, $m)) {
        $extractedGender = ucfirst(strtolower(trim($m[1])));
    }

    $finalName = !empty($extractedName) ? $extractedName : (!empty($namaDb) ? $namaDb : 'Hamba Allah');

    $data = [
        'id' => $row['id'],
        'nowa' => $row['nowa'], 
        'clean_wa' => $n_log, 
        'nama' => $finalName, 
        'gender' => $extractedGender,
        'klas' => $klas,
        'fu_text' => $row['last_followup_at'] ? date('d/m H:i', strtotime($row['last_followup_at'])) : 'Belum Pernah',
        'fu_tmpl' => $row['last_template_name'] ? $row['last_template_name'] : 'Baru'
    ];

    if ($klas === 'Data CSV/Manual') {
        if ($is_fu_today) $manualSudahDichat[] = $data; else $targetManual[] = $data;
    } else {
        if ($is_fu_today) $organikSudahDichat[] = $data; else $targetOrganik[] = $data;
    }
}

if (!empty($idsToDelete)) $conn->query("DELETE FROM log_wa WHERE id IN (" . implode(',', $idsToDelete) . ")");

// EXPORT CSV LENGKAP
if (isset($_GET['export_csv_action'])) {
    header('Content-Type: text/csv; charset=utf-8'); 
    header('Content-Disposition: attachment; filename=Prospek_Filtered.csv');
    $output = fopen('php://output', 'w'); 
    fputcsv($output, ['Nama', 'Gender', 'Nomor WA', 'Minat', 'Tgl Follow-up Terakhir', 'Template Terakhir']);
    
    // Gabungkan Organik dan Manual untuk export
    $allExportData = array_merge($targetOrganik, $targetManual);
    foreach($allExportData as $r) { 
        fputcsv($output, [$r['nama'], $r['gender'], $r['nowa'], $r['klas'], $r['fu_text'], $r['fu_tmpl']]); 
    }
    fclose($output); exit;
}
?>

<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follow-Up Module | JWD Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Raleway', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0c0f1d; color: #f1f5f9; overflow-x: hidden; }
        
        /* Custom Scrollbar for inner components */
        .custom-scroll { max-height: 400px; overflow-y: auto; scrollbar-width: thin; }
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #1e264a; border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #2a3562; }

        /* Solid Box Containers */
        .solid-box { background-color: #11162d; border: 1px solid rgba(30, 38, 74, 0.7); border-radius: 24px; }
        
        /* Smooth Transitions & Animations */
        .smooth-trans { transition: all 300ms cubic-bezier(0.25, 0.8, 0.25, 1); }
        .animate-fade-up { opacity: 0; animation: fadeUpReveal 500ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
        @keyframes fadeUpReveal {
            0% { opacity: 0; transform: translateY(15px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .delay-1 { animation-delay: 100ms; }
        .delay-2 { animation-delay: 200ms; }
        .delay-3 { animation-delay: 300ms; }

        /* WA Dark Mode Preview Styling */
        #waPreview { display: none; position: fixed; bottom: 20px; right: 20px; width: 320px; z-index: 100; border-radius: 16px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5); border: 1px solid #202c33; }
        .wa-header { background: #202c33; color: #e9edef; padding: 12px; display: flex; align-items: center; justify-content: space-between; }
        .wa-body { background: #0b141a; padding: 15px; position: relative; }
        /* WA Doodle Overlay */
        .wa-body::before { content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); opacity: 0.06; pointer-events: none; }
        .wa-bubble { position: relative; background: #005c4b; padding: 8px 12px; border-radius: 0 10px 10px 10px; font-size: 13px; color: #e9edef; box-shadow: 0 1px 1px rgba(0,0,0,0.2); width: fit-content; max-width: 90%; }
        
        /* Accessibility */
        *:focus-visible { transition: none !important; outline: 2px solid #6366f1; outline-offset: 2px; }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
    </style>
</head>
<body class="p-4 md:p-6 lg:p-8">

<div class="max-w-[1500px] mx-auto relative space-y-6">
    
    <header class="solid-box py-5 px-6 flex flex-wrap justify-between items-center gap-4 shadow-xl animate-fade-up">
        <div class="flex items-center gap-4">
            <div class="bg-indigo-500/10 border border-indigo-500/20 p-3 rounded-2xl text-indigo-400 shadow-inner">
                <i class="ph-fill ph-rocket-launch text-2xl"></i>
            </div>
            <div>
                <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-wide uppercase">Broadcast & Follow Up</h1>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mt-0.5">Control Engine System</p>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <a href="grafik.php" class="smooth-trans px-4 py-2.5 rounded-xl text-xs font-bold flex items-center bg-[#171e3d] text-slate-300 hover:text-white hover:bg-[#1e264a] border border-slate-700/50">
                <i class="ph-bold ph-chart-bar mr-2 text-sm text-indigo-400"></i>Statistik
            </a>
            <button onclick="openModal('modalTambah')" class="smooth-trans px-4 py-2.5 rounded-xl text-xs font-bold flex items-center bg-[#171e3d] text-slate-300 hover:text-white hover:bg-[#1e264a] border border-slate-700/50">
                <i class="ph-bold ph-user-plus mr-2 text-sm text-emerald-400"></i>Manual
            </button>
            <button onclick="openModal('modalCSV')" class="smooth-trans px-4 py-2.5 rounded-xl text-xs font-bold flex items-center bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/20 border border-indigo-500">
                <i class="ph-bold ph-file-csv mr-2 text-sm"></i>Upload CSV
            </button>
            <a href="?export_csv_action=1&search=<?=urlencode($search)?>&from=<?=urlencode($f_start)?>&to=<?=urlencode($f_end)?>&minat=<?=urlencode($f_minat)?>" class="smooth-trans px-4 py-2.5 rounded-xl text-xs font-bold flex items-center bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-500/20 border border-emerald-500">
                <i class="ph-bold ph-download mr-2 text-sm"></i>Export Filter
            </a>
            <a href="wa.php?logout=true" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="smooth-trans px-4 py-2.5 rounded-xl text-xs font-bold flex items-center bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 border border-rose-500/20">
                <i class="ph-bold ph-sign-out mr-2 text-sm"></i>Logout
            </a>
        </div>
    </header>

    <div id="loader" class="hidden solid-box overflow-hidden relative shadow-2xl animate-fade-up">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
        <div class="p-6 flex flex-col sm:flex-row items-center gap-6">
            <div class="bg-[#171e3d] w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 border border-slate-700/50 shadow-inner">
                <i class="ph-bold ph-paper-plane-tilt text-2xl text-indigo-400 animate-pulse"></i>
            </div>
            <div class="flex-1 w-full">
                <div class="flex justify-between items-end mb-3">
                    <div>
                        <h3 class="font-extrabold text-white text-base">Broadcast Engine Active...</h3>
                        <p id="progressStatus" class="text-[11px] font-medium text-slate-400 mt-0.5">Menautkan request ke API server...</p>
                    </div>
                    <p id="progressText" class="text-xl font-black text-indigo-400">0%</p>
                </div>
                <div class="w-full bg-[#171e3d] rounded-full h-2.5 overflow-hidden relative border border-slate-800">
                    <div id="progressBar" class="bg-gradient-to-r from-indigo-500 to-purple-500 h-full rounded-full transition-all duration-300 w-0"></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($notification): ?>
    <div class="p-4 rounded-2xl border <?= $notificationType === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : ($notificationType === 'warning' ? 'bg-amber-500/10 border-amber-500/20 text-amber-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-400') ?> flex items-center gap-3 font-bold text-xs shadow-sm animate-fade-up">
        <i class="<?= $notificationType === 'success' ? 'ph-fill ph-check-circle text-emerald-400' : ($notificationType === 'warning' ? 'ph-fill ph-warning text-amber-400' : 'ph-fill ph-x-circle text-rose-400') ?> text-xl"></i> 
        <span><?= $notification ?></span>
    </div>
    <?php endif; ?>

    <?php if(!empty($statistikMinat)): 
        $gradients = [
            'from-indigo-600 to-blue-600', 'from-emerald-600 to-teal-600', 
            'from-amber-600 to-orange-600', 'from-purple-600 to-fuchsia-600', 
            'from-rose-600 to-pink-600', 'from-cyan-600 to-blue-600'
        ];
        $i = 0;
    ?>
    <div class="animate-fade-up delay-1">
        <div class="flex justify-between items-end mb-3 px-1">
            <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Segmen Prospek Aktif</h2>
            <?php if($f_minat): ?>
            <a href="pesan.php" class="text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 px-3 py-1 rounded-full font-bold hover:bg-rose-500/20 transition-colors flex items-center"><i class="ph-bold ph-x mr-1"></i> Hapus Filter</a>
            <?php endif; ?>
        </div>
        <div class="flex gap-4 overflow-x-auto pb-4 custom-scroll snap-x">
            <?php foreach($statistikMinat as $namaMinat => $jumlah): 
                $grad = $gradients[$i % count($gradients)]; $i++;
                $isActive = ($f_minat === $namaMinat) ? 'ring-2 ring-white ring-offset-2 ring-offset-[#0c0f1d] scale-[1.02]' : 'opacity-90 hover:opacity-100 hover:scale-[1.02]';
            ?>
            <a href="?minat=<?= urlencode($namaMinat) ?>" class="bg-gradient-to-br <?= $grad ?> rounded-2xl flex-shrink-0 min-w-[160px] block transition-all shadow-lg cursor-pointer text-white snap-center relative overflow-hidden <?= $isActive ?>" title="Saring data <?= $namaMinat ?>">
                <div class="absolute -right-6 -top-6 w-20 h-20 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
                <div class="px-5 py-4 h-full flex flex-col justify-between relative z-10">
                    <div class="text-[10px] font-extrabold uppercase tracking-widest mb-2 text-white/90 truncate drop-shadow-sm"><?= htmlspecialchars($namaMinat) ?></div>
                    <div class="text-3xl font-black drop-shadow-md"><?= $jumlah ?> <span class="text-[9px] font-bold opacity-80 tracking-widest uppercase">Leads</span></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 animate-fade-up delay-2">
        
        <div class="xl:col-span-3 space-y-6">
            
            <div class="solid-box p-5 bg-[#11162d]">
                <h3 class="font-extrabold text-white mb-4 flex items-center gap-2 text-sm uppercase tracking-wide">
                    <div class="bg-[#171e3d] p-1.5 rounded-lg border border-slate-700/50 text-indigo-400"><i class="ph-bold ph-magnifying-glass"></i></div>
                    Filter Data
                </h3>
                <form method="GET" class="space-y-4">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Nama / Nomor..." class="w-full px-4 py-3 bg-[#0a0d1a] border border-slate-700/50 rounded-xl text-slate-200 text-xs outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-500 smooth-trans">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] text-slate-500 font-bold uppercase mb-1">Mulai Tgl</label>
                            <input type="date" name="from" value="<?= htmlspecialchars($f_start) ?>" class="w-full text-xs p-2.5 bg-[#0a0d1a] border border-slate-700/50 text-slate-300 rounded-xl outline-none focus:border-indigo-500 smooth-trans">
                        </div>
                        <div>
                            <label class="block text-[9px] text-slate-500 font-bold uppercase mb-1">Sampai Tgl</label>
                            <input type="date" name="to" value="<?= htmlspecialchars($f_end) ?>" class="w-full text-xs p-2.5 bg-[#0a0d1a] border border-slate-700/50 text-slate-300 rounded-xl outline-none focus:border-indigo-500 smooth-trans">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-3 rounded-xl font-bold text-xs shadow-md smooth-trans uppercase tracking-widest">Terapkan Filter</button>
                    <a href="pesan.php" class="block text-center text-[10px] text-slate-500 hover:text-slate-300 font-bold tracking-wide smooth-trans">Reset Semua Form</a>
                </form>
            </div>
            
            <div class="solid-box p-5 bg-[#11162d]">
                <h3 class="font-extrabold text-white mb-4 flex items-center gap-2 text-sm uppercase tracking-wide">
                    <div class="bg-[#171e3d] p-1.5 rounded-lg border border-slate-700/50 text-rose-400"><i class="ph-bold ph-shield-check"></i></div>
                    Tools Admin
                </h3>
                <div class="space-y-3">
                    <form method="POST" onsubmit="return confirm('Kosongkan histori follow-up hari ini?')">
                        <input type="hidden" name="clear_fu" value="1">
                        <button type="submit" class="w-full bg-[#0a0d1a] hover:bg-[#171e3d] text-slate-400 hover:text-white py-3 rounded-xl text-[11px] font-bold border border-slate-700/50 smooth-trans flex items-center justify-center uppercase tracking-wider">
                            <i class="ph-bold ph-arrows-clockwise mr-2 text-sm text-amber-400"></i> Reset Histori Harian
                        </button>
                    </form>
                    <a href="manage_templates.php" class="flex w-full bg-[#0a0d1a] hover:bg-[#171e3d] text-slate-400 hover:text-white py-3 rounded-xl text-[11px] font-bold border border-slate-700/50 smooth-trans items-center justify-center uppercase tracking-wider">
                        <i class="ph-bold ph-chat-centered-text mr-2 text-sm text-emerald-400"></i> Kelola Template
                    </a>
                </div>
            </div>

            <?php if(!empty($organikSudahDichat) || !empty($manualSudahDichat)): ?>
            <div class="flex flex-col gap-6">
                <?php if(!empty($organikSudahDichat)): ?>
                <div class="solid-box overflow-hidden">
                    <div class="p-4 border-b border-slate-800/50 flex justify-between items-center bg-[#171e3d]/50">
                        <h3 class="font-extrabold text-[10px] text-emerald-400 uppercase tracking-widest flex items-center"><i class="ph-fill ph-check-circle mr-1.5 text-base"></i> Selesai Organik</h3>
                        <span class="text-[9px] font-bold text-slate-300 bg-[#0a0d1a] border border-slate-700/50 px-2 py-0.5 rounded-md"><?= count($organikSudahDichat) ?></span>
                    </div>
                    <div class="max-h-[300px] overflow-y-auto custom-scroll p-2">
                        <div class="space-y-2">
                            <?php foreach($organikSudahDichat as $r): ?>
                            <div class="bg-[#0a0d1a] border border-slate-800/50 rounded-xl p-3 smooth-trans hover:border-emerald-500/30">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="font-bold text-xs text-slate-200">
                                            <?= $r['nama'] ?>
                                            <?php if($r['gender'] !== '-'): ?>
                                                <span class="ml-1 text-[8px] px-1.5 py-0.5 rounded uppercase tracking-wider font-extrabold <?= strtolower($r['gender']) == 'ikhwan' || strtolower($r['gender']) == 'laki-laki' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-pink-500/10 text-pink-400 border border-pink-500/20' ?>"><?= $r['gender'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-[9px] text-slate-500 font-mono mt-0.5"><?= $r['nowa'] ?></div>
                                    </div>
                                </div>
                                <div class="text-[9px] text-emerald-400 font-bold mb-2 flex items-center"><i class="ph-bold ph-clock-counter-clockwise mr-1"></i><?= $r['fu_tmpl'] ?></div>
                                <form class="flex gap-2 w-full" onsubmit="submitSingleAjax(event, this)">
                                    <input type="hidden" name="contact_id" value="<?= $r['nowa'] ?>">
                                    <select name="template_id" class="bg-[#11162d] border border-slate-700/50 rounded-lg px-2 py-1.5 text-[10px] text-slate-300 outline-none focus:border-emerald-500 save-template flex-1" required>
                                        <option value="">Chat Ulang...</option>
                                        <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= $t['name'] ?></option><?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="bg-[#171e3d] border border-slate-700/50 text-emerald-400 px-3 py-1.5 rounded-lg text-sm font-bold hover:bg-emerald-600 hover:text-white smooth-trans"><i class="ph-bold ph-paper-plane-tilt"></i></button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($manualSudahDichat)): ?>
                <div class="solid-box overflow-hidden">
                    <div class="p-4 border-b border-slate-800/50 flex justify-between items-center bg-[#171e3d]/50">
                        <h3 class="font-extrabold text-[10px] text-amber-400 uppercase tracking-widest flex items-center"><i class="ph-bold ph-checks mr-1.5 text-base"></i> Selesai Manual</h3>
                        <span class="text-[9px] font-bold text-slate-300 bg-[#0a0d1a] border border-slate-700/50 px-2 py-0.5 rounded-md"><?= count($manualSudahDichat) ?></span>
                    </div>
                    <div class="max-h-[300px] overflow-y-auto custom-scroll p-2">
                        <div class="space-y-2">
                            <?php foreach($manualSudahDichat as $r): ?>
                            <div class="bg-[#0a0d1a] border border-slate-800/50 rounded-xl p-3 smooth-trans hover:border-amber-500/30">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="font-bold text-xs text-slate-200"><?= $r['nama'] ?></div>
                                        <div class="text-[9px] text-slate-500 font-mono mt-0.5"><?= $r['nowa'] ?></div>
                                    </div>
                                </div>
                                <div class="text-[9px] text-amber-400 font-bold mb-2 flex items-center"><i class="ph-bold ph-clock-counter-clockwise mr-1"></i><?= $r['fu_tmpl'] ?></div>
                                <form class="flex gap-2 w-full" onsubmit="submitSingleAjax(event, this)">
                                    <input type="hidden" name="contact_id" value="<?= $r['nowa'] ?>">
                                    <select name="template_id" class="bg-[#11162d] border border-slate-700/50 rounded-lg px-2 py-1.5 text-[10px] text-slate-300 outline-none focus:border-amber-500 save-template flex-1" required>
                                        <option value="">Chat Ulang...</option>
                                        <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= $t['name'] ?></option><?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="bg-[#171e3d] border border-slate-700/50 text-amber-400 px-3 py-1.5 rounded-lg text-sm font-bold hover:bg-amber-500 hover:text-white smooth-trans"><i class="ph-bold ph-paper-plane-tilt"></i></button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="xl:col-span-9 space-y-6">
            
            <div class="solid-box p-6 flex flex-col md:flex-row gap-6 items-center bg-indigo-900/10 border-indigo-500/20">
                <div class="flex-1 flex items-center gap-4 w-full">
                    <div class="bg-indigo-500/20 border border-indigo-500/30 p-4 rounded-2xl text-indigo-400 shadow-inner">
                        <i class="ph-bold ph-megaphone text-2xl animate-pulse"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-white uppercase tracking-widest">Execute Mass Broadcast</h3>
                        <p class="text-[11px] font-bold text-slate-400 mt-1 uppercase tracking-wider">
                            <span id="countCheck" class="text-indigo-400 font-black bg-indigo-950 border border-indigo-500/30 px-2 py-0.5 rounded-md text-sm mx-1">0</span> Target Terpilih
                        </p>
                    </div>
                </div>
                <form id="formMassal" class="w-full md:w-auto flex flex-wrap md:flex-nowrap gap-3">
                    <select name="template_id_multi" onchange="showWA(this)" class="w-full md:w-56 bg-[#0a0d1a] border border-slate-700/50 rounded-xl px-4 py-3 text-xs font-bold text-slate-200 outline-none focus:border-indigo-500 smooth-trans save-template" required>
                        <option value="">-- Pilih Template --</option>
                        <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= $t['name'] ?></option><?php endforeach; ?>
                    </select>
                    <button type="button" onclick="submitMassAjax(event)" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-500 px-6 py-3 rounded-xl font-extrabold text-xs text-white uppercase tracking-widest shadow-lg shadow-indigo-500/20 smooth-trans active:scale-95 flex items-center justify-center">
                        <i class="ph-bold ph-paper-plane-tilt mr-2 text-sm"></i> Kirim
                    </button>
                </form>
            </div>

            <div class="solid-box overflow-hidden">
                <div class="p-5 bg-[#171e3d]/30 border-b border-slate-800/50 flex justify-between items-center sticky top-0 z-10 backdrop-blur-md">
                    <h3 class="font-extrabold text-sm text-white flex items-center gap-2 uppercase tracking-wide">
                        <i class="ph-fill ph-leaf text-emerald-400"></i> New Organik Leads 
                        <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-0.5 rounded-md text-[10px] font-black ml-2"><?= count($targetOrganik) ?></span>
                    </h3>
                    <label class="text-[10px] font-extrabold text-slate-400 cursor-pointer uppercase tracking-widest hover:text-white smooth-trans flex items-center">
                        <input type="checkbox" id="checkAllOrganik" class="mr-2 accent-indigo-500 w-4 h-4 rounded bg-[#0a0d1a] border-slate-700"> Pilih Semua
                    </label>
                </div>
                <div class="custom-scroll">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-500 uppercase font-black text-[9px] tracking-widest bg-[#0a0d1a]/50 border-b border-slate-800/50">
                            <tr>
                                <th class="p-4 w-12 text-center">#</th>
                                <th class="p-4">Identitas Prospek</th>
                                <th class="p-4">Riwayat Terakhir</th>
                                <th class="p-4 text-right">Tindakan Khusus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/50">
                            <?php foreach($targetOrganik as $r): ?>
                            <tr class="hover:bg-[#171e3d]/40 bg-transparent group smooth-trans">
                                <td class="p-4 text-center">
                                    <input type="checkbox" value="<?= $r['nowa'] ?>" class="cb-target cb-organik w-4 h-4 accent-indigo-500 bg-[#0a0d1a] border-slate-700 rounded">
                                </td>
                                <td class="p-4">
                                    <div class="font-extrabold text-slate-200 text-sm mb-1">
                                        <?= $r['nama'] ?>
                                        <?php if($r['gender'] !== '-'): ?>
                                            <span class="ml-1 text-[9px] px-1.5 py-0.5 rounded font-black uppercase tracking-widest <?= strtolower($r['gender']) == 'ikhwan' || strtolower($r['gender']) == 'laki-laki' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-pink-500/10 text-pink-400 border border-pink-500/20' ?>"><?= $r['gender'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mb-2 flex items-center">
                                        <?= $r['nowa'] ?> 
                                        <a href="https://wa.me/<?= $r['clean_wa'] ?>" target="_blank" class="text-[#25D366] ml-2 hover:scale-110 smooth-trans" title="Buka WhatsApp Web"><i class="ph-fill ph-whatsapp-logo text-sm"></i></a>
                                    </div>
                                    <span class="bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest"><?= $r['klas'] ?></span>
                                </td>
                                <td class="p-4 align-top">
                                    <?php if($r['fu_tmpl'] === 'Baru'): ?>
                                        <span class="text-emerald-400 text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 px-2 py-1 rounded border border-emerald-500/20">Leads Baru (Fresh)</span>
                                    <?php else: ?>
                                        <div class="text-[10px] font-bold text-slate-300 bg-[#0a0d1a] inline-block px-2 py-1 rounded border border-slate-700/50 mb-1.5 flex items-center w-fit"><i class="ph-bold ph-clock-counter-clockwise text-slate-500 mr-1.5"></i> <?= $r['fu_tmpl'] ?></div>
                                        <div class="text-[9px] text-slate-500 font-bold uppercase tracking-wider flex items-center"><i class="ph-bold ph-clock mr-1 text-sm"></i><?= $r['fu_text'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-right pr-6 align-middle">
                                    <div class="flex gap-2 justify-end">
                                        <form class="flex gap-2" onsubmit="submitSingleAjax(event, this)">
                                            <input type="hidden" name="contact_id" value="<?= $r['nowa'] ?>">
                                            <select name="template_id" class="bg-[#0a0d1a] border border-slate-700/50 rounded-lg p-2 text-[10px] w-32 font-bold text-slate-300 outline-none focus:border-indigo-500 save-template" required>
                                                <option value="">Kirim...</option>
                                                <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= $t['name'] ?></option><?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="bg-[#171e3d] text-indigo-400 border border-slate-700/50 p-2 rounded-lg hover:bg-indigo-600 hover:text-white hover:border-indigo-500 smooth-trans text-sm"><i class="ph-bold ph-paper-plane-tilt"></i></button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Hapus prospek ini secara permanen?')" class="inline">
                                            <input type="hidden" name="delete_prospect" value="1">
                                            <input type="hidden" name="contact_id" value="<?= $r['nowa'] ?>">
                                            <button type="submit" class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-2 rounded-lg hover:bg-rose-600 hover:text-white smooth-trans text-sm" title="Hapus Permanen"><i class="ph-bold ph-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="solid-box overflow-hidden border-amber-500/20">
                <div class="p-5 bg-amber-900/10 border-b border-amber-500/20 flex justify-between items-center sticky top-0 z-10 backdrop-blur-md">
                    <h3 class="font-extrabold text-sm text-white flex items-center gap-2 uppercase tracking-wide">
                        <i class="ph-bold ph-database text-amber-400"></i> Antrean Manual & CSV 
                        <span class="bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2.5 py-0.5 rounded-md text-[10px] font-black ml-2"><?= count($targetManual) ?></span>
                    </h3>
                    <label class="text-[10px] font-extrabold text-slate-400 cursor-pointer uppercase tracking-widest hover:text-white smooth-trans flex items-center">
                        <input type="checkbox" id="checkAllManual" class="mr-2 accent-amber-500 w-4 h-4 rounded bg-[#0a0d1a] border-slate-700"> Pilih Semua
                    </label>
                </div>
                <div class="custom-scroll">
                    <table class="w-full text-left text-xs">
                        <thead class="text-amber-500/50 uppercase font-black text-[9px] tracking-widest bg-[#0a0d1a]/50 border-b border-amber-500/20">
                            <tr>
                                <th class="p-4 w-12 text-center">#</th>
                                <th class="p-4">Identitas Prospek</th>
                                <th class="p-4">Riwayat Terakhir</th>
                                <th class="p-4 text-right">Tindakan Khusus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-500/10">
                            <?php foreach($targetManual as $r): ?>
                            <tr class="hover:bg-amber-900/10 bg-transparent smooth-trans">
                                <td class="p-4 text-center">
                                    <input type="checkbox" value="<?= $r['nowa'] ?>" class="cb-target cb-manual w-4 h-4 accent-amber-500 bg-[#0a0d1a] border-slate-700 rounded">
                                </td>
                                <td class="p-4">
                                    <div class="font-extrabold text-slate-200 text-sm mb-1"><?= $r['nama'] ?></div>
                                    <div class="text-[10px] text-slate-400 font-mono flex items-center">
                                        <?= $r['nowa'] ?> 
                                        <a href="https://wa.me/<?= $r['clean_wa'] ?>" target="_blank" class="text-[#25D366] ml-2 hover:scale-110 smooth-trans"><i class="ph-fill ph-whatsapp-logo text-sm"></i></a>
                                    </div>
                                </td>
                                <td class="p-4 align-top">
                                    <?php if($r['fu_tmpl'] === 'Baru'): ?>
                                        <span class="text-slate-500 text-[10px] font-bold italic uppercase tracking-wider">Belum ada riwayat</span>
                                    <?php else: ?>
                                        <div class="text-[10px] font-bold text-slate-300 bg-[#0a0d1a] inline-block px-2 py-1 rounded border border-slate-700/50 mb-1.5 flex items-center w-fit"><i class="ph-bold ph-clock-counter-clockwise text-amber-500/50 mr-1.5"></i> <?= $r['fu_tmpl'] ?></div>
                                        <div class="text-[9px] text-slate-500 font-bold uppercase tracking-wider flex items-center"><i class="ph-bold ph-clock mr-1 text-sm"></i><?= $r['fu_text'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-right pr-6 align-middle">
                                    <div class="flex gap-2 justify-end">
                                        <form class="flex gap-2" onsubmit="submitSingleAjax(event, this)">
                                            <input type="hidden" name="contact_id" value="<?= $r['nowa'] ?>">
                                            <select name="template_id" class="bg-[#0a0d1a] border border-amber-500/20 rounded-lg p-2 text-[10px] w-28 font-bold text-slate-300 outline-none focus:border-amber-500 save-template" required>
                                                <option value="">Kirim...</option>
                                                <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= $t['name'] ?></option><?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="bg-amber-500/10 text-amber-400 border border-amber-500/20 p-2 rounded-lg hover:bg-amber-500 hover:text-white hover:border-amber-500 smooth-trans text-sm"><i class="ph-bold ph-paper-plane-tilt"></i></button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Hapus prospek ini?')" class="inline">
                                            <input type="hidden" name="delete_prospect" value="1">
                                            <input type="hidden" name="contact_id" value="<?= $r['nowa'] ?>">
                                            <button type="submit" class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-2 rounded-lg hover:bg-rose-600 hover:text-white smooth-trans text-sm" title="Hapus"><i class="ph-bold ph-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="waPreview" onclick="closeWA()">
    <div class="wa-header">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-slate-700 rounded-full flex items-center justify-center"><i class="ph-fill ph-user text-slate-300"></i></div>
            <div>
                <p class="text-xs font-bold leading-tight">Prospek Preview</p>
                <p class="text-[9px] text-emerald-400 font-medium">Online</p>
            </div>
        </div>
        <button class="opacity-70 hover:opacity-100 p-1 smooth-trans"><i class="ph-bold ph-x text-lg"></i></button>
    </div>
    <div class="wa-body">
        <div class="wa-bubble font-sans" id="waText">...</div>
        <div class="text-[9px] text-right mt-1 opacity-70 text-[#e9edef]">Sekarang <i class="ph-bold ph-checks text-[#53bdeb] ml-1"></i></div>
    </div>
</div>

<div id="modalTambah" class="fixed inset-0 bg-[#0c0f1d]/90 backdrop-blur-sm z-[9999] hidden flex items-center justify-center p-4">
    <div class="solid-box bg-[#11162d] w-full max-w-sm overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-slate-800/50 font-extrabold flex justify-between items-center text-white bg-[#171e3d]/50 uppercase tracking-widest text-xs">
            <span>Input Prospek Manual</span>
            <button type="button" onclick="closeModal('modalTambah')" class="text-slate-500 hover:text-rose-400 smooth-trans"><i class="ph-bold ph-x text-lg"></i></button>
        </div>
        <form method="POST" class="p-8 space-y-5">
            <div>
                <label class="block text-[9px] text-slate-400 font-bold uppercase mb-2 tracking-widest">Nama Lengkap</label>
                <input type="text" name="nama_baru" placeholder="Ketik nama di sini..." required class="w-full p-3.5 bg-[#0a0d1a] border border-slate-700/50 rounded-xl text-xs text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/50 placeholder-slate-600 smooth-trans">
            </div>
            <div>
                <label class="block text-[9px] text-slate-400 font-bold uppercase mb-2 tracking-widest">Nomor WhatsApp</label>
                <input type="number" name="nowa_baru" placeholder="08..." required class="w-full p-3.5 bg-[#0a0d1a] border border-slate-700/50 rounded-xl text-xs text-slate-200 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/50 placeholder-slate-600 smooth-trans">
            </div>
            <div class="pt-2">
                <button type="submit" name="tambah_prospek" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3.5 rounded-xl font-extrabold shadow-lg shadow-emerald-500/20 text-xs uppercase tracking-widest smooth-trans">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="modalCSV" class="fixed inset-0 bg-[#0c0f1d]/90 backdrop-blur-sm z-[9999] hidden flex items-center justify-center p-4">
    <div class="solid-box bg-[#11162d] w-full max-w-sm overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-slate-800/50 font-extrabold flex justify-between items-center text-white bg-[#171e3d]/50 uppercase tracking-widest text-xs">
            <span>Upload Bulk CSV</span>
            <button type="button" onclick="closeModal('modalCSV')" class="text-slate-500 hover:text-rose-400 smooth-trans"><i class="ph-bold ph-x text-lg"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-8 space-y-6" onsubmit="showManualLoading()">
            <div class="bg-indigo-500/10 p-4 rounded-xl border border-indigo-500/20 text-[10px] text-indigo-400 flex items-start gap-3 leading-relaxed">
                <i class="ph-fill ph-info text-xl"></i>
                <p>Format Header Baris Pertama CSV WAJIB tertulis: <br><b class="text-white">Nama, WhatsApp</b></p>
            </div>
            <div>
                <input type="file" name="file_csv" accept=".csv" required class="w-full p-4 border-2 border-dashed border-slate-700/50 rounded-xl text-xs text-slate-400 cursor-pointer bg-[#0a0d1a] hover:border-indigo-500/50 smooth-trans file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-indigo-500/20 file:text-indigo-400 hover:file:bg-indigo-500/30">
            </div>
            <button type="submit" name="upload_csv" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-3.5 rounded-xl font-extrabold shadow-lg shadow-indigo-500/20 text-xs uppercase tracking-widest smooth-trans">Proses Upload</button>
        </form>
    </div>
</div>

<script>
// --- LOGIKA JS BAWAAN 100% AMAN TIDAK DIUBAH (HANYA MENGUBAH NAMA ICON) ---
const templates = <?= json_encode($jsTemplates) ?>;

function showWA(s) { 
    const p = document.getElementById('waPreview'), t = document.getElementById('waText'); 
    if(s.value && templates[s.value]) { 
        t.innerText = templates[s.value].replace(/\[nama\]|\{nama\}/gi, '[Nama Prospek]'); 
        p.style.display = 'block'; 
    } else { p.style.display = 'none'; }
}

function closeWA() { document.getElementById('waPreview').style.display = 'none'; }
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function showManualLoading() { 
    const modal = document.getElementById('loader');
    modal.classList.remove('hidden');
    modal.classList.add('block');
    document.getElementById('progressStatus').innerText = "Memproses request...";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateCount() { document.getElementById('countCheck').innerText = document.querySelectorAll('.cb-target:checked').length; }

document.getElementById('checkAllOrganik')?.addEventListener('change', function() { document.querySelectorAll('.cb-organik').forEach(c => c.checked = this.checked); updateCount(); });
document.getElementById('checkAllManual')?.addEventListener('change', function() { document.querySelectorAll('.cb-manual').forEach(c => c.checked = this.checked); updateCount(); });
document.querySelectorAll('.cb-target').forEach(c => c.addEventListener('change', updateCount));

async function submitMassAjax(e) { 
    e.preventDefault();
    const sel = document.querySelectorAll('.cb-target:checked'); 
    const tmplId = document.querySelector('select[name="template_id_multi"]').value;
    
    if(!sel.length || !tmplId) { alert('Harap pilih prospek dan template!'); return; } 
    
    const modal = document.getElementById('loader'); 
    const pBar = document.getElementById('progressBar');
    const pText = document.getElementById('progressText');
    const pStat = document.getElementById('progressStatus');
    
    modal.classList.remove('hidden');
    modal.classList.add('block');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    let success = 0, fail = 0; let total = sel.length;

    for (let i = 0; i < total; i++) {
        let contactId = sel[i].value;
        pStat.innerText = `Mengirim ke ${contactId}... (${i+1}/${total})`;
        
        try {
            let formData = new FormData();
            formData.append('ajax_send', '1');
            formData.append('contact_id', contactId);
            formData.append('template_id', tmplId);

            let res = await fetch('', { method: 'POST', body: formData });
            let json = await res.json();
            if(json.status === 'success') success++; else fail++;
        } catch(err) { fail++; }
        
        let pct = Math.round(((i + 1) / total) * 100);
        pBar.style.width = pct + '%';
        pText.innerText = pct + '%';
        await new Promise(r => setTimeout(r, 100)); 
    }
    
    pStat.innerHTML = `<span class="text-emerald-400 font-bold">Selesai!</span> ${success} Sukses, ${fail} Gagal.`;
    setTimeout(() => location.reload(), 1500);
}

async function submitSingleAjax(e, form) {
    e.preventDefault();
    const contactId = form.querySelector('input[name="contact_id"]').value;
    const tmplId = form.querySelector('select[name="template_id"]').value;
    if(!tmplId) { alert('Pilih template!'); return; }
    const btn = form.querySelector('button[type="submit"]');
    const oriHtml = btn.innerHTML;
    // Ubah icon fa-spinner lama ke format Phosphor baru
    btn.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i>'; btn.disabled = true;
    
    try {
        let formData = new FormData();
        formData.append('ajax_send', '1');
        formData.append('contact_id', contactId);
        formData.append('template_id', tmplId);

        let res = await fetch('', { method: 'POST', body: formData });
        let json = await res.json();
        
        if(json.status === 'success') {
            btn.innerHTML = '<i class="ph-bold ph-check"></i>';
            btn.classList.replace('bg-[#171e3d]', 'bg-emerald-600');
            btn.classList.replace('text-indigo-400', 'text-white');
            btn.classList.replace('text-amber-400', 'text-white');
            btn.classList.replace('text-emerald-400', 'text-white');
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Gagal API: ' + json.msg);
            btn.innerHTML = oriHtml; btn.disabled = false;
        }
    } catch(err) {
        alert('Koneksi Error.');
        btn.innerHTML = oriHtml; btn.disabled = false;
    }
}
</script>
</body>
</html>