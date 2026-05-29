<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Format 62 Converter</title>
    <style>
        /* 100% Offline: Gunakan font bawaan sistem OS */
        :root {
            --font-main: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-main);
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Background orbs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            animation: float 8s ease-in-out infinite;
        }
        body::before {
            width: 400px; height: 400px;
            background: rgba(108, 99, 255, 0.4);
            top: -100px; left: -100px;
        }
        body::after {
            width: 350px; height: 350px;
            background: rgba(255, 99, 195, 0.3);
            bottom: -80px; right: -80px;
            animation-delay: -4s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -30px) scale(1.05); }
        }

        .container {
            position: relative; z-index: 1;
            width: 100%; max-width: 520px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 36px 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .logo { text-align: center; margin-bottom: 28px; }
        .logo-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #6c63ff, #ff63c3);
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 24px; margin-bottom: 12px;
            box-shadow: 0 4px 20px rgba(108, 99, 255, 0.4);
        }
        .logo h1 { color: #fff; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
        .logo p { color: rgba(255, 255, 255, 0.5); font-size: 13px; margin-top: 4px; font-weight: 300; }

        .tabs {
            display: flex; background: rgba(255, 255, 255, 0.06);
            border-radius: 14px; padding: 4px; margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .tab-btn {
            flex: 1; padding: 10px 16px; border: none; background: transparent;
            color: rgba(255, 255, 255, 0.45); font-family: var(--font-main);
            font-size: 13px; font-weight: 500; cursor: pointer;
            border-radius: 11px; transition: all 0.3s ease;
        }
        .tab-btn.active {
            background: rgba(108, 99, 255, 0.35); color: #fff;
            box-shadow: 0 2px 12px rgba(108, 99, 255, 0.3);
        }
        .tab-btn:hover:not(.active) { color: rgba(255, 255, 255, 0.7); }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        label { display: block; color: rgba(255, 255, 255, 0.6); font-size: 12px; font-weight: 500; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.8px; }

        input[type="text"], textarea {
            width: 100%; background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 14px;
            padding: 14px 16px; color: #fff; font-family: var(--font-main);
            font-size: 15px; transition: all 0.3s ease; outline: none;
        }
        input[type="text"]:focus, textarea:focus {
            border-color: rgba(108, 99, 255, 0.5); background: rgba(255, 255, 255, 0.09);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.15);
        }
        input[type="text"]::placeholder, textarea::placeholder { color: rgba(255, 255, 255, 0.25); }
        .has-prefix { padding-left: 52px; }
        textarea { resize: vertical; min-height: 140px; line-height: 1.8; font-size: 14px; }

        .btn {
            width: 100%; padding: 14px; border: none; border-radius: 14px;
            font-family: var(--font-main); font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6c63ff, #8b5cf6); color: #fff;
            box-shadow: 0 4px 20px rgba(108, 99, 255, 0.35);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 28px rgba(108, 99, 255, 0.5); }
        .btn-primary:active { transform: translateY(0); }

        .result-area { margin-top: 20px; display: none; }
        .result-area.show { display: block; animation: fadeIn 0.3s ease; }

        .result-box {
            background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 14px; padding: 16px; position: relative;
        }
        .bulk-result { background: rgba(108, 99, 255, 0.08); border-color: rgba(108, 99, 255, 0.15); }

        .result-label {
            color: rgba(255, 255, 255, 0.4); font-size: 11px; text-transform: uppercase;
            letter-spacing: 0.8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;
        }
        .result-number { color: #10b981; font-size: 20px; font-weight: 600; letter-spacing: 1px; font-family: monospace, var(--font-main); word-break: break-all; }
        .bulk-result .result-number { color: #a78bfa; font-size: 14px; line-height: 2; font-weight: 400; }

        .copy-btn {
            background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.25);
            color: #10b981; padding: 4px 12px; border-radius: 8px; font-size: 11px;
            font-weight: 500; cursor: pointer; font-family: var(--font-main); transition: all 0.2s ease;
        }
        .copy-btn:hover { background: rgba(16, 185, 129, 0.25); }

        .stats { display: flex; gap: 12px; margin-top: 16px; }
        .stat-item {
            flex: 1; background: rgba(255, 255, 255, 0.05); border-radius: 12px;
            padding: 12px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .stat-value { color: #fff; font-size: 20px; font-weight: 700; }
        .stat-label { color: rgba(255, 255, 255, 0.35); font-size: 11px; margin-top: 2px; }

        .info-text { color: rgba(255, 255, 255, 0.3); font-size: 12px; margin-top: 12px; text-align: center; line-height: 1.6; }
        .info-text span { color: rgba(108, 99, 255, 0.7); }

        .toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: rgba(16, 185, 129, 0.2); backdrop-filter: blur(16px);
            border: 1px solid rgba(16, 185, 129, 0.3); color: #6ee7b7;
            padding: 12px 24px; border-radius: 12px; font-size: 13px; font-weight: 500;
            z-index: 999; transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); pointer-events: none;
        }
        .toast.show { transform: translateX(-50%) translateY(0); }

        @media (max-width: 480px) {
            .glass-card { padding: 28px 20px; border-radius: 20px; }
            .result-number { font-size: 17px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="glass-card">
        <div class="logo">
            <div class="logo-icon">📱</div>
            <h1>Format 62</h1>
            <p>Ubah nomor HP ke format Indonesia</p>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('single')">Satu Nomor</button>
            <button class="tab-btn" onclick="switchTab('bulk')">Bulk Format</button>
        </div>

        <div id="tab-single" class="tab-content active">
            <label>Masukkan Nomor</label>
            <input type="text" id="singleInput" class="has-prefix" placeholder="85722075589" oninput="convertSingle()">
            <br><br>
            <button class="btn btn-primary" onclick="convertSingle()">⚡ Format Sekarang</button>
            <div id="singleResult" class="result-area">
                <div class="result-box">
                    <div class="result-label"><span>Hasil</span><button class="copy-btn" onclick="copyResult('single')">📋 Copy</button></div>
                    <div class="result-number" id="singleOutput"></div>
                </div>
            </div>
        </div>

        <div id="tab-bulk" class="tab-content">
            <label>Masukkan Banyak Nomor</label>
            <textarea id="bulkInput" placeholder="Satu nomor per baris&#10;85722075589&#10;081234567890&#10;628987654321"></textarea>
            <br><br>
            <button class="btn btn-primary" onclick="convertBulk()">⚡ Format Semua</button>
            <div id="bulkResult" class="result-area">
                <div class="result-box bulk-result">
                    <div class="result-label"><span>Hasil</span><button class="copy-btn" onclick="copyResult('bulk')">📋 Copy Semua</button></div>
                    <div class="result-number" id="bulkOutput"></div>
                </div>
                <div class="stats">
                    <div class="stat-item"><div class="stat-value" id="statTotal">0</div><div class="stat-label">Total</div></div>
                    <div class="stat-item"><div class="stat-value" id="statConverted">0</div><div class="stat-label">Berhasil</div></div>
                    <div class="stat-item"><div class="stat-value" id="statSkipped">0</div><div class="stat-label">Dilewati</div></div>
                </div>
            </div>
        </div>

        <p class="info-text">Mendukung format: <span>08xx</span>, <span>8xx</span>, <span>62xx</span>, <span>+62xx</span></p>
    </div>
</div>

<div class="toast" id="toast">✅ Berhasil disalin!</div>

<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach((btn, i) => {
            btn.classList.toggle('active', (tab === 'single' && i === 0) || (tab === 'bulk' && i === 1));
        });
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
    }

    function formatTo62(number) {
        let n = number.trim().replace(/[\s\-\(\)]/g, '');
        if (!/^\d+$/.test(n)) return null;
        if (n.startsWith('+62')) return '62' + n.substring(3);
        if (n.startsWith('62')) return n;
        if (n.startsWith('0')) return '62' + n.substring(1);
        if (/^[89]/.test(n)) return '62' + n;
        return null;
    }

    function convertSingle() {
        const input = document.getElementById('singleInput').value.trim();
        const resultArea = document.getElementById('singleResult');
        const output = document.getElementById('singleOutput');
        if (!input) { resultArea.classList.remove('show'); return; }
        const formatted = formatTo62(input);
        if (formatted) { output.textContent = formatted; output.style.color = '#10b981'; }
        else { output.textContent = 'Format tidak valid'; output.style.color = '#f87171'; }
        resultArea.classList.add('show');
    }

    function convertBulk() {
        const raw = document.getElementById('bulkInput').value;
        const lines = raw.split('\n');
        const results = []; let converted = 0, skipped = 0;
        lines.forEach(line => {
            const trimmed = line.trim();
            if (!trimmed) { skipped++; return; }
            const formatted = formatTo62(trimmed);
            if (formatted) { results.push(formatted); converted++; }
            else { results.push('❌ ' + trimmed); skipped++; }
        });
        document.getElementById('bulkOutput').textContent = results.join('\n');
        const validLines = lines.filter(l => l.trim()).length;
        document.getElementById('statTotal').textContent = validLines;
        document.getElementById('statConverted').textContent = converted;
        document.getElementById('statSkipped').textContent = skipped;
        document.getElementById('bulkResult').classList.add('show');
    }

    function copyResult(type) {
        const text = type === 'single' ? document.getElementById('singleOutput').textContent : document.getElementById('bulkOutput').textContent;
        navigator.clipboard.writeText(text).catch(() => {
            const ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        }).finally(() => showToast());
    }

    function showToast() {
        const toast = document.getElementById('toast');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2000);
    }
</script>

</body>
</html>