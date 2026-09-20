<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>UX Agent</title>
<meta name="description" content="{{ __('A guided UX discovery agent: one question at a time, until the brief is complete.') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    @font-face {
        font-family: 'Vazirmatn';
        src: url('{{ asset('assets/fonts/Vazirmatn-UI-FD-Light.ttf') }}') format('truetype');
        font-weight: 300;
        font-style: normal;
        font-display: swap;
    }

    @font-face {
        font-family: 'Vazirmatn';
        src: url('{{ asset('assets/fonts/Vazirmatn-UI-FD-Regular.ttf') }}') format('truetype');
        font-weight: 400;
        font-style: normal;
        font-display: swap;
    }

    @font-face {
        font-family: 'Vazirmatn';
        src: url('{{ asset('assets/fonts/Vazirmatn-UI-FD-Bold.ttf') }}') format('truetype');
        font-weight: 700;
        font-style: normal;
        font-display: swap;
    }

    :root {
        color-scheme: dark;

        --bg: #0b0d12;
        --bg-glow: rgba(91, 141, 239, 0.10);
        --panel: #141822;
        --panel-2: #1a1f2b;
        --line: #242b39;
        --line-strong: #33405a;
        --text: #e8eaf0;
        --muted: #8b95a8;
        --accent: #6b8dfa;
        --accent-2: #4f74e8;
        --accent-soft: rgba(107, 141, 250, 0.14);
        --danger: #f0655c;
        --danger-soft: rgba(240, 101, 92, 0.10);
        --ok: #4fbf8b;
        --doc-bg: #0d1017;
        --code-bg: #1c222e;
        --shadow: 0 18px 40px -20px rgba(0, 0, 0, 0.9);
        --radius: 14px;
        --radius-sm: 9px;
        --font: 'Vazirmatn', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
    }

    html[data-theme="light"] {
        color-scheme: light;
        --bg: #f5f7fb;
        --bg-glow: rgba(91, 141, 239, 0.18);
        --panel: #ffffff;
        --panel-2: #eff2f8;
        --line: #e1e6ef;
        --line-strong: #c4ccdd;
        --text: #131720;
        --muted: #626d88;
        --accent: #3d6fd6;
        --accent-2: #2f5bb8;
        --accent-soft: rgba(61, 111, 214, 0.10);
        --danger: #cf3d34;
        --danger-soft: rgba(207, 61, 52, 0.08);
        --ok: #2f9e6a;
        --doc-bg: #ffffff;
        --code-bg: #eef1f7;
        --shadow: 0 18px 40px -24px rgba(20, 24, 31, 0.35);
    }

    * { box-sizing: border-box; }

    html, body { height: 100%; }

    body {
        margin: 0;
        background:
            radial-gradient(900px 420px at 50% -8%, var(--bg-glow), transparent 70%),
            var(--bg);
        color: var(--text);
        font-family: var(--font);
        font-size: 15px;
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        overflow: hidden;
    }

    ::selection { background: var(--accent-soft); }

    button {
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        border-radius: var(--radius-sm);
        border: 1px solid var(--line);
        background: var(--panel-2);
        color: var(--text);
        padding: 8px 13px;
        line-height: 1.2;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: background .16s ease, border-color .16s ease, color .16s ease, opacity .16s ease, transform .1s ease;
    }

    button:hover:not(:disabled) {
        background: var(--panel);
        border-color: var(--line-strong);
    }

    button:active:not(:disabled) { transform: translateY(1px); }

    button:disabled { opacity: .45; cursor: not-allowed; }

    button:focus-visible,
    textarea:focus-visible,
    a:focus-visible {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }

    button.primary {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
        box-shadow: 0 6px 18px -8px var(--accent);
    }

    button.primary:hover:not(:disabled) {
        background: var(--accent-2);
        border-color: var(--accent-2);
    }

    button.ghost {
        background: transparent;
        border-color: transparent;
        color: var(--muted);
    }

    button.ghost:hover:not(:disabled) {
        background: var(--panel-2);
        border-color: var(--line);
        color: var(--text);
    }

    button.icon {
        padding: 8px;
        width: 36px;
        height: 36px;
        justify-content: center;
    }

    button.stop {
        background: var(--danger-soft);
        border-color: var(--danger);
        color: var(--danger);
        box-shadow: none;
    }

    button.stop:hover:not(:disabled) {
        background: var(--danger);
        border-color: var(--danger);
        color: #fff;
    }

    /* ---------------------------------------------------------------- shell */

    #app {
        display: flex;
        flex-direction: column;
        height: 100vh;
        height: 100dvh;
        max-width: 940px;
        margin: 0 auto;
        padding: 0 18px;
        position: relative;
    }

    header.app-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 2px 12px;
        border-bottom: 1px solid var(--line);
        flex: 0 0 auto;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
    }

    .brand-mark {
        width: 36px;
        height: 36px;
        flex: 0 0 auto;
        border-radius: 11px;
        display: grid;
        place-items: center;
        background: linear-gradient(150deg, var(--accent), var(--accent-2));
        color: #fff;
        box-shadow: 0 8px 20px -10px var(--accent);
    }

    .brand-mark svg { width: 19px; height: 19px; display: block; }

    .brand-text { min-width: 0; }

    .brand-title {
        font-weight: 650;
        letter-spacing: .2px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
    }

    .brand-sub {
        color: var(--muted);
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 46ch;
    }

    .badge {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
        padding: 2px 7px;
        border-radius: 999px;
        border: 1px solid var(--line-strong);
        color: var(--muted);
        background: var(--panel-2);
        line-height: 1.5;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        color: var(--muted);
        padding: 5px 11px 5px 9px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: var(--panel);
        white-space: nowrap;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--ok);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--ok) 20%, transparent);
    }

    .status-pill[data-state="busy"] .status-dot {
        background: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-soft);
        animation: pulse 1.3s ease-in-out infinite;
    }

    .status-pill[data-state="error"] .status-dot {
        background: var(--danger);
        box-shadow: 0 0 0 3px var(--danger-soft);
    }

    .cost-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text);
        padding: 5px 11px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: var(--panel);
        white-space: nowrap;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .45; transform: scale(.8); }
    }

    .icon-sun, .icon-moon { display: none; }
    html[data-theme="dark"] .icon-sun { display: block; }
    html[data-theme="light"] .icon-moon { display: block; }

    /* ------------------------------------------------------------- progress */

    .progress-track {
        flex: 0 0 auto;
        height: 2px;
        width: 100%;
        background: transparent;
        overflow: hidden;
        margin-top: -1px;
    }

    .progress-track[hidden] { display: none; }

    .progress-bar {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, var(--accent), var(--accent-2));
        border-radius: 2px;
        transition: width .45s cubic-bezier(.4, 0, .2, 1);
    }

    /* -------------------------------------------------------------- messages */

    #messages {
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 24px 2px 18px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        scrollbar-width: thin;
        scrollbar-color: var(--line-strong) transparent;
        overscroll-behavior: contain;
        direction: ltr;
    }

    #messages::-webkit-scrollbar { width: 10px; }
    #messages::-webkit-scrollbar-track { background: transparent; }
    #messages::-webkit-scrollbar-thumb {
        background: var(--line);
        border-radius: 8px;
        border: 3px solid transparent;
        background-clip: content-box;
    }
    #messages::-webkit-scrollbar-thumb:hover { background: var(--line-strong); background-clip: content-box; }

    .msg {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        animation: rise .3s cubic-bezier(.2, .8, .3, 1) both;
    }

    @keyframes rise {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .msg-user { flex-direction: row-reverse; }
    .msg-system { justify-content: center; }
    .msg-doc { flex-direction: column; gap: 10px; width: 100%; }

    .avatar {
        width: 30px;
        height: 30px;
        flex: 0 0 auto;
        border-radius: 9px;
        display: grid;
        place-items: center;
        background: var(--panel-2);
        border: 1px solid var(--line);
        color: var(--accent);
        margin-top: 2px;
    }

    .avatar svg { width: 15px; height: 15px; display: block; }

    .msg-body { min-width: 0; max-width: 78%; display: flex; flex-direction: column; gap: 5px; }
    .msg-user .msg-body { align-items: flex-end; max-width: 78%; }
    .msg-doc .msg-body { max-width: 100%; width: 100%; }

    .bubble {
        padding: 11px 15px;
        border-radius: var(--radius);
        background: var(--panel);
        border: 1px solid var(--line);
        word-wrap: break-word;
        overflow-wrap: anywhere;
        box-shadow: var(--shadow);
    }

    html[dir="rtl"] .bubble,
    html[dir="rtl"] .doc-body { direction: rtl; }

    .bubble > :first-child { margin-top: 0; }
    .bubble > :last-child { margin-bottom: 0; }
    .bubble p { margin: 8px 0; }
    .bubble ul, .bubble ol { margin: 8px 0; padding-inline-start: 22px; }
    .bubble li { margin: 3px 0; }
    .bubble h1, .bubble h2, .bubble h3, .bubble h4 { margin: 14px 0 6px; line-height: 1.3; }
    .bubble h1 { font-size: 18px; }
    .bubble h2 { font-size: 15px; }
    .bubble h3 { font-size: 14px; }
    .bubble hr { border: none; border-top: 1px solid var(--line); margin: 14px 0; }
    .bubble blockquote {
        margin: 10px 0;
        padding-block: 2px;
        padding-inline-start: 13px;
        border-inline-start: 3px solid var(--line-strong);
        color: var(--muted);
    }
    .bubble a { color: var(--accent); text-decoration: none; border-bottom: 1px solid var(--accent-soft); }
    .bubble a:hover { border-bottom-color: var(--accent); }
    .bubble code {
        font-family: var(--mono);
        font-size: 12.5px;
        background: var(--code-bg);
        padding: 2px 6px;
        border-radius: 5px;
        border: 1px solid var(--line);
    }
    .bubble pre {
        background: var(--code-bg);
        border: 1px solid var(--line);
        padding: 12px 14px;
        border-radius: 10px;
        overflow-x: auto;
        margin: 10px 0;
    }
    .bubble pre code {
        background: transparent;
        border: none;
        padding: 0;
        font-size: 12.5px;
        line-height: 1.55;
    }

    .msg-user .bubble {
        background: linear-gradient(160deg, var(--accent), var(--accent-2));
        border-color: transparent;
        color: #fff;
        box-shadow: 0 10px 26px -16px var(--accent);
    }
    .msg-user .bubble code {
        background: rgba(255, 255, 255, .18);
        border-color: rgba(255, 255, 255, .22);
        color: #fff;
    }
    .msg-user .bubble a { color: #fff; border-bottom-color: rgba(255, 255, 255, .5); }

    .msg-system .bubble {
        background: var(--danger-soft);
        border-color: color-mix(in srgb, var(--danger) 40%, transparent);
        color: var(--danger);
        font-size: 13px;
        max-width: 88%;
        text-align: center;
        box-shadow: none;
    }

    .meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        color: var(--muted);
        padding: 0 4px;
        min-height: 20px;
    }

    .msg-user .meta { flex-direction: row-reverse; }

    .msg-tools {
        display: inline-flex;
        gap: 4px;
        opacity: 0;
        transition: opacity .15s ease;
    }

    .msg:hover .msg-tools,
    .msg:focus-within .msg-tools { opacity: 1; }

    @media (hover: none) {
        .msg-tools { opacity: 1; }
    }

    .tool-btn {
        border: none;
        background: transparent;
        color: var(--muted);
        padding: 2px 6px;
        border-radius: 6px;
        font-size: 11px;
        line-height: 1.4;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .tool-btn:hover:not(:disabled) {
        background: var(--panel-2);
        border-color: transparent;
        color: var(--text);
    }

    .tool-btn svg { width: 12px; height: 12px; display: block; }

    /* --------------------------------------------------------- typing dots */

    .typing .bubble {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 13px 16px;
    }

    .typing-dots { display: inline-flex; gap: 5px; align-items: center; }

    .typing-dots span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--muted);
        animation: blink 1.25s infinite ease-in-out both;
    }

    .typing-dots span:nth-child(2) { animation-delay: .15s; }
    .typing-dots span:nth-child(3) { animation-delay: .3s; }

    .typing-label { font-size: 12.5px; color: var(--muted); }

    @keyframes blink {
        0%, 80%, 100% { opacity: .22; transform: translateY(0); }
        40% { opacity: 1; transform: translateY(-3px); }
    }

    /* ------------------------------------------------------------ document */

    .doc-card {
        width: 100%;
        background: var(--doc-bg);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        animation: rise .34s cubic-bezier(.2, .8, .3, 1) both;
    }

    .doc-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 11px 14px;
        border-bottom: 1px solid var(--line);
        background: var(--panel-2);
    }

    .doc-head-left {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .5px;
        text-transform: uppercase;
        color: var(--muted);
    }

    .doc-head-left svg { width: 14px; height: 14px; display: block; color: var(--accent); }

    .doc-tools { display: flex; gap: 7px; }

    .doc-tools button { font-size: 12px; padding: 6px 11px; }

    .doc-body {
        padding: 24px 26px 28px;
        overflow-x: auto;
    }

    .doc-body > :first-child { margin-top: 0; }
    .doc-body > :last-child { margin-bottom: 0; }

    .doc-body h1 {
        font-size: 21px;
        margin: 0 0 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--line);
        letter-spacing: -.2px;
    }

    .doc-body h2 {
        font-size: 12px;
        margin: 24px 0 8px;
        color: var(--accent);
        text-transform: uppercase;
        letter-spacing: .8px;
        font-weight: 700;
    }

    .doc-body h3 { font-size: 14px; margin: 16px 0 6px; }
    .doc-body p { margin: 9px 0; }
    .doc-body ul, .doc-body ol { margin: 9px 0; padding-inline-start: 22px; }
    .doc-body li { margin: 4px 0; }

    .doc-body hr {
        border: none;
        border-top: 1px solid var(--line);
        margin: 22px 0;
    }

    .doc-body code {
        font-family: var(--mono);
        font-size: 12.5px;
        background: var(--code-bg);
        padding: 2px 6px;
        border-radius: 5px;
        border: 1px solid var(--line);
    }

    .doc-body pre {
        background: var(--code-bg);
        border: 1px solid var(--line);
        padding: 13px 15px;
        border-radius: 10px;
        overflow-x: auto;
    }

    .doc-body pre code { background: transparent; border: none; padding: 0; }

    .doc-body blockquote {
        margin: 12px 0;
        padding-block: 2px;
        padding-inline-start: 14px;
        border-inline-start: 3px solid var(--line-strong);
        color: var(--muted);
    }

    .doc-body a { color: var(--accent); text-decoration: none; border-bottom: 1px solid var(--accent-soft); }
    .doc-body a:hover { border-bottom-color: var(--accent); }

    .doc-body table {
        width: 100%;
        border-collapse: collapse;
        margin: 12px 0;
        font-size: 13.5px;
    }

    .doc-body th, .doc-body td {
        border: 1px solid var(--line);
        padding: 8px 11px;
        text-align: start;
        vertical-align: top;
    }

    .doc-body th { background: var(--panel-2); font-weight: 600; }

    .check {
        color: var(--accent);
        font-family: var(--mono);
        margin-inline-end: 6px;
        font-weight: 700;
    }

    /* ------------------------------------------------------------ composer */

    footer.app-footer {
        flex: 0 0 auto;
        border-top: 1px solid var(--line);
        padding: 12px 2px max(14px, env(safe-area-inset-bottom));
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .error {
        display: none;
        align-items: flex-start;
        gap: 9px;
        color: var(--danger);
        font-size: 13px;
        padding: 9px 12px;
        background: var(--danger-soft);
        border: 1px solid color-mix(in srgb, var(--danger) 38%, transparent);
        border-radius: var(--radius-sm);
        animation: rise .2s ease both;
    }

    .error.visible { display: flex; }
    .error svg { width: 15px; height: 15px; flex: 0 0 auto; margin-top: 2px; }
    .error-close {
        margin-inline-start: auto;
        border: none;
        background: transparent;
        color: inherit;
        padding: 0 2px;
        font-size: 15px;
        line-height: 1;
        opacity: .7;
    }
    .error-close:hover { background: transparent; opacity: 1; border-color: transparent; }

    .chips {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .chips[hidden] { display: none; }

    .chip {
        font-size: 12.5px;
        padding: 6px 12px;
        border-radius: 999px;
        background: var(--panel);
        border: 1px solid var(--line);
        color: var(--muted);
        transition: color .15s ease, border-color .15s ease, background .15s ease;
    }

    .chip:hover:not(:disabled) {
        color: var(--text);
        border-color: var(--accent);
        background: var(--accent-soft);
    }

    .composer {
        display: flex;
        gap: 9px;
        align-items: flex-end;
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 7px 7px 7px 14px;
        transition: border-color .16s ease, box-shadow .16s ease;
    }

    .composer:focus-within {
        border-color: var(--accent);
        box-shadow: 0 0 0 4px var(--accent-soft);
    }

    textarea#input {
        flex: 1 1 auto;
        resize: none;
        min-height: 40px;
        max-height: 200px;
        background: transparent;
        border: none;
        color: var(--text);
        padding: 9px 0;
        font-family: inherit;
        font-size: 14.5px;
        line-height: 1.5;
        outline: none;
        overflow-y: auto;
        scrollbar-width: thin;
    }

    textarea#input::placeholder { color: var(--muted); }
    textarea#input:disabled { opacity: .55; }

    .footer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .hint {
        font-size: 11.5px;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    kbd {
        font-family: var(--mono);
        font-size: 10.5px;
        padding: 1px 5px;
        border-radius: 5px;
        border: 1px solid var(--line);
        background: var(--panel-2);
        color: var(--muted);
        line-height: 1.6;
    }

    /* --------------------------------------------------------- jump button */

    #jump {
        position: absolute;
        inset-inline-end: 22px;
        bottom: 168px;
        width: 38px;
        height: 38px;
        padding: 0;
        justify-content: center;
        border-radius: 50%;
        background: var(--panel);
        box-shadow: var(--shadow);
        opacity: 0;
        pointer-events: none;
        transform: translateY(8px);
        transition: opacity .2s ease, transform .2s ease;
        z-index: 5;
    }

    #jump.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
    #jump svg { width: 16px; height: 16px; }

    /* -------------------------------------------------------------- toasts */

    .toast-wrap {
        position: fixed;
        inset-inline-end: 20px;
        bottom: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 60;
        pointer-events: none;
    }

    .toast {
        background: var(--panel);
        border: 1px solid var(--line-strong);
        color: var(--text);
        font-size: 13px;
        padding: 9px 14px;
        border-radius: var(--radius-sm);
        box-shadow: var(--shadow);
        opacity: 0;
        transform: translateY(10px);
        transition: opacity .22s ease, transform .22s ease;
        max-width: 280px;
    }

    .toast.in { opacity: 1; transform: translateY(0); }
    .toast.ok { border-color: color-mix(in srgb, var(--ok) 55%, transparent); }
    .toast.err { border-color: color-mix(in srgb, var(--danger) 55%, transparent); color: var(--danger); }

    /* ------------------------------------------------------------- history */

    .history {
        position: fixed;
        inset-block: 0;
        inset-inline-start: 0;
        width: 300px;
        max-width: 88vw;
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 16px 14px;
        background: var(--panel);
        border-inline-end: 1px solid var(--line);
        box-shadow: var(--shadow);
        overflow: hidden;
        z-index: 40;
    }

    .history[hidden] { display: none; }

    .history-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .history-heading {
        font-size: 13px;
        font-weight: 650;
        letter-spacing: .2px;
    }

    .history-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    .history-item {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 3px;
        width: 100%;
        text-align: start;
        padding: 9px 11px;
        background: var(--panel-2);
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
    }

    .history-item[aria-current="true"] {
        background: var(--accent-soft);
        border-color: var(--line-strong);
    }

    .history-item-title {
        font-size: 13px;
        font-weight: 600;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .history-item-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        color: var(--muted);
    }

    .history-empty {
        font-size: 12px;
        color: var(--muted);
    }

    .history-empty[hidden] { display: none; }

    .history-new {
        justify-content: center;
    }

    /* ---------------------------------------------------------- responsive */

    @media (max-width: 720px) {
        #app { padding: 0 12px; }
        .brand-sub { display: none; }
        .status-pill .status-text { display: none; }
        .status-pill { padding: 5px 9px; }
        .msg-body { max-width: 88%; }
        .doc-body { padding: 18px 16px 22px; }
        #jump { inset-inline-end: 16px; bottom: 176px; }
        .hint { display: none; }
    }

    @media (max-width: 420px) {
        .brand-mark { width: 32px; height: 32px; border-radius: 9px; }
        .footer-row { justify-content: stretch; }
        .footer-row #finalize { width: 100%; justify-content: center; }
    }

    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: .001ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .001ms !important;
            scroll-behavior: auto !important;
        }
    }
