<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>UX Agent</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    :root {
        --bg: #0f1115;
        --panel: #161a22;
        --panel-2: #1c212b;
        --line: #262c38;
        --text: #e6e8ee;
        --muted: #8a94a6;
        --accent: #5b8def;
        --accent-2: #3d6fd6;
        --danger: #e5544b;
        --doc: #0b0d12;
        --code: #2a3142;
        --radius: 10px;
        --font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
    }

    * { box-sizing: border-box; }

    html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        background: var(--bg);
        color: var(--text);
        font-family: var(--font);
        font-size: 15px;
        line-height: 1.55;
    }

    #app {
        display: flex;
        flex-direction: column;
        height: 100vh;
        max-width: 880px;
        margin: 0 auto;
        padding: 0 16px;
    }

    header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 4px;
        border-bottom: 1px solid var(--line);
    }

    header .title {
        font-weight: 600;
        letter-spacing: 0.2px;
    }

    header .subtitle {
        color: var(--muted);
        font-size: 12px;
        margin-left: 10px;
    }

    header .actions {
        display: flex;
        gap: 8px;
    }

    button {
        font-family: inherit;
        font-size: 13px;
        cursor: pointer;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: var(--panel-2);
        color: var(--text);
        padding: 7px 12px;
        transition: background 0.15s ease, border-color 0.15s ease, opacity 0.15s ease;
    }

    button:hover:not(:disabled) {
        background: #232a37;
        border-color: #33405a;
    }

    button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    button.primary {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    button.primary:hover:not(:disabled) {
        background: var(--accent-2);
        border-color: var(--accent-2);
    }

    #messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px 4px 12px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        scroll-behavior: smooth;
    }

    #messages::-webkit-scrollbar { width: 10px; }
    #messages::-webkit-scrollbar-track { background: transparent; }
    #messages::-webkit-scrollbar-thumb { background: #2a3142; border-radius: 8px; }

    .msg {
        display: flex;
        width: 100%;
    }

    .msg-user { justify-content: flex-end; }
    .msg-assistant { justify-content: flex-start; }
    .msg-system { justify-content: center; }
    .msg-doc { justify-content: stretch; }

    .bubble {
        max-width: 78%;
        padding: 10px 14px;
        border-radius: var(--radius);
        background: var(--panel);
        border: 1px solid var(--line);
        white-space: pre-wrap;
        word-wrap: break-word;
        overflow-wrap: anywhere;
        direction: rtl;
    }

    .msg-user .bubble {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    .msg-system .bubble {
        background: transparent;
        border-color: var(--danger);
        color: var(--danger);
        font-size: 13px;
        max-width: 90%;
    }

    .bubble.doc {
        max-width: 100%;
        width: 100%;
        background: var(--doc);
        border-color: var(--line);
        white-space: normal;
        font-family: var(--font);
        padding: 22px 24px;
    }

    .msg-doc {
        flex-direction: column;
        gap: 8px;
    }

    /* Typing indicator */
    .typing .bubble {
        display: inline-flex;
        gap: 5px;
        align-items: center;
        padding: 14px 16px;
    }

    .typing .bubble span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--muted);
        animation: blink 1.2s infinite ease-in-out both;
    }

    .typing .bubble span:nth-child(2) { animation-delay: 0.15s; }
    .typing .bubble span:nth-child(3) { animation-delay: 0.3s; }

    @keyframes blink {
        0%, 80%, 100% { opacity: 0.25; transform: translateY(0); }
        40% { opacity: 1; transform: translateY(-2px); }
    }

    /* Composer */
    footer {
        border-top: 1px solid var(--line);
        padding: 12px 4px 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .error {
        display: none;
        color: var(--danger);
        font-size: 13px;
        padding: 8px 12px;
        background: rgba(229, 84, 75, 0.08);
        border: 1px solid rgba(229, 84, 75, 0.35);
        border-radius: 8px;
    }

    .error.visible { display: block; }

    .composer {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    textarea#input {
        flex: 1;
        resize: none;
        min-height: 44px;
        max-height: 200px;
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: 10px;
        color: var(--text);
        padding: 11px 14px;
        font-family: inherit;
        font-size: 14px;
        line-height: 1.5;
        outline: none;
        transition: border-color 0.15s ease;
    }

    textarea#input:focus {
        border-color: var(--accent);
    }

    .finalize-row {
        display: flex;
        justify-content: flex-end;
    }

    /* Document styling */
    .doc h1 {
        font-size: 22px;
        margin: 0 0 16px 0;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--line);
    }

    .doc h2 {
        font-size: 15px;
        margin: 22px 0 8px 0;
        color: var(--accent);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        font-weight: 600;
    }

    .doc h3 {
        font-size: 14px;
        margin: 16px 0 6px 0;
    }

    .doc p { margin: 8px 0; }
    .doc ul { margin: 8px 0 8px 0; padding-left: 20px; }
    .doc li { margin: 4px 0; }

    .doc hr {
        border: none;
        border-top: 1px solid var(--line);
        margin: 18px 0;
    }

    .doc code {
        font-family: var(--mono);
        font-size: 13px;
        background: var(--code);
        padding: 2px 6px;
        border-radius: 4px;
    }

    .doc pre {
        background: var(--code);
        padding: 12px 14px;
        border-radius: 8px;
        overflow-x: auto;
    }

    .doc pre code {
        background: transparent;
        padding: 0;
        font-size: 13px;
        line-height: 1.5;
    }

    .doc .check {
        color: var(--accent);
        font-family: var(--mono);
        margin-right: 4px;
    }

    .doc-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .copy-btn {
        font-size: 12px;
        padding: 6px 12px;
    }

    @media (max-width: 600px) {
        .bubble { max-width: 90%; }
        header .subtitle { display: none; }
    }
