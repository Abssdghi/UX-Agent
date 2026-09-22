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
        mic: document.getElementById('mic'),
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
        chatId: null,
        dictating: false
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
        els.mic.disabled = busy;

        if (busy) {
            stopDictation();
        }
    }

    /* ------------------------------------------------------------ dictation */

    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    var recognition = null;
    var committed = '';

    function speechLocale() {
        return document.documentElement.getAttribute('data-lang') || 'en-US';
    }

    function setDictating(on) {
        els.mic.classList.toggle('recording', on);
        els.mic.setAttribute('aria-pressed', on ? 'true' : 'false');
        els.mic.title = on ? t('Stop dictation') : t('Dictate');
        els.mic.setAttribute('aria-label', els.mic.title);
    }

    function writeDictation(text) {
        var value = text.slice(0, MAX_LENGTH);

        if (els.input.value === value) {
            return;
        }

        els.input.value = value;
        autosize();
        els.input.scrollTop = els.input.scrollHeight;
    }

    function startDictation() {
        if (!recognition || state.dictating || isBusy()) {
            return;
        }

        if (els.input.value.length >= MAX_LENGTH) {
            setError('Messages are limited to ' + MAX_LENGTH + ' characters.');
            return;
        }

        committed = els.input.value;
        state.dictating = true;
        setDictating(true);

        try {
            recognition.start();
        } catch (err) {
            stopDictation();
        }
    }

    function stopDictation() {
        if (!state.dictating) {
            return;
        }

        state.dictating = false;
        committed = '';
        setDictating(false);

        try {
            recognition.stop();
        } catch (err) {
            /* already stopped */
        }
    }

    function onDictationResult(event) {
        var interim = '';

        for (var i = event.resultIndex; i < event.results.length; i++) {
            var chunk = event.results[i][0].transcript;

            if (event.results[i].isFinal) {
                committed += chunk;
            } else {
                interim += chunk;
            }
        }

        writeDictation(committed + interim);
    }

    function initDictation() {
        /* Firefox has no SpeechRecognition: the button stays hidden. */
        if (!SpeechRecognition || !els.mic) {
            return;
        }

        recognition = new SpeechRecognition();
        recognition.lang = speechLocale();
        recognition.continuous = true;
        recognition.interimResults = true;

        recognition.onresult = onDictationResult;

        recognition.onerror = function (event) {
            var reason = event && event.error ? event.error : '';

            /* Silence is not a failure: onend restarts while the user is listening. */
            if (reason === 'no-speech') {
                return;
            }

            state.dictating = false;
            setDictating(false);

            if (reason === 'aborted') {
                return;
            }

            if (reason === 'not-allowed' || reason === 'service-not-allowed') {
                setError('Microphone access was blocked.');
                return;
            }

            setError('Dictation failed. Please try again.');
        };

        recognition.onend = function () {
            /* Chrome ends the session after a pause: restart while listening. */
            if (state.dictating) {
                try {
                    recognition.start();
                } catch (err) {
                    stopDictation();
                }
                return;
            }

            setDictating(false);
        };

        els.mic.addEventListener('click', function () {
            if (state.dictating) {
                stopDictation();
            } else {
                startDictation();
            }
        });

        els.mic.hidden = false;
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

    els.input.addEventListener('input', function () {
        /* Typing during dictation takes over and keeps what the user wrote. */
        if (state.dictating) {
            stopDictation();
        }

        autosize();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (state.dictating) {
            stopDictation();
            return;
        }

        if (isBusy()) {
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
        initDictation();
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