</style>
</head>
<body>

<div id="app">
    <header class="app-header">
        <div class="brand">
            <div class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"></path>
                    <path d="M18.5 16.5l.7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7.7-1.8z"></path>
                </svg>
            </div>
            <div class="brand-text">
                <div class="brand-title">
                    UX Agent
                    <span class="badge" id="demo-badge" hidden>{{ __('Demo') }}</span>
                </div>
                <div class="brand-sub">One question at a time, until the brief is complete.</div>
            </div>
        </div>

        <div class="header-actions">
            <button type="button" id="history-toggle" class="icon ghost" title="Chat history" aria-label="Chat history" aria-expanded="false" aria-controls="history">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 3V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </button>

            <span class="cost-pill" id="cost" title="{{ __('Spent in this chat (Toman)') }}" aria-live="polite">0 {{ __('Toman') }}</span>

            <span class="status-pill" id="status" data-state="idle" role="status" aria-live="polite">
                <span class="status-dot" aria-hidden="true"></span>
                <span class="status-text" id="status-text">{{ __('Ready') }}</span>
            </span>

            <form method="GET" action="{{ route('chat.index') }}">
                <input type="hidden" name="lang" value="{{ app()->getLocale() === 'fa' ? 'en' : 'fa' }}">
                <button type="submit" class="ghost" title="{{ __('Switch language') }}" aria-label="{{ __('Switch language') }}">{{ app()->getLocale() === 'fa' ? 'EN' : 'FA' }}</button>
            </form>

            <button type="button" id="theme-toggle" class="icon ghost" title="{{ __('Toggle light / dark theme') }}" aria-label="{{ __('Toggle light or dark theme') }}">
                <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path>
                </svg>
                <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path>
                </svg>
            </button>

            <button type="button" id="reset" class="ghost" title="{{ __('Clear the conversation') }}" aria-label="{{ __('Reset conversation') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true">
                    <path d="M3 12a9 9 0 1 0 3-6.7L3 8"></path>
                    <path d="M3 3v5h5"></path>
                </svg>
                {{ __('Reset') }}
            </button>
        </div>
    </header>

    <aside class="history" id="history" hidden aria-label="{{ __('Chat history') }}">
        <div class="history-head">
            <span class="history-heading">{{ __('Chats') }}</span>
            <button type="button" id="history-close" class="icon ghost" title="{{ __('Close chat history') }}" aria-label="{{ __('Close chat history') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>
        <button type="button" id="history-new" class="primary">{{ __('New chat') }}</button>
        <div class="history-empty" id="history-empty" hidden>{{ __('No saved chats yet.') }}</div>
        <div class="history-list" id="history-list"></div>
    </aside>

    <div class="progress-track" id="progress-track" hidden aria-hidden="true">
        <div class="progress-bar" id="progress-bar"></div>
    </div>

    <main id="messages" role="log" aria-live="polite" aria-label="Conversation" tabindex="-1"></main>

    <button type="button" id="jump" class="ghost" title="{{ __('Jump to the latest message') }}" aria-label="{{ __('Jump to the latest message') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 5v14M19 12l-7 7-7-7"></path>
        </svg>
    </button>

    <footer class="app-footer">
        <div class="error" id="error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 8v5M12 16.5v.01"></path>
            </svg>
            <span id="error-text"></span>
            <button type="button" class="error-close" id="error-close" aria-label="{{ __('Dismiss error') }}">&times;</button>
        </div>

        <div class="chips" id="chips" aria-label="{{ __('Example prompts') }}"></div>

        <div class="composer">
            <textarea
                id="input"
                rows="1"
                maxlength="4000"
                placeholder="{{ __('Describe the idea or problem. Press Enter to send, Shift+Enter for a new line.') }}"
                autocomplete="off"
                autocapitalize="sentences"
                spellcheck="true"
                aria-label="{{ __('Message') }}"
            ></textarea>
            <button type="button" id="send" class="primary">{{ __('Send') }}</button>
        </div>

        <div class="footer-row">
            <span class="hint">
                <kbd>Enter</kbd> {{ __('send') }}
                <kbd>Shift</kbd>+<kbd>Enter</kbd> {{ __('new line') }}
                <kbd>Ctrl</kbd>+<kbd>Enter</kbd> {{ __('finalize') }}
            </span>
            <button type="button" id="finalize" title="{{ __('Ask the agent to produce the final document') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"></path>
                    <path d="M14 3v5h5"></path>
                </svg>
                {{ __('Finalize output') }}
            </button>
        </div>
    </footer>

    <div class="toast-wrap" id="toasts" aria-live="polite" aria-atomic="true"></div>