</style>
</head>
<body>
<div id="app">
    <header>
        <div>
            <span class="title">UX Agent</span>
            <span class="subtitle">One question at a time, until the brief is complete.</span>
        </div>
        <div class="actions">
            <button type="button" id="reset" title="Clear the conversation">Reset</button>
        </div>
    </header>

    <div id="messages" aria-live="polite"></div>

    <footer>
        <div class="error" id="error"></div>

        <div class="composer">
            <textarea id="input" rows="1" placeholder="Describe the idea or problem. Press Enter to send, Shift+Enter for a new line." autocomplete="off"></textarea>
            <button type="button" id="send" class="primary">Send</button>
        </div>

        <div class="finalize-row">
            <button type="button" id="finalize" title="Ask the agent to produce the final document">Finalize output</button>
        </div>
    </footer>
</div>

<script>
    window.APP_ROUTES = {
        send: @json(route('chat.send')),
        finalize: @json(route('chat.finalize')),
        reset: @json(route('chat.reset')),
    };
</script>

<script>
(function () {
    'use strict';

    var state = {
        sending: false,
        finalizing: false,
    };

    var els = {
        messages: document.getElementById('messages'),
        input: document.getElementById('input'),
        send: document.getElementById('send'),
        finalize: document.getElementById('finalize'),
        reset: document.getElementById('reset'),
        error: document.getElementById('error'),
    };

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[c];
        });
    }

    function renderInline(text) {
        var out = escapeHtml(text);
        out = out.replace(/`([^`]+)`/g, '<code>$1</code>');
        out = out.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        out = out.replace(/(^|[^*])\*([^*]+)\*/g, '$1<em>$2</em>');
        return out;
    }

    /**
     * Minimal markdown renderer: headings, lists, task checkboxes, code
     * fences, horizontal rules, paragraphs, inline code / bold / italic.
     */
    function renderMarkdown(source) {
        var lines = String(source).split(/\r?\n/);
        var html = '';
        var inList = false;
        var inCode = false;
        var codeBuffer = [];

        function closeList() {
            if (inList) {
                html += '</ul>';
                inList = false;
            }
        }

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];

            if (/^\s*```/.test(line)) {
                if (inCode) {
                    html += '<pre><code>' + escapeHtml(codeBuffer.join('\n')) + '</code></pre>';
                    codeBuffer = [];
                    inCode = false;
                } else {
                    closeList();
                    inCode = true;
                }
                continue;
            }

            if (inCode) {
                codeBuffer.push(line);
                continue;
            }

            if (line.trim() === '') {
                closeList();
                continue;
            }

            var heading = line.match(/^(#{1,6})\s+(.*)$/);
            if (heading) {
                closeList();
                var level = heading[1].length;
                html += '<h' + level + '>' + renderInline(heading[2]) + '</h' + level + '>';
                continue;
            }

            if (/^\s*(-{3,}|\*{3,})\s*$/.test(line)) {
                closeList();
                html += '<hr>';
                continue;
            }

            var bullet = line.match(/^\s*[-*]\s+(.*)$/);
            if (bullet) {
                if (!inList) {
                    html += '<ul>';
                    inList = true;
                }
                var item = bullet[1];
                var checkbox = item.match(/^\[( |x|X)\]\s+(.*)$/);
                var prefix = '';
                if (checkbox) {
                    var checked = checkbox[1].toLowerCase() === 'x';
                    prefix = '<span class="check">' + (checked ? '&#10003;' : '&#9744;') + '</span>';
                    item = checkbox[2];
                }
                html += '<li>' + prefix + renderInline(item) + '</li>';
                continue;
            }

            closeList();
            html += '<p>' + renderInline(line) + '</p>';
        }

        closeList();

        if (inCode && codeBuffer.length) {
            html += '<pre><code>' + escapeHtml(codeBuffer.join('\n')) + '</code></pre>';
        }

        return html;
    }

    function scrollToBottom() {
        els.messages.scrollTop = els.messages.scrollHeight;
    }

    function addMessage(role, content, asHtml) {
        var wrap = document.createElement('div');
        wrap.className = 'msg msg-' + role;

        var bubble = document.createElement('div');
        bubble.className = 'bubble';

        if (asHtml) {
            bubble.innerHTML = content;
        } else {
            bubble.textContent = content;
        }

        wrap.appendChild(bubble);
        els.messages.appendChild(wrap);
        scrollToBottom();
        return wrap;
    }

    function addTyping() {
        var wrap = document.createElement('div');
        wrap.className = 'msg msg-assistant typing';

        var bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.innerHTML = '<span></span><span></span><span></span>';

        wrap.appendChild(bubble);
        els.messages.appendChild(wrap);
        scrollToBottom();
        return wrap;
    }

    function setError(message) {
        if (!message) {
            els.error.textContent = '';
            els.error.classList.remove('visible');
            return;
        }
        els.error.textContent = message;
        els.error.classList.add('visible');
    }

    function postJSON(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body || {}),
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().catch(function () { return null; }).then(function (data) {
                if (!response.ok || !data || data.ok === false) {
                    var msg = (data && data.error) ? data.error : ('Request failed with status ' + response.status);
                    throw new Error(msg);
                }
                return data;
            });
        });
    }

    function setBusy(busy) {
        els.send.disabled = busy;
        els.finalize.disabled = busy;
    }

    function autosize() {
        els.input.style.height = 'auto';
        els.input.style.height = Math.min(els.input.scrollHeight, 200) + 'px';
    }

    function handleSend() {
        var text = els.input.value.trim();
        if (!text || state.sending || state.finalizing) {
            return;
        }

        setError('');
        addMessage('user', text);
        els.input.value = '';
        autosize();

        state.sending = true;
        setBusy(true);
        var typing = addTyping();

        postJSON(window.APP_ROUTES.send, { message: text })
            .then(function (data) {
                typing.remove();
                addMessage('assistant', data.reply);
            })
            .catch(function (error) {
                typing.remove();
                setError(error.message);
                addMessage('system', 'Error: ' + error.message);
            })
            .then(function () {
                state.sending = false;
                setBusy(false);
                els.input.focus();
            });
    }

    function handleFinalize() {
        if (state.sending || state.finalizing) {
            return;
        }

        setError('');
        state.finalizing = true;
        setBusy(true);
        var typing = addTyping();

        postJSON(window.APP_ROUTES.finalize, {})
            .then(function (data) {
                typing.remove();

                var wrap = document.createElement('div');
                wrap.className = 'msg msg-doc';

                var bubble = document.createElement('div');
                bubble.className = 'bubble doc';
                bubble.innerHTML = renderMarkdown(data.document);

                var actions = document.createElement('div');
                actions.className = 'doc-actions';

                var copyBtn = document.createElement('button');
                copyBtn.type = 'button';
                copyBtn.className = 'copy-btn';
                copyBtn.textContent = 'Copy output';

                copyBtn.addEventListener('click', function () {
                    var finish = function (label) {
                        copyBtn.textContent = label;
                        setTimeout(function () {
                            copyBtn.textContent = 'Copy output';
                        }, 1500);
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(data.document)
                            .then(function () { finish('Copied'); })
                            .catch(function () { finish('Copy failed'); });
                    } else {
                        try {
                            var ta = document.createElement('textarea');
                            ta.value = data.document;
                            ta.style.position = 'fixed';
                            ta.style.opacity = '0';
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                            finish('Copied');
                        } catch (e) {
                            finish('Copy failed');
                        }
                    }
                });

                actions.appendChild(copyBtn);
                wrap.appendChild(bubble);
                wrap.appendChild(actions);
                els.messages.appendChild(wrap);
                scrollToBottom();
            })
            .catch(function (error) {
                typing.remove();
                setError(error.message);
                addMessage('system', 'Error: ' + error.message);
            })
            .then(function () {
                state.finalizing = false;
                setBusy(false);
                els.input.focus();
            });
    }

    function handleReset() {
        if (state.sending || state.finalizing) {
            return;
        }

        if (!window.confirm('Reset the conversation?')) {
            return;
        }

        postJSON(window.APP_ROUTES.reset, {})
            .then(function () {
                els.messages.innerHTML = '';
                setError('');
                addMessage('assistant', 'Describe the idea or problem. I will ask one question at a time.');
                els.input.focus();
            })
            .catch(function (error) {
                setError(error.message);
            });
    }

    els.send.addEventListener('click', handleSend);
    els.finalize.addEventListener('click', handleFinalize);
    els.reset.addEventListener('click', handleReset);

    els.input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            handleSend();
        }
    });

    els.input.addEventListener('input', autosize);

    addMessage('assistant', 'Describe the idea or problem. I will ask one question at a time.');
    els.input.focus();
    autosize();
})();
</script>
</body>
</html>