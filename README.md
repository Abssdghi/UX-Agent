# UX Agent

**An AI requirements interviewer that turns a rough product idea into an implementation-ready UX brief — one question at a time.**

Laravel 13 with a single self-contained Blade front end. English/Persian UI, RTL support, dark/light themes, persistent chat history.

## What it does

Describe your idea once. The agent interviews you — one question at a time, 8 questions maximum — asking only what a developer would otherwise have to guess.

- It refuses technical questions (databases, APIs, algorithms, deployment) and extracts **what** and **why**, never **how**.
- Any other feature you mention is a black box: it may ask **at most one** question about it, and only about what your feature *consumes* from it.
- If an answer contradicts your own brief, it raises it once, then moves on.

Press **Finalize output** and it writes a structured Markdown spec: summary, problem, target user, in/out of scope, workflow, success criteria, constraints, examples, conceptual data model, open questions. Anything it couldn't get an answer for becomes an explicit `ASSUMPTION:` line rather than a silent invention.

## Features

- Guided interview with hard limits on question count, topic repetition, and scope
- Multi-conversation history with per-chat cost tracking
- English + Persian UI, automatic RTL, bundled Vazirmatn font
- Dark/light theme, markdown rendering, copy or download the finished brief
- Cancellable requests (`Stop` button or `Esc`)
- Falls back to session storage if the database is unavailable

## Requirements

PHP 8.3+, Composer 2, Node 20+ (for asset builds), and an API key for any **OpenAI-compatible** chat-completions endpoint.

## Installation

```bash
git clone https://github.com/abssdghi/ux-agent.git
cd ux-agent

composer setup          # install, .env, key, migrate, npm install, build
composer dev            # serve + queue + vite
```

Then open <http://localhost:8000>. Run the steps manually if you prefer: `composer install`, `cp .env.example .env`, `php artisan key:generate`, `php artisan migrate`, `npm install`, `npm run build`.

## Configuration

All settings live in `config/agent.php`, driven by `.env`:

```dotenv
AGENT_API_KEY=sk-your-real-key-here
AGENT_BASE_URL=https://api.openai.com/v1   # any OpenAI-compatible endpoint
AGENT_MODEL=gpt-4o-mini
AGENT_TEMPERATURE=0.3
AGENT_MAX_TOKENS=2000
AGENT_TIMEOUT=60
AGENT_LANGUAGE=persian                     # language the agent speaks and writes
```

`AGENT_LANGUAGE` accepts `persian`/`fa`/`ir`/`farsi`, `english`/`en`, `arabic`/`ar`, `turkish`/`tr`. It controls the **agent's** output only — the interface language is separate, toggled from the header.

<!-- > `.env.example` ships pointing at `https://api.avalai.ir/v1`, which is why the cost counter reports **Toman**. The `estimated_cost.irt` field it reads is provider-specific; on plain OpenAI the counter simply stays at `0`. -->

<!--
## License

MIT
-->
