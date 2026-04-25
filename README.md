# LinguaPath

LinguaPath is a full-stack Laravel Inertia EdTech web app for English learning and TOEFL ITP-style practice. It is built as a productized learning platform prototype, not as an official ETS scoring tool and not as a public REST API service.

## Current Positioning

- **Product type:** English learning and TOEFL ITP preparation web app.
- **Architecture:** Laravel + Fortify + Inertia + React monolith.
- **Database:** SQLite for local development, with MySQL-compatible schema as a production target.
- **API status:** No public REST API and no Laravel Sanctum integration yet.
- **Score status:** TOEFL scores are internal estimates only, not official ETS scores.

TOEFL and ETS are trademarks of their respective owners. LinguaPath provides internal practice and estimated scoring, not official ETS scoring.

## Tech Stack

- Laravel 13
- Laravel Fortify authentication
- Inertia.js v3
- React 19
- Tailwind CSS v4
- Laravel Wayfinder
- Pest 4
- Vite

## Feature Matrix

| Area | Status | Notes |
|---|---|---|
| 60-day study path | Done | Lesson path, mini-tests, completion tracking. |
| TOEFL ITP simulation | Partial | 140 questions and section lock exist; timer is now server-synced with internal score disclaimer. |
| Listening audio | Partial | Audio upload foundation exists; seeded content still uses transcript fallback until real audio is uploaded. |
| Reading passages | Partial | TOEFL-style 300-700 word imported passages added for exam-eligible reading content. |
| Vocabulary SRS | Done | Daily deck, quiz options, due review, status tracking. |
| Mistake journal | Done | Wrong answers are logged with review status and explanations. |
| Speaking practice | Partial | Recording UI and heuristic feedback; no production-grade pronunciation analysis yet. |
| Writing practice | Partial | Prompt and heuristic scoring; no full grammar correction engine yet. |
| Admin/CMS | Partial | Admin role, dashboard, content health metrics, and audio upload are implemented. Full CRUD for all content remains planned. |
| REST API/Sanctum | Planned | Deferred until the core learning product is stable. |
| Billing/SaaS | Planned | Subscription table exists but billing is not implemented. |

## Demo Accounts

After running the database seeder:

- User: `test@example.com` / `password`
- Admin: `admin@example.com` / `password`

## Core Screenshots To Capture

- Welcome page
- Dashboard
- Study path
- Lesson page
- Practice room
- Exam room
- Exam result
- Vocabulary deck
- Mistake journal
- Analytics dashboard
- Admin dashboard
- Admin audio upload

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
```

For local development with Laravel Herd, use the Herd site URL for this project. Do not run a separate PHP server unless your local setup requires it.

## Quality Commands

```bash
php artisan test --compact
npm run types:check
npm run build
npm run lint:check
npm run format:check
```

PHP formatting:

```bash
vendor/bin/pint --dirty --format agent
```

MySQL compatibility check when a MySQL connection is configured:

```bash
php artisan migrate:fresh --seed --database=mysql
php artisan test --compact --database=mysql
```

## Architecture Overview

- Laravel controllers return Inertia pages.
- Service classes hold learning logic such as exam simulation, practice, analytics, vocabulary review, speaking, and writing.
- React pages implement the interactive learning rooms and dashboards.
- Wayfinder generates typed route helpers for existing Laravel routes.
- Admin content operations are protected by an admin role and middleware.

## Known Limitations

- TOEFL score conversion is an internal estimate, not an ETS-validated scaled score.
- Seeded listening content does not include real recorded audio by default.
- Speaking feedback is heuristic and does not perform real pronunciation scoring.
- Writing feedback is heuristic and does not perform full grammar correction.
- Admin/CMS currently covers dashboard metrics and audio upload; full content CRUD is still planned.
- Public REST API and Sanctum token authentication are not implemented.
- MySQL compatibility should be verified before production deployment.

## Roadmap

1. Complete admin CRUD for lessons, questions, options, passages, vocabulary, skill tags, and prompts.
2. Attach real audio assets to listening questions and enforce audio quality flags.
3. Expand TOEFL-style reading passages and review item quality with an English/TOEFL subject matter expert.
4. Improve speaking and writing feedback with stronger linguistic analysis.
5. Add E2E browser tests for exam, admin, vocabulary, and mistake review flows.
6. Add REST API/Sanctum only after the web product reaches stable core quality.
# linguapath
