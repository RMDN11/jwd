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
$colors = ['bg-indigo-500', 'bg-blue-500', 'bg-emerald-500', 'bg-rose-500', 'bg-amber-500', 'bg-fuchsia-500', 'bg-cyan-500'];
$i = 0;
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Massal - JWD</title>
    
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
                        brand: {
                            dark: '#0a0d1a',
                            box: '#11162d',
                            border: '#1e264a'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Transparent Body Agar Menyatu Sempurna Dalam iframe Dashboard.php */
        body { background-color: transparent; color: #e2e8f0; overflow-x: hidden; }
        
        /* Animasi Emil Kowalski Style */
        .smooth-trans { transition: all 300ms cubic-bezier(0.25, 0.8, 0.25, 1); }
        .animate-fade-up { opacity: 0; animation: fadeUpReveal 500ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
        @keyframes fadeUpReveal { 0% { opacity: 0; transform: translateY(20px); filter: blur(3px); } 100% { opacity: 1; transform: translateY(0); filter: blur(0); } }
        
        /* Custom Scrollbar Tipis */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #1e264a; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #2d3765; }

        .delay-1 { animation-delay: 100ms; }
        .delay-2 { animation-delay: 200ms; }

        input, select, textarea, button { outline: none; }
        *:focus-visible { outline: 2px solid #6366f1; outline-offset: 2px; transition: none; }
    </style>
</head>
<body class="p-2 sm:p-4 font-sans antialiased h-full">

<?php if (!empty($notification)): ?>
<div class="mb-6 p-4 rounded-xl text-sm font-bold tracking-wide animate-fade-up <?php echo $notificationType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($notificationType === 'warning' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'); ?>">
    <?php echo htmlspecialchars($notification); ?>
</div>
<?php endif; ?>

<div class="max-w-7xl mx-auto space-y-6 pb-20">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-brand-box border border-brand-border rounded-2xl p-5 shadow-lg animate-fade-up">
        <div>
            <h2 class="text-lg font-extrabold text-white tracking-widest uppercase flex items-center">
                <i class="ph-bold ph-paper-plane-tilt text-indigo-500 mr-2 text-xl"></i>
                Follow-Up Engine
            </h2>
            <p class="text-xs text-slate-400 mt-1 font-medium tracking-wide">Kirim pesan massal dengan mudah dan cerdas.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <button onclick="document.getElementById('modalManual').classList.remove('hidden')" class="flex-1 md:flex-none bg-[#171e3d] border border-brand-border hover:bg-[#1f294f] text-indigo-400 hover:text-indigo-300 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider smooth-trans flex items-center justify-center shadow-sm">
                <i class="ph-bold ph-user-plus mr-2"></i> Manual
            </button>
            <button onclick="document.getElementById('modalCSV').classList.remove('hidden')" class="flex-1 md:flex-none bg-indigo-600/20 border border-indigo-500/30 hover:bg-indigo-600/40 text-indigo-400 px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider smooth-trans flex items-center justify-center shadow-sm">
                <i class="ph-bold ph-file-csv mr-2"></i> CSV
            </button>
        </div>
    </div>

    <?php if(!empty($statistikMinat)): ?>
    <div class="animate-fade-up delay-1">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest"><i class="ph-bold ph-chart-pie-slice mr-1"></i> Data Minat</h3>
            <?php if($f_minat): ?>
            <a href="pesan.php" class="text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 px-3 py-1 rounded-full font-bold hover:bg-rose-500/20 smooth-trans"><i class="ph-bold ph-x mr-1"></i> Reset Filter</a>
            <?php endif; ?>
        </div>
        <div class="flex gap-4 overflow-x-auto pb-2 snap-x custom-scroll">
            <?php foreach($statistikMinat as $namaMinat => $jumlah): 
                $c = $colors[$i % count($colors)]; $i++;
                $isActive = ($f_minat === $namaMinat) ? 'ring-2 ring-indigo-400 shadow-lg scale-105' : 'hover:-translate-y-1 opacity-80 hover:opacity-100';
            ?>
            <a href="?minat=<?= urlencode($namaMinat) ?>" class="<?= $c ?> rounded-2xl flex-shrink-0 w-36 block smooth-trans shadow-md cursor-pointer text-white snap-center <?= $isActive ?> p-4 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-20 group-hover:scale-110 smooth-trans"><i class="ph-fill ph-chart-donut text-6xl"></i></div>
                <div class="text-[10px] font-bold uppercase tracking-widest mb-3 text-white/90 truncate relative z-10"><?= htmlspecialchars($namaMinat) ?></div>
                <div class="text-3xl font-extrabold relative z-10"><?= $jumlah ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-fade-up delay-2">
        
        <div class="lg:col-span-3 space-y-6">
            
            <div class="bg-brand-box border border-brand-border rounded-2xl p-5 shadow-lg">
                <h3 class="font-extrabold text-white mb-4 text-xs tracking-widest uppercase flex items-center">
                    <i class="ph-bold ph-faders text-indigo-400 mr-2"></i> Filter Prospek
                </h3>
                <form method="GET" class="space-y-4">
                    <input type="hidden" name="minat" value="<?= htmlspecialchars($f_minat) ?>">
                    <div>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Nama/Nomor..." class="w-full bg-brand-dark border border-brand-border rounded-xl px-4 py-3 text-slate-300 text-sm smooth-trans focus:border-indigo-500 placeholder-slate-600">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Mulai Tanggal</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($f_start) ?>" class="w-full bg-brand-dark border border-brand-border rounded-xl px-4 py-3 text-slate-300 text-sm smooth-trans focus:border-indigo-500 [color-scheme:dark]">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Sampai Tanggal</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($f_end) ?>" class="w-full bg-brand-dark border border-brand-border rounded-xl px-4 py-3 text-slate-300 text-sm smooth-trans focus:border-indigo-500 [color-scheme:dark]">
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest smooth-trans shadow-md flex justify-center items-center"><i class="ph-bold ph-magnifying-glass mr-1"></i> Saring</button>
                        <a href="?export_csv_action=1&search=<?=urlencode($search)?>&from=<?=urlencode($f_start)?>&to=<?=urlencode($f_end)?>&minat=<?=urlencode($f_minat)?>" class="bg-[#171e3d] hover:bg-[#1f294f] text-slate-300 px-3 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest smooth-trans border border-brand-border" title="Export CSV Terfilter"><i class="ph-bold ph-download-simple"></i></a>
                    </div>
                </form>
            </div>

            <div class="bg-brand-box border border-brand-border rounded-2xl p-5 shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-extrabold text-white text-[10px] tracking-widest uppercase flex items-center"><i class="ph-bold ph-check-circle text-emerald-400 mr-1.5"></i> Selesai Organik</h3>
                    <span class="text-[9px] font-bold text-slate-300 bg-[#171e3d] border border-brand-border px-2 py-0.5 rounded-md"><?= count($organikSudahDichat) ?></span>
                </div>
                <div class="max-h-[300px] overflow-y-auto custom-scroll space-y-2 pr-2">
                    <?php if(empty($organikSudahDichat)): ?><div class="text-[11px] text-slate-600 italic text-center py-4">Belum ada.</div><?php endif; ?>
                    <?php foreach($organikSudahDichat as $r): ?>
                    <div class="bg-brand-dark border border-brand-border rounded-xl p-3">
                        <div class="font-bold text-xs text-emerald-400 mb-1"><?= htmlspecialchars($r['nama']) ?></div>
                        <div class="text-[9px] text-slate-500 font-mono mb-2"><?= htmlspecialchars($r['nowa']) ?></div>
                        <form class="flex gap-1.5 w-full" onsubmit="submitSingleAjax(event, this)">
                            <input type="hidden" name="contact_id" value="<?= htmlspecialchars($r['nowa']) ?>">
                            <select name="template_id" class="bg-brand-box border border-brand-border rounded-lg p-1.5 text-[9px] text-slate-300 flex-1 outline-none font-bold uppercase" required>
                                <option value="">Chat Ulang...</option>
                                <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-emerald-500/20 hover:bg-emerald-500/40 text-emerald-400 border border-emerald-500/30 px-2 rounded-lg smooth-trans"><i class="ph-bold ph-paper-plane-right"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-brand-box border border-brand-border rounded-2xl p-5 shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-extrabold text-white text-[10px] tracking-widest uppercase flex items-center"><i class="ph-bold ph-check-circle text-amber-400 mr-1.5"></i> Selesai Manual</h3>
                    <span class="text-[9px] font-bold text-slate-300 bg-[#171e3d] border border-brand-border px-2 py-0.5 rounded-md"><?= count($manualSudahDichat) ?></span>
                </div>
                <div class="max-h-[300px] overflow-y-auto custom-scroll space-y-2 pr-2">
                    <?php if(empty($manualSudahDichat)): ?><div class="text-[11px] text-slate-600 italic text-center py-4">Belum ada.</div><?php endif; ?>
                    <?php foreach($manualSudahDichat as $r): ?>
                    <div class="bg-brand-dark border border-brand-border rounded-xl p-3">
                        <div class="font-bold text-xs text-amber-400 mb-1"><?= htmlspecialchars($r['nama']) ?></div>
                        <div class="text-[9px] text-slate-500 font-mono mb-2"><?= htmlspecialchars($r['nowa']) ?></div>
                        <form class="flex gap-1.5 w-full" onsubmit="submitSingleAjax(event, this)">
                            <input type="hidden" name="contact_id" value="<?= htmlspecialchars($r['nowa']) ?>">
                            <select name="template_id" class="bg-brand-box border border-brand-border rounded-lg p-1.5 text-[9px] text-slate-300 flex-1 outline-none font-bold uppercase" required>
                                <option value="">Chat Ulang...</option>
                                <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-amber-500/20 hover:bg-amber-500/40 text-amber-400 border border-amber-500/30 px-2 rounded-lg smooth-trans"><i class="ph-bold ph-paper-plane-right"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <form method="POST" onsubmit="return confirm('Hapus semua history sesi pada hari ini?')">
                <button type="submit" name="clear_fu" class="w-full text-[10px] font-bold uppercase tracking-widest text-slate-500 hover:text-rose-400 smooth-trans py-2 flex items-center justify-center"><i class="ph-bold ph-trash mr-1.5"></i> Bersihkan Sesi History</button>
            </form>
        </div>

        <div class="lg:col-span-9 space-y-6">
            
            <div class="bg-[#171e3d] border border-indigo-500/30 rounded-2xl p-5 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center text-indigo-400">
                        <i class="ph-fill ph-megaphone text-xl animate-pulse"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-white text-sm uppercase tracking-widest">Kirim Broadcast Massal</h3>
                        <p class="text-[10px] text-indigo-300 font-bold tracking-widest uppercase mt-0.5"><span id="countCheck" class="bg-indigo-500 text-white px-1.5 py-0.5 rounded-md mr-1">0</span> Kontak Terpilih</p>
                    </div>
                </div>
                <form id="formMassal" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <select name="template_id_multi" class="bg-brand-dark border border-brand-border rounded-xl px-4 py-2.5 text-xs font-bold text-slate-300 outline-none w-full sm:w-48" required>
                        <option value="">-- Pilih Template --</option>
                        <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
                    </select>
                    <button type="button" onclick="submitMassAjax(event)" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest shadow-md smooth-trans flex items-center justify-center">
                        <i class="ph-bold ph-paper-plane-tilt mr-2"></i> Eksekusi
                    </button>
                </form>
            </div>

            <div class="bg-brand-box border border-brand-border rounded-2xl overflow-hidden shadow-lg">
                <div class="p-4 bg-[#141b3a] border-b border-brand-border flex justify-between items-center sticky top-0 z-10">
                    <h3 class="font-extrabold text-white text-xs uppercase tracking-widest flex items-center">
                        <i class="ph-fill ph-plant text-emerald-400 mr-2 text-base"></i> Leads Organik (Chat Masuk)
                        <span class="ml-3 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-md text-[10px]"><?= count($targetOrganik) ?></span>
                    </h3>
                    <label class="text-[10px] font-bold text-slate-400 hover:text-white cursor-pointer uppercase tracking-widest smooth-trans flex items-center">
                        <input type="checkbox" id="checkAllOrganik" class="mr-2 w-3.5 h-3.5 accent-emerald-500">Pilih Semua
                    </label>
                </div>
                <div class="overflow-x-auto custom-scroll">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="text-slate-500 uppercase font-extrabold text-[9px] tracking-widest border-b border-brand-border bg-brand-dark">
                            <tr><th class="p-4 w-10">Cek</th><th class="p-4">Info Prospek</th><th class="p-4">Kategori Minat</th><th class="p-4 text-center">Status Follow-Up</th><th class="p-4 text-right">Tindakan</th></tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border">
                            <?php if(empty($targetOrganik)): ?><tr><td colspan="5" class="p-8 text-center text-slate-500 italic">Data leads bersih, tidak ada antrean.</td></tr><?php endif; ?>
                            <?php foreach($targetOrganik as $r): ?>
                            <tr class="hover:bg-[#151c3b] smooth-trans text-slate-300">
                                <td class="p-4"><input type="checkbox" name="selected_contacts[]" value="<?= htmlspecialchars($r['nowa']) ?>" class="chk-mass w-3.5 h-3.5 accent-indigo-500" data-group="organik"></td>
                                <td class="p-4">
                                    <div class="font-bold text-white text-sm mb-1"><?= htmlspecialchars($r['nama']) ?></div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-mono text-slate-400"><?= htmlspecialchars($r['nowa']) ?></span>
                                        <?php if($r['gender'] !== '-'): ?>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded-md <?= strtolower($r['gender']) == 'ikhwan' || strtolower($r['gender']) == 'laki-laki' ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/30' : 'bg-pink-500/10 text-pink-400 border border-pink-500/30' ?> uppercase font-bold tracking-widest"><?= htmlspecialchars($r['gender']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="p-4"><span class="bg-[#171e3d] border border-brand-border text-slate-300 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-widest"><?= htmlspecialchars($r['klas']) ?></span></td>
                                <td class="p-4 text-center">
                                    <div class="text-[10px] font-bold uppercase tracking-widest <?= $r['fu_text']=='Belum Pernah' ? 'text-rose-400' : 'text-emerald-400' ?>"><?= $r['fu_text'] ?></div>
                                    <?php if($r['fu_text']!=='Belum Pernah'): ?><div class="text-[9px] text-slate-500 mt-1 truncate max-w-[150px] mx-auto"><?= htmlspecialchars($r['fu_tmpl']) ?></div><?php endif; ?>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form onsubmit="submitSingleAjax(event, this)" class="flex gap-2">
                                            <input type="hidden" name="contact_id" value="<?= htmlspecialchars($r['nowa']) ?>">
                                            <select name="template_id" class="bg-brand-dark border border-brand-border rounded-lg p-2 text-[10px] font-bold uppercase tracking-widest text-slate-300 outline-none w-28" required>
                                                <option value="">Pilih Chat...</option>
                                                <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="bg-indigo-500/20 hover:bg-indigo-500/40 text-indigo-400 border border-indigo-500/30 w-8 h-8 rounded-lg smooth-trans flex items-center justify-center"><i class="ph-bold ph-paper-plane-right"></i></button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus prospek ini secara permanen?')">
                                            <input type="hidden" name="delete_prospect" value="1"><input type="hidden" name="contact_id" value="<?= htmlspecialchars($r['nowa']) ?>">
                                            <button type="submit" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 w-8 h-8 rounded-lg smooth-trans flex items-center justify-center"><i class="ph-bold ph-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-brand-box border border-brand-border rounded-2xl overflow-hidden shadow-lg">
                <div class="p-4 bg-[#141b3a] border-b border-brand-border flex justify-between items-center sticky top-0 z-10">
                    <h3 class="font-extrabold text-white text-xs uppercase tracking-widest flex items-center">
                        <i class="ph-fill ph-database text-amber-400 mr-2 text-base"></i> Antrean Manual & CSV
                        <span class="ml-3 bg-amber-500/20 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded-md text-[10px]"><?= count($targetManual) ?></span>
                    </h3>
                    <label class="text-[10px] font-bold text-slate-400 hover:text-white cursor-pointer uppercase tracking-widest smooth-trans flex items-center">
                        <input type="checkbox" id="checkAllManual" class="mr-2 w-3.5 h-3.5 accent-amber-500">Pilih Semua
                    </label>
                </div>
                <div class="overflow-x-auto custom-scroll">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="text-slate-500 uppercase font-extrabold text-[9px] tracking-widest border-b border-brand-border bg-brand-dark">
                            <tr><th class="p-4 w-10">Cek</th><th class="p-4">Info Prospek</th><th class="p-4 text-center">Status Follow-Up</th><th class="p-4 text-right">Tindakan</th></tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border">
                            <?php if(empty($targetManual)): ?><tr><td colspan="4" class="p-8 text-center text-slate-500 italic">Tidak ada data manual atau CSV masuk.</td></tr><?php endif; ?>
                            <?php foreach($targetManual as $r): ?>
                            <tr class="hover:bg-[#151c3b] smooth-trans text-slate-300">
                                <td class="p-4"><input type="checkbox" name="selected_contacts[]" value="<?= htmlspecialchars($r['nowa']) ?>" class="chk-mass w-3.5 h-3.5 accent-indigo-500" data-group="manual"></td>
                                <td class="p-4">
                                    <div class="font-bold text-white text-sm mb-1"><?= htmlspecialchars($r['nama']) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400"><?= htmlspecialchars($r['nowa']) ?></div>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="text-[10px] font-bold uppercase tracking-widest <?= $r['fu_text']=='Belum Pernah' ? 'text-rose-400' : 'text-amber-400' ?>"><?= $r['fu_text'] ?></div>
                                    <?php if($r['fu_text']!=='Belum Pernah'): ?><div class="text-[9px] text-slate-500 mt-1 truncate max-w-[150px] mx-auto"><?= htmlspecialchars($r['fu_tmpl']) ?></div><?php endif; ?>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form onsubmit="submitSingleAjax(event, this)" class="flex gap-2">
                                            <input type="hidden" name="contact_id" value="<?= htmlspecialchars($r['nowa']) ?>">
                                            <select name="template_id" class="bg-brand-dark border border-brand-border rounded-lg p-2 text-[10px] font-bold uppercase tracking-widest text-slate-300 outline-none w-28" required>
                                                <option value="">Pilih Chat...</option>
                                                <?php foreach($pesanTemplates as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="bg-indigo-500/20 hover:bg-indigo-500/40 text-indigo-400 border border-indigo-500/30 w-8 h-8 rounded-lg smooth-trans flex items-center justify-center"><i class="ph-bold ph-paper-plane-right"></i></button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus prospek ini secara permanen?')">
                                            <input type="hidden" name="delete_prospect" value="1"><input type="hidden" name="contact_id" value="<?= htmlspecialchars($r['nowa']) ?>">
                                            <button type="submit" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 w-8 h-8 rounded-lg smooth-trans flex items-center justify-center"><i class="ph-bold ph-trash"></i></button>
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

<div id="modalManual" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 smooth-trans">
    <div class="bg-brand-box border border-brand-border w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden relative animate-fade-up">
        <div class="absolute top-4 right-4">
            <button onclick="document.getElementById('modalManual').classList.add('hidden')" class="text-slate-500 hover:text-white smooth-trans"><i class="ph-bold ph-x text-lg"></i></button>
        </div>
        <div class="p-6">
            <h3 class="text-white font-extrabold text-sm uppercase tracking-widest mb-1"><i class="ph-bold ph-user-plus text-indigo-400 mr-1.5"></i> Tambah Prospek</h3>
            <p class="text-[11px] text-slate-400 mb-6 font-medium">Ketikkan data prospek secara manual.</p>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Nama Lengkap</label>
                    <input type="text" name="nama_baru" required class="w-full bg-brand-dark border border-brand-border rounded-xl px-4 py-3 text-slate-200 text-sm focus:border-indigo-500 smooth-trans placeholder-slate-600" placeholder="Ketik nama...">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Nomor WhatsApp Aktif</label>
                    <input type="number" name="nowa_baru" required placeholder="Contoh: 081234567..." class="w-full bg-brand-dark border border-brand-border rounded-xl px-4 py-3 text-slate-200 text-sm focus:border-indigo-500 smooth-trans placeholder-slate-600">
                </div>
                <div class="pt-2">
                    <button type="submit" name="tambah_prospek" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-3.5 rounded-xl font-bold text-xs uppercase tracking-widest shadow-lg smooth-trans shadow-indigo-600/20">Simpan Prospek</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalCSV" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 smooth-trans">
    <div class="bg-brand-box border border-brand-border w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden relative animate-fade-up">
        <div class="absolute top-4 right-4">
            <button onclick="document.getElementById('modalCSV').classList.add('hidden')" class="text-slate-500 hover:text-white smooth-trans"><i class="ph-bold ph-x text-lg"></i></button>
        </div>
        <div class="p-6">
            <h3 class="text-white font-extrabold text-sm uppercase tracking-widest mb-1"><i class="ph-bold ph-file-csv text-indigo-400 mr-1.5"></i> Upload Data CSV</h3>
            <p class="text-[11px] text-slate-400 mb-6 font-medium">Format Wajib: Kolom A (Nama), Kolom B (Nomor WA).</p>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="border-2 border-dashed border-brand-border rounded-xl p-6 text-center bg-brand-dark hover:border-indigo-500 smooth-trans cursor-pointer flex flex-col items-center justify-center" onclick="document.getElementById('file_csv').click()">
                    <i class="ph-bold ph-upload-simple text-3xl text-indigo-500 mb-3 bg-indigo-500/10 p-3 rounded-full"></i>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest" id="fileName">Ketuk Untuk Memilih CSV</p>
                    <input type="file" name="file_csv" id="file_csv" accept=".csv" class="hidden" required onchange="document.getElementById('fileName').innerText = this.files[0].name">
                </div>
                <div class="pt-2">
                    <button type="submit" name="upload_csv" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-3.5 rounded-xl font-bold text-xs uppercase tracking-widest shadow-lg smooth-trans shadow-indigo-600/20">Mulai Proses Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="statusOverlay" class="fixed inset-0 bg-brand-dark/95 backdrop-blur-md z-[100] hidden flex flex-col items-center justify-center smooth-trans">
    <i class="ph-fill ph-paper-plane-tilt text-6xl text-indigo-500 animate-bounce mb-5 drop-shadow-[0_0_15px_rgba(99,102,241,0.5)]"></i>
    <h2 class="text-white font-extrabold text-xl tracking-widest uppercase mb-2">Engine Sedang Bekerja...</h2>
    <p id="processStatus" class="text-slate-400 font-mono text-xs tracking-wider">Menyiapkan pengiriman data ke server API...</p>
</div>

<script>
    // Menyimpan data template
    const templates = <?php echo json_encode($jsTemplates); ?>;
    
    // Logika Pemilihan Checkbox (Kalkulasi Otomatis)
    const checkboxes = document.querySelectorAll('.chk-mass');
    const countCheck = document.getElementById('countCheck');
    
    function updateCount() { 
        countCheck.innerText = document.querySelectorAll('.chk-mass:checked').length; 
    }
    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));

    document.getElementById('checkAllOrganik')?.addEventListener('change', function() {
        document.querySelectorAll('.chk-mass[data-group="organik"]').forEach(cb => cb.checked = this.checked); 
        updateCount();
    });
    document.getElementById('checkAllManual')?.addEventListener('change', function() {
        document.querySelectorAll('.chk-mass[data-group="manual"]').forEach(cb => cb.checked = this.checked); 
        updateCount();
    });

    // Mengirim 1 Pesan via AJAX
    async function submitSingleAjax(e, form) {
        e.preventDefault();
        let btn = form.querySelector('button'); 
        let icon = btn.innerHTML;
        let t = form.querySelector('select').value;
        
        if(!t) return alert("Pilih tipe chat / template terlebih dahulu!");
        
        btn.innerHTML = '<i class="ph-bold ph-spinner-gap animate-spin"></i>'; 
        btn.disabled = true;
        
        let fd = new FormData();
        fd.append('ajax_send', '1');
        fd.append('contact_id', form.contact_id.value);
        fd.append('template_id', t);

        try {
            let res = await fetch('', {method:'POST', body:fd});
            let json = await res.json();
            if(json.status === 'success') { 
                location.reload(); 
            } else { 
                alert('Gagal Terkirim: ' + json.msg); 
                btn.innerHTML = icon; btn.disabled = false; 
            }
        } catch(err) { 
            alert('Error pada koneksi API atau Server.'); 
            btn.innerHTML = icon; btn.disabled = false; 
        }
    }

    // Mengirim Broadcast Massal via AJAX
    async function submitMassAjax(e) {
        e.preventDefault();
        let sel = document.querySelectorAll('.chk-mass:checked');
        let tmpl = document.querySelector('select[name="template_id_multi"]').value;
        
        if (sel.length === 0) return alert('Centang minimal 1 prospek di dalam tabel terlebih dahulu!');
        if (!tmpl) return alert('Anda belum memilih jenis template untuk di-broadcast!');
        if (!confirm(`Tembakkan pesan ke ${sel.length} kontak terpilih sekarang?`)) return;

        const overlay = document.getElementById('statusOverlay');
        const pStat = document.getElementById('processStatus');
        overlay.classList.remove('hidden');

        let success = 0, fail = 0, total = sel.length;
        
        for (let i = 0; i < total; i++) {
            let cid = sel[i].value;
            pStat.innerText = `Menembakkan API ke ${cid}... (${i+1}/${total})`;
            try {
                let fd = new FormData();
                fd.append('ajax_send', '1');
                fd.append('contact_id', cid);
                fd.append('template_id', tmpl);
                let res = await fetch('', {method:'POST', body:fd});
                let json = await res.json();
                
                if(json.status === 'success') success++; else fail++;
            } catch(e) { fail++; }
            
            // Artificial delay (Anti-Spam Banned Rules) ~ 1.5 Detik per tembakan
            await new Promise(r => setTimeout(r, 1500));
        }
        
        pStat.innerText = `Eksekusi Selesai! Terkirim: ${success}, Gagal: ${fail}. Menyesuaikan tabel...`;
        setTimeout(() => location.reload(), 2000);
    }
</script>

</body>
</html>