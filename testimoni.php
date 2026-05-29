<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimoni Peserta — JAWWADA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --font: 'Raleway', sans-serif;
            --ease: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="light"] {
            --bg: #f0f2f5;
            --text: #1a1a2e;
            --text-secondary: #555570;
            --text-muted: #8888a0;
            --glass-bg: rgba(255, 255, 255, 0.55);
            --glass-border: rgba(255, 255, 255, 0.7);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            --glass-shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.1);
            --glass-blur: blur(20px);
            --glass-inset: rgba(255, 255, 255, 0.8);
            --green: #16a34a;
            --green-glass: rgba(22, 163, 74, 0.12);
            --green-solid: #16a34a;
            --purple: #9333ea;
            --purple-glass: rgba(147, 51, 234, 0.12);
            --purple-solid: #9333ea;
            --gold: #d97706;
            --danger: #ef4444;
            --danger-glass: rgba(239, 68, 68, 0.1);
            --overlay: rgba(0, 0, 0, 0.2);
            --radius: 16px;
            --radius-sm: 10px;
            --radius-lg: 24px;
        }

        [data-theme="dark"] {
            --bg: #0c0c1d;
            --text: #e8e8f0;
            --text-secondary: #a0a0b8;
            --text-muted: #606078;
            --glass-bg: rgba(255, 255, 255, 0.06);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            --glass-shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.4);
            --glass-blur: blur(20px);
            --glass-inset: rgba(255, 255, 255, 0.03);
            --green: #4ade80;
            --green-glass: rgba(74, 222, 128, 0.12);
            --green-solid: #4ade80;
            --purple: #c084fc;
            --purple-glass: rgba(192, 132, 252, 0.12);
            --purple-solid: #c084fc;
            --gold: #fbbf24;
            --danger: #f87171;
            --danger-glass: rgba(248, 113, 113, 0.1);
            --overlay: rgba(0, 0, 0, 0.6);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            transition: background var(--ease), color var(--ease);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ─── Background Orbs ─── */
        .bg-orbs {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: orbFloat 20s ease-in-out infinite;
        }

        .orb-1 {
            width: 400px; height: 400px;
            background: var(--green-glass);
            top: -100px; left: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 350px; height: 350px;
            background: var(--purple-glass);
            top: 30%; right: -80px;
            animation-delay: -7s;
            animation-duration: 25s;
        }

        .orb-3 {
            width: 300px; height: 300px;
            background: var(--green-glass);
            bottom: 10%; left: 20%;
            animation-delay: -14s;
            animation-duration: 22s;
        }

        .orb-4 {
            width: 250px; height: 250px;
            background: var(--purple-glass);
            bottom: -50px; right: 20%;
            animation-delay: -3s;
            animation-duration: 18s;
        }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -40px) scale(1.05); }
            50% { transform: translate(-20px, 30px) scale(0.95); }
            75% { transform: translate(40px, 20px) scale(1.02); }
        }

        /* ─── Glass Mixin ─── */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
        }

        /* ─── LAYOUT ─── */
        .page {
            position: relative;
            z-index: 1;
            max-width: 960px;
            margin: 0 auto;
            padding: 0 1.25rem;
        }

        /* ─── HEADER ─── */
        .header {
            padding: 2.5rem 0 0;
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .logo-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 10px;
        }

        .logo-text h1 {
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .logo-text h1 span {
            font-weight: 700;
            background: linear-gradient(135deg, var(--green-solid), var(--purple-solid));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-text p {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all var(--ease);
        }

        .icon-btn:hover {
            color: var(--text);
            box-shadow: var(--glass-shadow-hover);
            transform: translateY(-1px);
        }

        .admin-btn { display: none; }
        .admin-btn.visible { display: flex; }

        /* ─── Stats ─── */
        .stats {
            display: flex;
            gap: 1px;
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 2rem;
            background: var(--glass-border);
        }

        .stat {
            flex: 1;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            padding: 1.25rem 1rem;
            text-align: center;
            transition: all var(--ease);
        }

        .stat:first-child { border-radius: var(--radius) 0 0 var(--radius); }
        .stat:last-child { border-radius: 0 var(--radius) var(--radius) 0; }

        .stat:hover {
            background: var(--glass-inset);
        }

        .stat-num {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            margin-top: 0.15rem;
        }

        .stat:nth-child(2) .stat-num { color: var(--green-solid); }
        .stat:nth-child(3) .stat-num { color: var(--purple-solid); }

        /* ─── CONTROLS ─── */
        .controls {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.25rem;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.8rem;
            pointer-events: none;
        }

        .search-box input {
            width: 100%;
            padding: 0.7rem 0.85rem 0.7rem 2.3rem;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            font-size: 0.85rem;
            font-family: var(--font);
            color: var(--text);
            outline: none;
            transition: all var(--ease);
        }

        .search-box input:focus {
            border-color: var(--text-muted);
            box-shadow: 0 0 0 3px var(--green-glass);
        }

        .search-box input::placeholder { color: var(--text-muted); }

        .tabs {
            display: flex;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            overflow: hidden;
            gap: 2px;
            padding: 2px;
        }

        .tab {
            padding: 0.5rem 0.9rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-family: var(--font);
            transition: all var(--ease);
            white-space: nowrap;
            border-radius: 50px;
        }

        .tab:hover { color: var(--text); }

        .tab.active[data-filter="all"] { background: var(--text); color: #fff; }
        .tab.active[data-filter="tahsin"] { background: var(--green-solid); color: #fff; }
        .tab.active[data-filter="tahfidz"] { background: var(--purple-solid); color: #fff; }

        .sort-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.8rem;
            transition: all var(--ease);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sort-btn:hover { color: var(--text); box-shadow: var(--glass-shadow-hover); }

        .count-text {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 1.25rem;
        }

        /* ─── GRID ─── */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 0.75rem;
            padding-bottom: 4rem;
        }

        /* ─── CARD ─── */
        .card {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 1.25rem;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            animation: cardIn 0.5s ease forwards;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: var(--radius) var(--radius) 0 0;
        }

        .card.tahsin::before { background: var(--green-solid); }
        .card.tahfidz::before { background: var(--purple-solid); }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card:hover {
            border-color: rgba(255, 255, 255, 0.9);
            box-shadow: var(--glass-shadow-hover);
            transform: translateY(-3px);
        }

        .card.tahsin:hover { box-shadow: 0 8px 30px rgba(22, 163, 74, 0.1); }
        .card.tahfidz:hover { box-shadow: 0 8px 30px rgba(147, 51, 234, 0.1); }

        .card-head {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }

        .card.tahsin .avatar { background: linear-gradient(135deg, var(--green-solid), #22c55e); }
        .card.tahfidz .avatar { background: linear-gradient(135deg, var(--purple-solid), #a855f7); }

        .card-name { font-weight: 700; font-size: 0.88rem; line-height: 1.3; }

        .card-badge {
            font-size: 0.62rem;
            font-weight: 600;
            padding: 0.15rem 0.55rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 0.15rem;
            display: inline-block;
        }

        .card.tahsin .card-badge { background: var(--green-glass); color: var(--green-solid); }
        .card.tahfidz .card-badge { background: var(--purple-glass); color: var(--purple-solid); }

        .card-body {
            font-size: 0.83rem;
            color: var(--text-secondary);
            line-height: 1.65;
            display: -webkit-box;
            -webkit-line-clamp: 5;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-body.expanded { -webkit-line-clamp: unset; }

        .card-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.85rem;
            padding-top: 0.65rem;
            border-top: 1px solid var(--glass-border);
        }

        .card-date { font-size: 0.68rem; color: var(--text-muted); }

        .card-foot-right { display: flex; align-items: center; gap: 0.35rem; }

        .read-more {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 0.73rem;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--font);
            padding: 0;
            transition: color var(--ease);
        }

        .read-more:hover { color: var(--text); }

        .del-btn {
            display: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid var(--glass-border);
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.7rem;
            transition: all var(--ease);
            align-items: center;
            justify-content: center;
        }

        .del-btn:hover {
            color: var(--danger);
            background: var(--danger-glass);
            border-color: rgba(239, 68, 68, 0.3);
        }

        body.admin .del-btn { display: flex; }

        /* ─── EMPTY ─── */
        .empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 1rem;
        }

        .empty i { font-size: 2.5rem; color: var(--text-muted); margin-bottom: 1rem; opacity: 0.2; }
        .empty h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem; }
        .empty p { font-size: 0.82rem; color: var(--text-muted); }

        /* ── MODAL (Glass) ─── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--overlay);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .modal-overlay.open { display: flex; }

        .modal {
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 460px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: var(--glass-shadow-hover);
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-head {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-head h2 { font-size: 1rem; font-weight: 700; }

        .modal-body { padding: 1.5rem; }

        .field { margin-bottom: 1rem; }
        .field label {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field input,
        .field textarea,
        .field select {
            width: 100%;
            padding: 0.65rem 0.85rem;
            background: var(--glass-inset);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-family: var(--font);
            color: var(--text);
            outline: none;
            transition: all var(--ease);
        }

        .field input:focus,
        .field textarea:focus,
        .field select:focus {
            border-color: var(--text-muted);
            box-shadow: 0 0 0 3px var(--green-glass);
        }

        .field textarea { min-height: 80px; resize: vertical; }

        /* Format preview */
        .format-box {
            background: var(--glass-inset);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-sm);
            padding: 0.85rem 1rem;
            margin-bottom: 1rem;
        }

        .fmt-label {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .format-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.72rem;
            font-family: 'Courier New', monospace;
        }

        .format-table th {
            padding: 0.4rem 0.6rem;
            background: var(--glass-inset);
            border: 1px solid var(--glass-border);
            font-weight: 700;
            text-align: left;
            color: var(--text);
        }

        .format-table td {
            padding: 0.35rem 0.6rem;
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
        }

        .format-table th:nth-child(1) { color: var(--green-solid); }
        .format-table th:nth-child(2) { color: var(--purple-solid); }

        .format-hint {
            font-size: 0.68rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .format-hint i { margin-right: 0.25rem; color: var(--gold); }

        .upload-zone {
            border: 1.5px dashed var(--glass-border);
            border-radius: var(--radius);
            padding: 1.75rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all var(--ease);
            position: relative;
            background: var(--glass-inset);
        }

        .upload-zone:hover,
        .upload-zone.drag {
            border-color: var(--text-muted);
            background: rgba(255,255,255,0.1);
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-zone i { font-size: 1.5rem; color: var(--text-muted); margin-bottom: 0.5rem; }
        .upload-zone h4 { font-size: 0.82rem; font-weight: 600; }
        .upload-zone p { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.15rem; }

        .upload-status {
            margin-top: 0.75rem;
            padding: 0.6rem 0.85rem;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 500;
            display: none;
        }

        .upload-status.ok { display: block; background: var(--green-glass); color: var(--green-solid); border: 1px solid rgba(22,163,74,0.3); }
        .upload-status.err { display: block; background: var(--danger-glass); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); }

        .divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0;
            font-size: 0.68rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--glass-border);
        }

        .btn {
            padding: 0.6rem 1.25rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: var(--font);
            transition: all var(--ease);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-primary {
            background: var(--text);
            color: var(--bg);
        }
        .btn-primary:hover { opacity: 0.85; transform: translateY(-1px); }

        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--glass-border);
        }
        .btn-ghost:hover { color: var(--text); border-color: var(--text-muted); }

        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { opacity: 0.85; transform: translateY(-1px); }

        .modal-foot {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--glass-border);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        /* Confirm */
        .confirm-body { text-align: center; padding: 2rem 1.5rem; }
        .confirm-body i { font-size: 2.5rem; color: var(--gold); margin-bottom: 0.75rem; }
        .confirm-body h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.35rem; }
        .confirm-body p { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.5rem; }
        .confirm-actions { display: flex; gap: 0.5rem; justify-content: center; }

        /* ─── TOAST ─── */
        .toasts {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .toast {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-sm);
            padding: 0.7rem 1rem;
            box-shadow: var(--glass-shadow-hover);
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: toastIn 0.25s ease;
            min-width: 220px;
        }

        .toast.out { animation: toastOut 0.25s ease forwards; }

        @keyframes toastIn { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toastOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(100%); } }

        .toast.ok { border-left: 3px solid var(--green-solid); }
        .toast.err { border-left: 3px solid var(--danger); }
        .toast.warn { border-left: 3px solid var(--gold); }

        /* ─── FOOTER ─── */
        .footer {
            text-align: center;
            padding: 2rem 1rem;
            border-top: 1px solid var(--glass-border);
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 640px) {
            .header { padding: 1.5rem 0 0; }
            .logo-text h1 { font-size: 1.1rem; }
            .logo-img { width: 40px; height: 40px; }
            .controls { flex-wrap: wrap; }
            .tabs { width: 100%; justify-content: center; }
            .grid { grid-template-columns: 1fr; }
            .toasts { left: 1rem; right: 1rem; }
            .toast { min-width: auto; }
        }

        @media (max-width: 380px) {
            .header-inner { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .header-actions { align-self: flex-end; }
        }
    </style>
</head>
<body>

<!-- Background Orbs -->
<div class="bg-orbs">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>
</div>

<div class="page">

    <!-- HEADER -->
    <header class="header">
        <div class="header-inner">
            <div class="logo-row">
                <img src="LOGOJWD.png" alt="JAWWADA" class="logo-img" onerror="this.style.display='none'">
                <div class="logo-text">
                    <h1>TESTIMONIAL PESERTA <span>JAWWADA</span></h1>
                    <p>Program Tahsin & Tahfidz Al-Quran</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="icon-btn admin-btn" id="adminBtn" onclick="openModal()" title="Upload Data">
                    <i class="fas fa-upload"></i>
                </button>
                <button class="icon-btn" id="themeToggle" onclick="toggleTheme()" title="Toggle theme">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
            </div>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-num" id="totalCount">0</div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat">
                <div class="stat-num" id="tahsinCount">0</div>
                <div class="stat-label">Tahsin</div>
            </div>
            <div class="stat">
                <div class="stat-num" id="tahfidzCount">0</div>
                <div class="stat-label">Tahfidz</div>
            </div>
        </div>
    </header>

    <!-- CONTROLS -->
    <div class="controls">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari nama..." autocomplete="off">
        </div>
        <div class="tabs">
            <button class="tab active" data-filter="all" onclick="setFilter('all')">Semua</button>
            <button class="tab" data-filter="tahsin" onclick="setFilter('tahsin')">Tahsin</button>
            <button class="tab" data-filter="tahfidz" onclick="setFilter('tahfidz')">Tahfidz</button>
        </div>
        <button class="sort-btn" onclick="cycleSort()" title="Urutkan">
            <i class="fas fa-arrow-down-short-wide" id="sortIcon"></i>
        </button>
    </div>
    <div class="count-text" id="countText"></div>

    <!-- GRID -->
    <main class="grid" id="grid"></main>

</div>

<footer class="footer">
    &copy; 2025 JAWWADA — Program Tahsin & Tahfidz Al-Quran
</footer>

<!-- UPLOAD MODAL -->
<div class="modal-overlay" id="uploadModal">
    <div class="modal">
        <div class="modal-head">
            <h2><i class="fas fa-upload" style="margin-right:0.5rem;"></i>Upload Data</h2>
            <button class="icon-btn" onclick="closeModal()" style="width:32px;height:32px;font-size:0.75rem;">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="field">
                <label>Program</label>
                <select id="programSelect">
                    <option value="tahsin">Tahsin</option>
                    <option value="tahfidz">Tahfidz</option>
                </select>
            </div>

            <div class="format-box">
                <div class="fmt-label">Format CSV dari Spreadsheet</div>
                <table class="format-table">
                    <thead>
                        <tr><th>NAMA</th><th>TESTIMONI</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Ahmad Zaid</td><td>Sangat menunjang...</td></tr>
                        <tr><td>Khansa Fahira</td><td>Ustadzahnya menyenangkan</td></tr>
                    </tbody>
                </table>
                <div class="format-hint">
                    <i class="fas fa-circle-info"></i>
                    Export spreadsheet sebagai <strong>.csv</strong> — Duplikat nama otomatis dihindari
                </div>
            </div>

            <div class="upload-zone" id="uploadZone">
                <input type="file" id="csvInput" accept=".csv,.txt" onchange="handleFile(event)">
                <i class="fas fa-cloud-arrow-up"></i>
                <h4>Drag & drop file CSV</h4>
                <p>atau klik untuk pilih file</p>
            </div>
            <div class="upload-status" id="uploadStatus"></div>

            <div class="divider">atau tambahkan manual</div>

            <div class="field">
                <label>Nama</label>
                <input type="text" id="manualName" placeholder="Nama peserta">
            </div>
            <div class="field">
                <label>Testimoni</label>
                <textarea id="manualText" placeholder="Tuliskan testimoni..."></textarea>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost" onclick="closeModal()">Batal</button>
            <button class="btn btn-primary" onclick="addManual()"><i class="fas fa-plus"></i> Tambah</button>
        </div>
    </div>
</div>

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal" style="max-width:380px;">
        <div class="confirm-body">
            <i class="fas fa-triangle-exclamation"></i>
            <h3>Hapus Testimoni?</h3>
            <p id="confirmMsg">Data akan dihapus permanen.</p>
            <div class="confirm-actions">
                <button class="btn btn-ghost" onclick="closeConfirm()">Batal</button>
                <button class="btn btn-danger" onclick="doDelete()">Hapus</button>
            </div>
        </div>
    </div>
</div>

<!-- TOASTS -->
<div class="toasts" id="toasts"></div>

<script>
    const KEY = 'jwwd_testimoni_v5';
    const THEME_KEY = 'jwwd_theme_v5';
    let data = [], filter = 'all', admin = false, delId = null;
    const sorts = ['newest', 'oldest', 'az', 'za'];
    let sortIdx = 0;

    document.addEventListener('DOMContentLoaded', () => {
        checkAdmin();
        loadTheme();
        load();
        render();
        updateStats();
        listen();
    });

    // ─── Admin ───
    function checkAdmin() {
        const p = new URLSearchParams(location.search);
        admin = p.get('admin') === 'true';
        document.getElementById('adminBtn').classList.toggle('visible', admin);
        document.body.classList.toggle('admin', admin);
    }

    // ─── Theme ───
    function loadTheme() {
        const s = localStorage.getItem(THEME_KEY);
        const dark = s || (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
        applyTheme(dark);
    }

    function applyTheme(t) {
        document.documentElement.dataset.theme = t;
        localStorage.setItem(THEME_KEY, t);
        document.getElementById('themeIcon').className = t === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    function toggleTheme() {
        applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
    }

    // ─── Data ───
    function load() {
        try { data = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch { data = []; }
    }

    function save() { localStorage.setItem(KEY, JSON.stringify(data)); }
    function uid() { return Date.now().toString(36) + Math.random().toString(36).slice(2, 8); }

    // ─── Duplicate Check ───
    function isDuplicate(name) {
        return data.some(d => d.name.toLowerCase().trim() === name.toLowerCase().trim());
    }

    // ─── CSV Parser (Spreadsheet format) ───
    function parseCSV(text) {
        const out = [];
        const lines = text.split(/\r?\n/);

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!line) continue;

            const fields = parseCSVLine(line);
            if (fields.length < 2) continue;

            const name = fields[0].trim();
            const testimoni = fields.slice(1).join('').trim();

            // Skip header
            if (i === 0) {
                const h = name.toUpperCase();
                if (h === 'NAMA' || h === 'NAME' || h === 'NO' || h === 'NOMOR' || h === 'N') continue;
            }

            if (name && testimoni) {
                out.push({ name: esc(name), testimoni: esc(testimoni) });
            }
        }

        return out;
    }

    function parseCSVLine(line) {
        const result = [];
        let current = '';
        let inQuotes = false;
        for (let i = 0; i < line.length; i++) {
            const ch = line[i];
            if (inQuotes) {
                if (ch === '"') {
                    if (i + 1 < line.length && line[i + 1] === '"') { current += '"'; i++; }
                    else { inQuotes = false; }
                } else { current += ch; }
            } else {
                if (ch === '"') { inQuotes = true; }
                else if (ch === ',') { result.push(current); current = ''; }
                else { current += ch; }
            }
        }
        result.push(current);
        return result;
    }

    function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    // ─── Upload ───
    function handleFile(e) {
        const f = e.target.files[0];
        if (!f) return;
        const status = document.getElementById('uploadStatus');

        if (!/\.csv|\.txt$/i.test(f.name)) {
            status.textContent = 'Format tidak valid. Gunakan file .csv';
            status.className = 'upload-status err';
            return;
        }

        if (f.size > 5 * 1024 * 1024) {
            status.textContent = 'Maksimal 5MB';
            status.className = 'upload-status err';
            return;
        }

        const r = new FileReader();
        r.onload = ev => {
            const entries = parseCSV(ev.target.result);
            if (!entries.length) {
                status.textContent = 'Tidak ada data valid';
                status.className = 'upload-status err';
                return;
            }

            const prog = document.getElementById('programSelect').value;
            let added = 0, skipped = 0;

            entries.forEach(en => {
                if (isDuplicate(en.name)) {
                    skipped++;
                } else {
                    data.unshift({
                        id: uid(),
                        name: en.name,
                        testimoni: en.testimoni,
                        program: prog,
                        date: new Date().toISOString()
                    });
                    added++;
                }
            });

            save();
            render();
            updateStats();

            let msg = `✓ ${added} testimoni ditambahkan`;
            if (skipped > 0) msg += ` — ${skipped} duplikat dilewati`;
            status.textContent = msg;
            status.className = 'upload-status ok';

            e.target.value = '';

            if (skipped > 0) {
                toast(`${added} ditambah, ${skipped} duplikat dilewati`, 'warn');
            } else {
                toast(`${added} testimoni ditambahkan`, 'ok');
            }
        };
        r.readAsText(f);
    }

    // ─── Manual ──
    function addManual() {
        const n = document.getElementById('manualName').value.trim();
        const t = document.getElementById('manualText').value.trim();
        if (!n) return toast('Nama wajib diisi', 'err');
        if (!t) return toast('Testimoni wajib diisi', 'err');

        if (isDuplicate(n)) {
            toast(`"${n}" sudah ada — tidak ditambahkan`, 'warn');
            return;
        }

        data.unshift({
            id: uid(),
            name: esc(n),
            testimoni: esc(t),
            program: document.getElementById('programSelect').value,
            date: new Date().toISOString()
        });

        save();
        render();
        updateStats();
        document.getElementById('manualName').value = '';
        document.getElementById('manualText').value = '';
        toast('Berhasil ditambahkan', 'ok');
    }

    // ─── Delete ──
    function askDelete(id) {
        delId = id;
        const item = data.find(d => d.id === id);
        document.getElementById('confirmMsg').textContent = `"${item?.name}" akan dihapus permanen.`;
        document.getElementById('confirmModal').classList.add('open');
    }

    function doDelete() {
        if (delId) {
            data = data.filter(d => d.id !== delId);
            save();
            render();
            updateStats();
            toast('Dihapus', 'ok');
        }
        closeConfirm();
    }

    function closeConfirm() {
        document.getElementById('confirmModal').classList.remove('open');
        delId = null;
    }

    // ── Modals ───
    function openModal() {
        if (!admin) return;
        document.getElementById('uploadModal').classList.add('open');
        document.getElementById('uploadStatus').className = 'upload-status';
    }

    function closeModal() {
        document.getElementById('uploadModal').classList.remove('open');
    }

    document.addEventListener('click', e => {
        if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    });

    // ─── Render ───
    function render() {
        const q = document.getElementById('searchInput').value.toLowerCase().trim();
        const sort = sorts[sortIdx];

        let list = data.filter(d => {
            return (filter === 'all' || d.program === filter) &&
                (!q || d.name.toLowerCase().includes(q) || d.testimoni.toLowerCase().includes(q));
        });

        list.sort((a, b) => {
            if (sort === 'newest') return new Date(b.date) - new Date(a.date);
            if (sort === 'oldest') return new Date(a.date) - new Date(b.date);
            if (sort === 'az') return a.name.localeCompare(b.name, 'id');
            return b.name.localeCompare(a.name, 'id');
        });

        document.getElementById('countText').textContent = `${list.length} / ${data.length} testimoni`;

        const grid = document.getElementById('grid');

        if (!list.length) {
            grid.innerHTML = `<div class="empty">
                <i class="fas fa-inbox"></i>
                <h3>${q ? 'Tidak ditemukan' : 'Belum ada data'}</h3>
                <p>${q ? 'Coba kata kunci lain' : admin ? 'Upload CSV atau tambah manual' : 'Testimoni akan muncul di sini'}</p>
            </div>`;
            return;
        }

        grid.innerHTML = list.map((d, i) => {
            const init = d.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
            const date = new Date(d.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            const long = d.testimoni.length > 150;

            return `<div class="card ${d.program}" style="animation-delay:${i * 0.04}s">
                <div class="card-head">
                    <div class="avatar">${init}</div>
                    <div>
                        <div class="card-name">${d.name}</div>
                        <span class="card-badge">${d.program === 'tahsin' ? 'Tahsin' : 'Tahfidz'}</span>
                    </div>
                </div>
                <div class="card-body" id="body-${d.id}">${d.testimoni}</div>
                <div class="card-foot">
                    <span class="card-date">${date}</span>
                    <div class="card-foot-right">
                        ${long ? `<button class="read-more" onclick="toggleExpand('${d.id}')">Selengkapnya</button>` : ''}
                        <button class="del-btn" onclick="askDelete('${d.id}')"><i class="fas fa-trash-can"></i></button>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function toggleExpand(id) {
        const el = document.getElementById('body-' + id);
        if (!el) return;
        el.classList.toggle('expanded');
        const btn = el.closest('.card').querySelector('.read-more');
        if (btn) btn.textContent = el.classList.contains('expanded') ? 'Sembunyikan' : 'Selengkapnya';
    }

    // ─── Filter / Sort ───
    function setFilter(f) {
        filter = f;
        document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.filter === f));
        render();
    }

    function cycleSort() {
        sortIdx = (sortIdx + 1) % sorts.length;
        const icon = document.getElementById('sortIcon');
        const map = {
            newest: 'fa-arrow-down-short-wide',
            oldest: 'fa-arrow-up-short-wide',
            az: 'fa-arrow-down-a-z',
            za: 'fa-arrow-down-z-a'
        };
        icon.className = 'fas ' + map[sorts[sortIdx]];
        render();
    }

    // ─── Stats ───
    function updateStats() {
        anim('totalCount', data.length);
        anim('tahsinCount', data.filter(d => d.program === 'tahsin').length);
        anim('tahfidzCount', data.filter(d => d.program === 'tahfidz').length);
    }

    function anim(id, target) {
        const el = document.getElementById(id);
        const cur = parseInt(el.textContent) || 0;
        const step = (target - cur) / 15;
        let i = 0;
        const t = setInterval(() => {
            i++;
            el.textContent = Math.round(cur + step * i);
            if (i >= 15) { el.textContent = target; clearInterval(t); }
        }, 20);
    }

    // ─── Toast ───
    function toast(msg, type = 'ok') {
        const c = document.getElementById('toasts');
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        const icons = { ok: 'fa-circle-check', err: 'fa-circle-xmark', warn: 'fa-triangle-exclamation' };
        t.innerHTML = `<i class="fas ${icons[type] || icons.ok}"></i> ${msg}`;
        c.appendChild(t);
        setTimeout(() => {
            t.classList.add('out');
            setTimeout(() => t.remove(), 250);
        }, 3000);
    }

    // ─── Events ───
    function listen() {
        let timer;
        document.getElementById('searchInput').addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(render, 250);
        });

        const zone = document.getElementById('uploadZone');
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('drag');
            if (e.dataTransfer.files.length) {
                document.getElementById('csvInput').files = e.dataTransfer.files;
                handleFile({ target: document.getElementById('csvInput') });
            }
        });
    }
</script>
</body>
</html>