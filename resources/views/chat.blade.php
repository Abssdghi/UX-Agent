<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}" data-lang="{{ app()->getLocale() === 'fa' ? 'fa-IR' : 'en-US' }}" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>UX Agent</title>
<meta name="description" content="{{ __('A guided UX discovery agent: one question at a time, until the brief is complete.') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('assets/css/chat.css') }}">
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
                <div class="brand-sub">{{ __('One question at a time, until the brief is complete.') }}</div>
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
            <button type="button" id="mic" class="ghost icon" hidden title="{{ __('Dictate') }}" aria-label="{{ __('Dictate') }}" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true">
                    <path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3z"></path>
                    <path d="M5 11a7 7 0 0 0 14 0"></path>
                    <path d="M12 18v3"></path>
                </svg>
            </button>
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

<script src="{{ asset('assets/js/chat.js') }}"></script>
</body>
</html>