</div>

<noscript>
    <div style="padding:24px;text-align:center;color:#e5544b;">
        {{ __('This interface requires JavaScript to run.') }}
    </div>
</noscript>

<!--
    Routes are injected by Laravel. If this file is opened outside of Blade the
    payload is not valid JSON, the client detects that and falls back to a fully
    self-contained demo agent so the interface stays usable.
-->
@php($appRoutes = ["send" => route("chat.send"), "finalize" => route("chat.finalize"), "reset" => route("chat.reset"), "history" => route("chat.history")])
<script type="application/json" id="app-routes">@json($appRoutes)</script>
@php($appI18n = is_array($messages = trans('*')) ? $messages : [])
<script type="application/json" id="app-i18n">@json($appI18n)</script>

<script>
(function () {
    'use strict';

    /* =====================================================================
       1. Environment / configuration
       ===================================================================== */

    var MAX_LENGTH = 4000;
    var DEMO_TOTAL_QUESTIONS = 6;

    function readRoutes() {
        var node = document.getElementById('app-routes');
        if (!node) {
            return null;
        }
        try {
            var parsed = JSON.parse(node.textContent.trim());
            if (parsed && typeof parsed.send === 'string' && parsed.send.indexOf('{{') === -1) {
                return parsed;
            }
            return null;
        } catch (err) {
            return null;
        }
    }

    var forcedDemo = /[?&]demo=1(?:&|$)/.test(window.location.search);
    var injectedRoutes = readRoutes();
    var demoMode = forcedDemo || injectedRoutes === null;

    var ROUTES = demoMode
        ? { send: '#demo/send', finalize: '#demo/finalize', reset: '#demo/reset', history: '#demo/history' }
        : injectedRoutes;

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var CSRF = csrfMeta ? csrfMeta.getAttribute('content') : '';
    if (!CSRF || CSRF.indexOf('{{') !== -1) {
        CSRF = '';
    }

    var I18N = readI18n();

    function readI18n() {
        var node = document.getElementById('app-i18n');
        if (!node) {
            return {};
        }
        try {
            var parsed = JSON.parse(node.textContent.trim());

            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (err) {
            return {};
        }
    }

    function t(key) {
        return typeof I18N[key] === 'string' ? I18N[key] : key;
    }

    var prefersReducedMotion = window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
        : false;

    /* =====================================================================
       2. DOM references & state
       ===================================================================== */

    var els = {
        messages: document.getElementById('messages'),
        input: document.getElementById('input'),
        send: document.getElementById('send'),
        finalize: document.getElementById('finalize'),
        reset: document.getElementById('reset'),
        themeToggle: document.getElementById('theme-toggle'),
        error: document.getElementById('error'),
        errorText: document.getElementById('error-text'),
        errorClose: document.getElementById('error-close'),
        status: document.getElementById('status'),
        statusText: document.getElementById('status-text'),
        cost: document.getElementById('cost'),
        history: document.getElementById('history'),
        historyToggle: document.getElementById('history-toggle'),
        historyClose: document.getElementById('history-close'),
        historyList: document.getElementById('history-list'),
        historyEmpty: document.getElementById('history-empty'),
        historyNew: document.getElementById('history-new'),
        progressTrack: document.getElementById('progress-track'),
        progressBar: document.getElementById('progress-bar'),
        chips: document.getElementById('chips'),
        jump: document.getElementById('jump'),
        toasts: document.getElementById('toasts'),
        demoBadge: document.getElementById('demo-badge')
    };

    var state = {
        sending: false,
        finalizing: false,
        controller: null,
        started: false,
        cost: 0,
        chats: [],
        chatId: null
    };

    var SUGGESTIONS = [
        t('A habit tracker for new runners'),
        t('Redesign the checkout flow for a grocery app'),
        t('Onboarding for a developer tool')
    ];

    /* =====================================================================
       3. Small utilities
       ===================================================================== */

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (c) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[c];
        });
    }

    function unescapeEntities(value) {
        return String(value)
            .replace(/&amp;/g, '&')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#39;/g, "'");
    }

    function sanitizeUrl(url) {
        var raw = unescapeEntities(String(url || '').trim());
        if (/^https?:\/\//i.test(raw)) { return raw; }
        if (/^mailto:/i.test(raw) || /^tel:/i.test(raw)) { return raw; }
        if (/^#/.test(raw) || /^\/(?!\/)/.test(raw) || /^\.\.?\//.test(raw)) { return raw; }
        return '#';
    }

    function pad2(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function formatTime(date) {
        var d = date || new Date();
        var hours = d.getHours();
        var suffix = hours >= 12 ? 'PM' : 'AM';
        var h12 = hours % 12;
        if (h12 === 0) { h12 = 12; }
        return h12 + ':' + pad2(d.getMinutes()) + ' ' + suffix;
    }

    function timestampSlug() {
        var d = new Date();
        return '' + d.getFullYear() + pad2(d.getMonth() + 1) + pad2(d.getDate()) +
            '-' + pad2(d.getHours()) + pad2(d.getMinutes());
    }

    function abortError() {
        var err = new Error('Request cancelled.');
        err.name = 'AbortError';
        return err;
    }

    /* =====================================================================
       4. Markdown renderer (safe: everything is escaped first)
       ===================================================================== */

    function renderInline(text) {
        var out = escapeHtml(text);
        var codes = [];

        out = out.replace(/`([^`]+)`/g, function (match, code) {
            codes.push(code);
            return '\u0000C' + (codes.length - 1) + '\u0000';
        });

        out = out.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (match, label, url) {
            var href = sanitizeUrl(url);
            if (href === '#') {
                return label;
            }
            return '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer">' + label + '</a>';
        });

        out = out.replace(/(^|[\s(])((?:https?:\/\/)[^\s<)]+)/g, function (match, lead, url) {
            return lead + '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
        });

        out = out.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
        out = out.replace(/__([^_\n]+)__/g, '<strong>$1</strong>');
        out = out.replace(/(^|[^*\w])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>');
        out = out.replace(/(^|[^_\w])_([^_\n]+)_(?!_)/g, '$1<em>$2</em>');
        out = out.replace(/~~([^~\n]+)~~/g, '<del>$1</del>');

        out = out.replace(/\u0000C(\d+)\u0000/g, function (match, index) {
            return '<code>' + codes[Number(index)] + '</code>';
        });

        return out;
    }

    function splitTableRow(line) {
        var trimmed = String(line).trim();
        if (trimmed.charAt(0) === '|') { trimmed = trimmed.slice(1); }
        if (trimmed.charAt(trimmed.length - 1) === '|') { trimmed = trimmed.slice(0, -1); }
        return trimmed.split('|').map(function (cell) { return cell.trim(); });
    }

    function isTableSeparator(line) {
        var trimmed = String(line).trim();
        if (trimmed.indexOf('-') === -1 || trimmed.indexOf('|') === -1) {
            return false;
        }
        return /^\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?$/.test(trimmed);
    }

    function renderMarkdown(source) {
        var text = String(source === null || source === undefined ? '' : source);
        var lines = text.replace(/\t/g, '    ').replace(/\r\n?/g, '\n').split('\n');
        var html = [];
        var listStack = [];
        var liOpen = false;
        var i = 0;

        function closeLists() {
            while (listStack.length) {
                html.push('</li></' + listStack.pop().tag + '>');
            }
            liOpen = false;
        }

        function openItem(tag, indent, content) {
            if (liOpen && listStack.length && indent > listStack[listStack.length - 1].indent) {
                listStack.push({ tag: tag, indent: indent });
                html.push('<' + tag + '><li>' + content);
                liOpen = true;
                return;
            }

            while (listStack.length && listStack[listStack.length - 1].indent > indent) {
                html.push('</li></' + listStack.pop().tag + '>');
                liOpen = listStack.length > 0;
            }

            if (liOpen) {
                html.push('</li>');
                liOpen = false;
            }

            if (!listStack.length || listStack[listStack.length - 1].indent < indent) {
                listStack.push({ tag: tag, indent: indent });
                html.push('<' + tag + '>');
            }

            html.push('<li>' + content);
            liOpen = true;
        }

        while (i < lines.length) {
            var line = lines[i];

            /* --- fenced code blocks --- */
            var fence = line.match(/^\s*```\s*([A-Za-z0-9_+-]*)\s*$/);
            if (fence) {
                closeLists();
                var lang = fence[1];
                var buffer = [];
                i++;
                while (i < lines.length && !/^\s*```\s*$/.test(lines[i])) {
                    buffer.push(lines[i]);
                    i++;
                }
                i++;
                html.push(
                    '<pre><code' + (lang ? ' data-lang="' + escapeHtml(lang) + '"' : '') + '>' +
                    escapeHtml(buffer.join('\n')) +
                    '</code></pre>'
                );
                continue;
            }

            /* --- blank line --- */
            if (line.trim() === '') {
                closeLists();
                i++;
                continue;
            }

            /* --- headings --- */
            var heading = line.match(/^(#{1,6})\s+(.*)$/);
            if (heading) {
                closeLists();
                var level = heading[1].length;
                html.push('<h' + level + '>' + renderInline(heading[2].trim()) + '</h' + level + '>');
                i++;
                continue;
            }

            /* --- horizontal rule --- */
            if (/^\s*(-{3,}|\*{3,}|_{3,})\s*$/.test(line)) {
                closeLists();
                html.push('<hr>');
                i++;
                continue;
            }

            /* --- tables --- */
            if (line.indexOf('|') !== -1 && i + 1 < lines.length && isTableSeparator(lines[i + 1])) {
                closeLists();
                var headerCells = splitTableRow(line);
                i += 2;
                var bodyRows = [];
                while (i < lines.length && lines[i].indexOf('|') !== -1 && lines[i].trim() !== '') {
                    bodyRows.push(splitTableRow(lines[i]));
                    i++;
                }
                var table = '<div class="table-wrap"><table><thead><tr>';
                for (var h = 0; h < headerCells.length; h++) {
                    table += '<th>' + renderInline(headerCells[h]) + '</th>';
                }
                table += '</tr></thead><tbody>';
                for (var r = 0; r < bodyRows.length; r++) {
                    table += '<tr>';
                    for (var c = 0; c < headerCells.length; c++) {
                        table += '<td>' + renderInline(bodyRows[r][c] === undefined ? '' : bodyRows[r][c]) + '</td>';
                    }
                    table += '</tr>';
                }
                table += '</tbody></table></div>';
                html.push(table);
                continue;
            }

            /* --- blockquote --- */
            if (/^\s*>\s?/.test(line)) {
                closeLists();
                var quoteLines = [];
                while (i < lines.length && /^\s*>\s?/.test(lines[i])) {
                    quoteLines.push(lines[i].replace(/^\s*>\s?/, ''));
                    i++;
                }
                html.push('<blockquote>' + renderInline(quoteLines.join('\n')).replace(/\n/g, '<br>') + '</blockquote>');
                continue;
            }

            /* --- lists --- */
            var item = line.match(/^(\s*)([-*+]|\d+[.)])\s+(.*)$/);
            if (item) {
                var indent = item[1].length;
                var ordered = /\d/.test(item[2]);
                var content = item[3];

                var checkbox = content.match(/^\[( |x|X)\]\s+(.*)$/);
                var prefix = '';
                if (checkbox) {
                    var checked = checkbox[1].toLowerCase() === 'x';
                    prefix = '<span class="check" aria-hidden="true">' + (checked ? '&#10003;' : '&#9744;') + '</span>';
                    content = checkbox[2];
                }

                openItem(ordered ? 'ol' : 'ul', indent, prefix + renderInline(content));
                i++;
                continue;
            }

            /* --- paragraph --- */
            closeLists();
            var paragraph = [line];
            i++;
            while (
                i < lines.length &&
                lines[i].trim() !== '' &&
                !/^\s*```/.test(lines[i]) &&
                !/^#{1,6}\s+/.test(lines[i]) &&
                !/^\s*>\s?/.test(lines[i]) &&
                !/^(\s*)([-*+]|\d+[.)])\s+/.test(lines[i]) &&
                !/^\s*(-{3,}|\*{3,}|_{3,})\s*$/.test(lines[i])
            ) {
                paragraph.push(lines[i]);
                i++;
            }
            html.push('<p>' + renderInline(paragraph.join('\n')).replace(/\n/g, '<br>') + '</p>');
        }

        closeLists();
        return html.join('');
    }

    /* =====================================================================
       5. Theme
       ===================================================================== */

    var THEME_KEY = 'ux-agent-theme';

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        try {
            window.localStorage.setItem(THEME_KEY, theme);
        } catch (err) {
            /* storage unavailable — ignore */
        }
    }

    function initTheme() {
        var stored = null;
        try {
            stored = window.localStorage.getItem(THEME_KEY);
        } catch (err) {
            stored = null;
        }
        if (stored === 'light' || stored === 'dark') {
            applyTheme(stored);
            return;
        }
        var prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
        applyTheme(prefersLight ? 'light' : 'dark');
    }

    els.themeToggle.addEventListener('click', function () {
        var current = document.documentElement.getAttribute('data-theme');
        applyTheme(current === 'light' ? 'dark' : 'light');
    });

    /* =====================================================================
       Chat history & cost
       ===================================================================== */

    var CHATS_KEY = 'ux-agent-chats';
    var CHAT_ID_KEY = 'ux-agent-chat-id';

    function readLocalChats() {
        var stored = null;
        try {
            stored = window.localStorage.getItem(CHATS_KEY);
        } catch (err) {
            stored = null;
        }
        var parsed = null;
        try {
            parsed = JSON.parse(stored);
        } catch (err) {
            parsed = null;
        }
        return Array.isArray(parsed) ? parsed : [];
    }

    function saveLocalChats() {
        var local = state.chats.filter(function (chat) {
            return chat.storage === 'local';
        });
        try {
            window.localStorage.setItem(CHATS_KEY, JSON.stringify(local));
        } catch (err) {
            /* storage unavailable — ignore */
        }
    }

    function readChatId() {
        var stored = null;
        try {
            stored = window.localStorage.getItem(CHAT_ID_KEY);
        } catch (err) {
            stored = null;
        }
        if (!stored) {
            return null;
        }
        var numeric = parseInt(stored, 10);
        return isFinite(numeric) && String(numeric) === stored ? numeric : stored;
    }

    function setChatId(id) {
        state.chatId = id === undefined ? null : id;
        try {
            window.localStorage.setItem(CHAT_ID_KEY, state.chatId === null ? '' : String(state.chatId));
        } catch (err) {
            /* storage unavailable — ignore */
        }
    }

    function conversationId() {
        return typeof state.chatId === 'number' ? state.chatId : null;
    }

    function findChat(id) {
        for (var i = 0; i < state.chats.length; i++) {
            if (state.chats[i].id === id) {
                return state.chats[i];
            }
        }
        return null;
    }

    function currentChat() {
        return findChat(state.chatId);
    }

    function chatCost(chat) {
        var cost = chat ? parseFloat(chat.cost) : 0;

        return isFinite(cost) && cost > 0 ? cost : 0;
    }

    function chatTitle(chat) {
        if (chat && typeof chat.title === 'string' && chat.title.trim() !== '') {
            return chat.title.trim();
        }

        var messages = (chat && chat.messages) || [];
        for (var i = 0; i < messages.length; i++) {
            if (messages[i].role === 'user' && typeof messages[i].content === 'string') {
                return messages[i].content.slice(0, 80);
            }
        }

        return t('New chat');
    }

    function formatCost(cost) {
        return cost.toFixed(2) + ' ' + t('Toman');
    }

    function renderCost() {
        state.cost = chatCost(currentChat());
        els.cost.textContent = formatCost(state.cost);
    }

    function renderHistory() {
        els.historyList.innerHTML = '';
        els.historyEmpty.hidden = state.chats.length > 0;

        state.chats.forEach(function (chat) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'history-item';
            item.title = chatTitle(chat);
            if (chat.id === state.chatId) {
                item.setAttribute('aria-current', 'true');
            }

            var title = document.createElement('span');
            title.className = 'history-item-title';
            title.textContent = chatTitle(chat);

            var meta = document.createElement('span');
            meta.className = 'history-item-meta';

            var cost = document.createElement('span');
            cost.textContent = formatCost(chatCost(chat));

            var count = document.createElement('span');
            count.textContent = ((chat.messages || []).length) + ' ' + t('messages');

            meta.appendChild(cost);
            meta.appendChild(count);
            item.appendChild(title);
            item.appendChild(meta);
            item.addEventListener('click', function () {
                openChat(chat.id);
            });

            els.historyList.appendChild(item);
        });
    }

    function toggleHistory(open) {
        var next = typeof open === 'boolean' ? open : els.history.hidden;
        els.history.hidden = !next;
        els.historyToggle.setAttribute('aria-expanded', next ? 'true' : 'false');
    }

    function openChat(id) {
        var chat = findChat(id);
        if (!chat) {
            return;
        }

        setChatId(chat.id);
        els.messages.innerHTML = '';
        (chat.messages || []).forEach(function (message) {
            addMessage(message.role, message.content, {
                markdown: message.role === 'assistant',
                time: message.at
            });
        });
        hideChips();
        setError('');
        setStatus('Ready', 'idle');
        renderCost();
        renderHistory();
        toggleHistory(false);
        els.input.focus();
    }

    function syncCost(data) {
        var conversation = data && data.conversation;

        if (conversation && conversation.id) {
            var chat = findChat(conversation.id);
            if (chat !== null) {
                var total = parseFloat(conversation.cost);
                chat.cost = isFinite(total) ? total : chat.cost;
                if (typeof conversation.title === 'string' && conversation.title !== '') {
                    chat.title = conversation.title;
                }
            }
        } else {
            var current = currentChat();
            var delta = data ? parseFloat(data.cost) : 0;
            if (current !== null && isFinite(delta) && delta > 0) {
                current.cost = chatCost(current) + delta;
            }
        }

        saveLocalChats();
        renderCost();
        renderHistory();
    }

    function trackChat(data, question, reply) {
        var conversation = data && data.conversation;
        var stored = conversation && conversation.id ? conversation : null;
        var chat = stored ? findChat(stored.id) : null;

        if (chat === null) {
            chat = {
                id: stored ? stored.id : 'local-' + Date.now(),
                storage: stored ? 'db' : 'local',
                title: null,
                cost: 0,
                messages: []
            };
            state.chats.unshift(chat);
        }

        chat.messages = chat.messages || [];
        chat.messages.push({ role: 'user', content: question });
        chat.messages.push({ role: 'assistant', content: reply });

        if (!chat.title) {
            chat.title = question.slice(0, 80);
        }

        setChatId(chat.id);
        syncCost(data);
    }

    function refreshHistory() {
        if (demoMode) {
            renderHistory();
            return;
        }

        getJSON(ROUTES.history).then(function (data) {
            if (!data || data.ok !== true || !Array.isArray(data.chats)) {
                throw new Error('The server did not return a chat history.');
            }

            var local = state.chats.filter(function (chat) {
                return chat.storage !== 'db';
            });

            state.chats = data.chats.concat(local);
            renderHistory();
            renderCost();
        }).catch(function () {
            renderHistory();
            renderCost();
        });
    }

    state.chats = readLocalChats();
    setChatId(readChatId());
    renderCost();
    renderHistory();

    els.historyToggle.addEventListener('click', function () {
        toggleHistory();
    });

    els.historyClose.addEventListener('click', function () {
        toggleHistory(false);
    });

    els.historyNew.addEventListener('click', function () {
        toggleHistory(false);
        handleReset();
    });

    /* =====================================================================
       6. Toasts
       ===================================================================== */

    function toast(message, kind) {
        var el = document.createElement('div');
        el.className = 'toast' + (kind ? ' toast-' + kind : '');
        el.textContent = t(message);
        els.toasts.appendChild(el);

        window.requestAnimationFrame(function () {
            el.classList.add('in');
        });

        window.setTimeout(function () {
            el.classList.remove('in');
            window.setTimeout(function () {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 240);
        }, 2200);
    }

    /* =====================================================================
       7. Clipboard
       ===================================================================== */

    function copyText(value) {
        var text = String(value === null || value === undefined ? '' : value);

        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).catch(function () {
                return legacyCopy(text);
            });
        }
        return legacyCopy(text);
    }

    function legacyCopy(text) {
        return new Promise(function (resolve, reject) {
            try {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', 'readonly');
                ta.style.position = 'fixed';
                ta.style.top = '-1000px';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                ta.setSelectionRange(0, ta.value.length);
                var ok = document.execCommand('copy');
                document.body.removeChild(ta);
                if (ok) {
                    resolve();
                } else {
                    reject(new Error('Copy command was rejected.'));
                }
            } catch (err) {
                reject(err);
            }
        });
    }

    /* =====================================================================
       8. Status, progress, errors, chips
       ===================================================================== */

    function setStatus(text, stateName) {
        els.statusText.textContent = t(text);
        els.status.setAttribute('data-state', stateName || 'idle');
    }

    function setError(message) {
        if (!message) {
            els.errorText.textContent = '';
            els.error.classList.remove('visible');
            return;
        }
        els.errorText.textContent = t(message);
        els.error.classList.add('visible');
    }

    els.errorClose.addEventListener('click', function () {
        setError('');
    });

    function setProgress(done, total) {
        if (demoMode && total > 0) {
            els.progressTrack.hidden = false;
            var pct = Math.max(0, Math.min(100, Math.round((done / total) * 100)));
            els.progressBar.style.width = pct + '%';
        } else {
            els.progressTrack.hidden = true;
        }
    }

    function renderChips() {
        els.chips.innerHTML = '';
        if (state.started) {
            els.chips.hidden = true;
            return;
        }
        els.chips.hidden = false;
        SUGGESTIONS.forEach(function (text) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chip';
            btn.textContent = text;
            btn.addEventListener('click', function () {
                els.input.value = text;
                autosize();
                handleSend();
            });
            els.chips.appendChild(btn);
        });
    }

    function hideChips() {
        state.started = true;
        els.chips.hidden = true;
    }

    /* =====================================================================
       9. Message rendering
       ===================================================================== */

    var ICON_ASSISTANT =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"></path>' +
        '</svg>';

    var ICON_COPY =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<rect x="9" y="9" width="11" height="11" rx="2"></rect>' +
        '<path d="M5 15V5a2 2 0 0 1 2-2h10"></path>' +
        '</svg>';

    var ICON_DOWNLOAD =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M12 4v11M7 11l5 5 5-5M5 20h14"></path>' +
        '</svg>';

    function isNearBottom(threshold) {
        var el = els.messages;
        var limit = typeof threshold === 'number' ? threshold : 120;
        return (el.scrollHeight - el.scrollTop - el.clientHeight) <= limit;
    }

    function scrollToBottom(force) {
        var el = els.messages;
        var behavior = (prefersReducedMotion || !force) ? 'auto' : 'smooth';
        try {
            el.scrollTo({ top: el.scrollHeight, behavior: behavior });
        } catch (err) {
            el.scrollTop = el.scrollHeight;
        }
        updateJumpButton();
    }

    function updateJumpButton() {
        if (isNearBottom(140)) {
            els.jump.classList.remove('visible');
        } else {
            els.jump.classList.add('visible');
        }
    }

    els.jump.addEventListener('click', function () {
        scrollToBottom(true);
    });

    els.messages.addEventListener('scroll', updateJumpButton, { passive: true });

    window.addEventListener('resize', updateJumpButton);

    function makeToolButton(label, iconSvg, onClick) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'tool-btn';
        btn.innerHTML = iconSvg + '<span>' + escapeHtml(label) + '</span>';
        btn.addEventListener('click', onClick);
        return btn;
    }

    function addMessage(role, content, options) {
        var opts = options || {};
        var stick = isNearBottom();

        var row = document.createElement('div');
        row.className = 'msg msg-' + role;

        if (role === 'system') {
            var sysBubble = document.createElement('div');
            sysBubble.className = 'bubble';
            sysBubble.textContent = content;
            row.appendChild(sysBubble);
            els.messages.appendChild(row);
            if (stick) { scrollToBottom(true); }
            updateJumpButton();
            return row;
        }

        if (role === 'assistant') {
            var avatar = document.createElement('div');
            avatar.className = 'avatar';
            avatar.innerHTML = ICON_ASSISTANT;
            row.appendChild(avatar);
        }

        var body = document.createElement('div');
        body.className = 'msg-body';

        var bubble = document.createElement('div');
        bubble.className = 'bubble';

        if (opts.html) {
            bubble.innerHTML = content;
        } else if (opts.markdown) {
            bubble.innerHTML = renderMarkdown(content);
        } else {
            bubble.textContent = content;
        }

        var meta = document.createElement('div');
        meta.className = 'meta';

        var time = document.createElement('span');
        time.textContent = formatTime(opts.time ? new Date(opts.time) : new Date());
        meta.appendChild(time);

        if (role === 'assistant' && !opts.noTools) {
            var tools = document.createElement('span');
            tools.className = 'msg-tools';

            var copyBtn = makeToolButton('Copy', ICON_COPY, function () {
                copyText(content).then(function () {
                    toast('Message copied', 'ok');
                }).catch(function () {
                    toast('Could not copy', 'err');
                });
            });

            tools.appendChild(copyBtn);
            meta.appendChild(tools);
        }

        body.appendChild(bubble);
        body.appendChild(meta);
        row.appendChild(body);
        els.messages.appendChild(row);

        if (stick) {
            scrollToBottom(true);
        }
        updateJumpButton();
        return row;
    }

    function addTyping(label) {
        var stick = isNearBottom();

        var row = document.createElement('div');
        row.className = 'msg msg-assistant typing';

        var avatar = document.createElement('div');
        avatar.className = 'avatar';
        avatar.innerHTML = ICON_ASSISTANT;
        row.appendChild(avatar);

        var body = document.createElement('div');
        body.className = 'msg-body';

        var bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.innerHTML =
            '<span class="typing-dots" aria-hidden="true"><span></span><span></span><span></span></span>' +
            '<span class="typing-label">' + escapeHtml(t(label || 'Thinking')) + '…</span>';

        body.appendChild(bubble);
        row.appendChild(body);
        els.messages.appendChild(row);

        if (stick) {
            scrollToBottom(true);
        }
        return row;
    }

    function renderDocument(markdown, title) {
        var stick = isNearBottom();

        var row = document.createElement('div');
        row.className = 'msg msg-doc';

        var card = document.createElement('div');
        card.className = 'doc-card';

        var head = document.createElement('div');
        head.className = 'doc-head';

        var headLeft = document.createElement('div');
        headLeft.className = 'doc-head-left';
        headLeft.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"></path>' +
            '<path d="M14 3v5h5"></path></svg>' +
            '<span>' + escapeHtml(title || 'Final output') + '</span>';

        var tools = document.createElement('div');
        tools.className = 'doc-tools';

        var copyBtn = makeToolButton('Copy', ICON_COPY, function () {
            copyText(markdown).then(function () {
                toast('Output copied', 'ok');
            }).catch(function () {
                toast('Could not copy', 'err');
            });
        });

        var downloadBtn = makeToolButton('Download', ICON_DOWNLOAD, function () {
            try {
                var blob = new Blob([markdown], { type: 'text/markdown;charset=utf-8' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'ux-brief-' + timestampSlug() + '.md';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                toast('Download started', 'ok');
            } catch (err) {
                toast('Download failed', 'err');
            }
        });

        tools.appendChild(copyBtn);
        tools.appendChild(downloadBtn);

        head.appendChild(headLeft);
        head.appendChild(tools);

        var bodyEl = document.createElement('div');
        bodyEl.className = 'doc-body';
        bodyEl.innerHTML = renderMarkdown(markdown);

        card.appendChild(head);
        card.appendChild(bodyEl);
        row.appendChild(card);
        els.messages.appendChild(row);

        if (stick) {
            scrollToBottom(true);
        }
        updateJumpButton();
        return row;
    }

    /* =====================================================================
       10. Demo agent (used only when no backend routes are available)
       ===================================================================== */

    var demoAgent = (function () {
        var questions = [
            {
                key: 'idea',
                title: 'The idea',
                q: 'What are you building? Describe the product or feature in a sentence or two.'
            },
            {
                key: 'problem',
                title: 'The problem',
                q: 'What problem does it solve — and what do people do today instead?'
            },
            {
                key: 'audience',
                title: 'Primary audience',
                q: 'Who is the primary user? Describe one concrete person and the context they are in.'
            },
            {
                key: 'success',
                title: 'Success signal',
                q: 'How will you know it worked? Give one measurable signal you can observe.'
            },
            {
                key: 'constraints',
                title: 'Constraints',
                q: 'What constraints matter most — platform, timeline, team, compliance, existing systems?'
            },
            {
                key: 'scope',
                title: 'Out of scope',
                q: 'What is explicitly out of scope for this first release?'
            }
        ];

        var answers = {};
        var pending = questions[0];

        function nextUnanswered() {
            for (var i = 0; i < questions.length; i++) {
                if (!Object.prototype.hasOwnProperty.call(answers, questions[i].key)) {
                    return questions[i];
                }
            }
            return null;
        }

        function progress() {
            var count = 0;
            for (var i = 0; i < questions.length; i++) {
                if (Object.prototype.hasOwnProperty.call(answers, questions[i].key)) {
                    count++;
                }
            }
            return { done: count, total: questions.length };
        }

        function reset() {
            answers = {};
            pending = questions[0];
        }

        function reply(message) {
            var text = String(message === null || message === undefined ? '' : message).trim();

            if (!text) {
                return 'I did not catch that. Could you say it again?';
            }

            if (pending) {
                answers[pending.key] = text;
                pending = nextUnanswered();
            }

            if (!pending) {
                return 'I have everything I need. Hit **Finalize output** and I will assemble the brief.';
            }

            var index = questions.indexOf(pending) + 1;
            return '**Question ' + index + ' of ' + questions.length + '** — ' + pending.q;
        }

        function finalize() {
            var lines = [];

            lines.push('# UX Brief');
            lines.push('');
            lines.push('_Produced by the UX Agent in local demo mode. Connect the backend routes to replace this with a real synthesis._');
            lines.push('');

            for (var i = 0; i < questions.length; i++) {
                var q = questions[i];
                lines.push('## ' + q.title);
                lines.push('');
                lines.push(answers[q.key] ? answers[q.key] : '_Not provided._');
                lines.push('');
            }

            lines.push('---');
            lines.push('');
            lines.push('## Open questions');
            lines.push('');
            lines.push('- [ ] Which assumption above is riskiest, and how do we test it this week?');
            lines.push('- [ ] Who signs off on scope for the first release?');
            lines.push('');
            lines.push('## Next steps');
            lines.push('');
            lines.push('- [ ] Validate the problem statement with five target users');
            lines.push('- [ ] Sketch the primary end-to-end flow');
            lines.push('- [ ] Define success metrics and instrumentation');
            lines.push('- [ ] Review constraints with engineering');
            lines.push('');
            lines.push('_End of brief._');

            return lines.join('\n');
        }

        return {
            questions: questions,
            reply: reply,
            finalize: finalize,
            reset: reset,
            progress: progress
        };
    })();

    function demoRequest(kind, body, signal) {
        return new Promise(function (resolve, reject) {
            var delay = 420 + Math.round(Math.random() * 480);

            var timer = window.setTimeout(function () {
                try {
                    if (kind === 'send') {
                        resolve({ ok: true, reply: demoAgent.reply(body && body.message) });
                    } else if (kind === 'finalize') {
                        resolve({ ok: true, document: demoAgent.finalize() });
                    } else {
                        demoAgent.reset();
                        resolve({ ok: true });
                    }
                } catch (err) {
                    reject(err);
                }
            }, delay);

            if (signal) {
                if (signal.aborted) {
                    window.clearTimeout(timer);
                    reject(abortError());
                    return;
                }
                signal.addEventListener('abort', function () {
                    window.clearTimeout(timer);
                    reject(abortError());
                }, { once: true });
            }
        });
    }

    function updateDemoProgress() {
        if (!demoMode) {
            setProgress(0, 0);
            return;
        }
        var p = demoAgent.progress();
        setProgress(p.done, p.total);
    }

    /* =====================================================================
       11. Networking
       ===================================================================== */

    function postJSON(url, body, signal) {
        var headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (CSRF) {
            headers['X-CSRF-TOKEN'] = CSRF;
        }

        return fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(body || {}),
            credentials: 'same-origin',
            signal: signal
        }).then(function (response) {
            return response.text().then(function (raw) {
                var data = null;
                if (raw) {
                    try {
                        data = JSON.parse(raw);
                    } catch (err) {
                        data = null;
                    }
                }

                if (response.status === 419) {
                    throw new Error('Your session expired. Reload the page and try again.');
                }

                if (!response.ok) {
                    var message = (data && (data.error || data.message)) || ('Request failed with status ' + response.status + '.');
                    throw new Error(message);
                }

                if (!data) {
                    throw new Error('The server returned an unreadable response.');
                }

                if (data.ok === false) {
                    throw new Error(data.error || 'The request was rejected.');
                }

                return data;
            });
        });
    }

    function request(kind, body) {
        var signal = state.controller ? state.controller.signal : undefined;
        if (demoMode) {
            return demoRequest(kind, body, signal);
        }
        return postJSON(ROUTES[kind], body, signal);
    }

    function getJSON(url, signal) {
        return fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            signal: signal
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Request failed with status ' + response.status + '.');
            }
            return response.json();
        });
    }

    /* =====================================================================
       12. Composer behaviour
       ===================================================================== */

    function autosize() {
        els.input.style.height = 'auto';
        var next = Math.min(els.input.scrollHeight, 200);
        els.input.style.height = Math.max(next, 40) + 'px';
    }

    function isBusy() {
        return state.sending || state.finalizing;
    }

    function updateComposer() {
        var busy = isBusy();

        els.send.textContent = busy ? t('Stop') : t('Send');
        els.send.classList.toggle('stop', busy);
        els.send.classList.toggle('primary', !busy);
        els.send.setAttribute('aria-label', busy ? t('Stop the current request') : t('Send message'));

        els.finalize.disabled = busy;
        els.reset.disabled = busy;
    }

    /* =====================================================================
       13. Actions
       ===================================================================== */

    function handleSend() {
        if (isBusy()) {
            return;
        }

        var text = els.input.value.trim();

        if (!text) {
            els.input.focus();
            return;
        }

        if (text.length > MAX_LENGTH) {
            setError('Messages are limited to ' + MAX_LENGTH + ' characters.');
            return;
        }

        setError('');
        hideChips();
        addMessage('user', text);

        els.input.value = '';
        autosize();

        state.sending = true;
        state.controller = new AbortController();
        updateComposer();
        setStatus('Thinking', 'busy');

        var typing = addTyping('Thinking');

        request('send', { message: text, conversation_id: conversationId() })
            .then(function (data) {
                typing.remove();
                var replyText = data && typeof data.reply === 'string' ? data.reply : '';
                if (!replyText) {
                    replyText = t('I did not receive a reply. Please try again.');
                }
                addMessage('assistant', replyText, { markdown: true });
                trackChat(data, text, replyText);
                setStatus('Ready', 'idle');
            })
            .catch(function (error) {
                typing.remove();
                if (error && error.name === 'AbortError') {
                    addMessage('system', t('Request cancelled.'));
                    setStatus('Ready', 'idle');
                    return;
                }
                var message = (error && error.message) ? error.message : 'Something went wrong.';
                setError(message);
                addMessage('system', 'Error: ' + message);
                setStatus('Error', 'error');
            })
            .then(function () {
                state.sending = false;
                state.controller = null;
                updateComposer();
                updateDemoProgress();
                els.input.focus();
            });
    }

    function handleFinalize() {
        if (isBusy()) {
            return;
        }

        if (!demoMode) {
            /* In production the server decides whether the brief is ready. */
        } else if (demoAgent.progress().done === 0) {
            setError('Answer at least one question before finalizing.');
            return;
        }

        setError('');
        hideChips();
        state.finalizing = true;
        state.controller = new AbortController();
        updateComposer();
        setStatus('Finalizing', 'busy');

        var typing = addTyping('Writing the brief');

        request('finalize', { conversation_id: conversationId() })
            .then(function (data) {
                typing.remove();
                var documentText = data && typeof data.document === 'string' ? data.document : '';
                if (!documentText) {
                    throw new Error('The agent returned an empty document.');
                }
                var title = (data && typeof data.title === 'string' && data.title) ? data.title : t('UX Brief');
                renderDocument(documentText, title);
                syncCost(data);
                setStatus('Done', 'idle');
                toast('Brief generated', 'ok');
            })
            .catch(function (error) {
                typing.remove();
                if (error && error.name === 'AbortError') {
                    addMessage('system', t('Finalize cancelled.'));
                    setStatus('Ready', 'idle');
                    return;
                }
                var message = (error && error.message) ? error.message : 'Something went wrong.';
                setError(message);
                addMessage('system', 'Error: ' + message);
                setStatus('Error', 'error');
            })
            .then(function () {
                state.finalizing = false;
                state.controller = null;
                updateComposer();
                els.input.focus();
            });
    }

    function handleCancel() {
        if (state.controller) {
            state.controller.abort();
        }
        state.sending = false;
        state.finalizing = false;
        state.controller = null;
        updateComposer();
        setStatus('Ready', 'idle');
    }

    function handleReset() {
        if (isBusy()) {
            return;
        }

        var confirmed = window.confirm(t('Reset the conversation? This clears every answer collected so far.'));
        if (!confirmed) {
            return;
        }

        setError('');
        state.controller = new AbortController();
        updateComposer();

        request('reset', {})
            .then(function () {
                els.messages.innerHTML = '';
                setChatId(null);
                state.started = false;
                renderCost();
                renderHistory();
                updateDemoProgress();
                addMessage(
                    'assistant',
                    t('Describe the idea or problem. I will ask one question at a time.'),
                    { markdown: true, noTools: true }
                );
                setStatus('Ready', 'idle');
                toast('Conversation reset', 'ok');
                els.input.focus();
            })
            .catch(function (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                var message = (error && error.message) ? error.message : 'Could not reset the conversation.';
                setError(message);
            })
            .then(function () {
                state.controller = null;
                updateComposer();
                els.input.focus();
            });
    }

    /* =====================================================================
       14. Event wiring
       ===================================================================== */

    els.send.addEventListener('click', function () {
        if (isBusy()) {
            handleCancel();
        } else {
            handleSend();
        }
    });

    els.finalize.addEventListener('click', handleFinalize);
    els.reset.addEventListener('click', handleReset);

    els.input.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') {
            return;
        }

        if (event.isComposing || event.keyCode === 229) {
            return;
        }

        if (event.ctrlKey || event.metaKey) {
            event.preventDefault();
            handleFinalize();
            return;
        }

        if (!event.shiftKey && !event.altKey) {
            event.preventDefault();
            handleSend();
        }
    });

    els.input.addEventListener('input', autosize);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isBusy()) {
            handleCancel();
        }
    });

    window.addEventListener('beforeunload', function () {
        if (state.controller) {
            state.controller.abort();
        }
    });

    /* =====================================================================
       15. Boot
       ===================================================================== */

    function boot() {
        initTheme();
        updateComposer();
        updateDemoProgress();
        refreshHistory();

        if (demoMode) {
            els.demoBadge.hidden = false;
            els.demoBadge.title = 'No backend routes detected — running the built-in demo agent.';
        }

        addMessage(
            'assistant',
            t('Describe the idea or problem. I will ask one question at a time.'),
            { markdown: true, noTools: true }
        );

        autosize();
        updateJumpButton();

        if (window.matchMedia && window.matchMedia('(min-width: 721px)').matches) {
            els.input.focus();
        }
    }

    boot();
})();
</script>
</body>
</html>