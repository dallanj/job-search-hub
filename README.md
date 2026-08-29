# Job Search Hub

An open-source job search command center for tracking opportunities, managing follow-ups, and understanding what is working throughout the hiring process.

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-42B883?logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Job Search Hub brings applications, contacts, interviews, notes, and tasks into one private workspace. Its analytics dashboard turns status history into practical metrics such as interview conversion, response time, weekly activity, and source performance.

## Highlights

- **Application pipeline** — move opportunities through a drag-and-drop Kanban board from saved to hired, rejected, or archived.
- **Search analytics** — review funnel conversion, application activity, response-time distribution, source performance, and items needing attention.
- **Application records** — capture role details, compensation, workplace type, source, job posting, description, and private notes.
- **Relationship tracking** — organize company contacts and connect interviewers to scheduled interviews.
- **Follow-up planning** — create prioritized tasks, track overdue work, and see interviews and deadlines in one upcoming-actions view.
- **Fast filtering** — search and filter applications, pipeline stages, contacts, and tasks without losing context.
- **Secure accounts** — email verification, password confirmation, two-factor authentication, passkeys, authorization policies, and user-scoped route binding.
- **CSV migration** — preview and import existing data from an `ai-job-search` tracker with an idempotent Artisan command.
- **Responsive interface** — a Vue and Tailwind CSS interface with light and dark appearance modes.

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13, Laravel Fortify |
| Frontend | Vue 3, TypeScript, Inertia.js 3 |
| UI | Tailwind CSS 4, Reka UI, Lucide icons |
| Routing | Laravel Wayfinder |
| Database | SQLite by default; Laravel-supported databases can be configured |
| Quality | Pest 5, PHPStan/Larastan, Laravel Pint, ESLint, Prettier |
| Build tooling | Vite 8 |

## Getting started

### Prerequisites

- PHP 8.3 or newer with the extensions required by Laravel
- [Composer](https://getcomposer.org/)
- Node.js and npm
- SQLite, unless you configure another database

### Installation

```bash
git clone https://github.com/dallanj/job-search-hub.git
cd job-search-hub
touch database/database.sqlite
composer run setup
composer run dev
```

Then open the URL shown in your terminal and register an account.

`composer run setup` installs PHP and JavaScript dependencies, creates the local environment file, generates an application key, runs the migrations, and builds the frontend assets. `composer run dev` starts the Laravel application and Vite development server together.

### Load demo data

To explore the dashboard with a representative job search already in progress:

```bash
php artisan db:seed
```

Sign in with:

```text
Email: demo@jobsearchhub.test
Password: password
```

The demo seeder replaces only the existing demo account when it is run again.

## Importing an existing tracker

Job Search Hub can import a `job_search_tracker.csv` file exported by the [`ai-job-search`](https://github.com/dallanj/ai-job-search) project. Preview the operation before writing any data:

```bash
php artisan import:ai-job-search-tracker /path/to/job_search_tracker.csv --user=1 --dry-run
```

Remove `--dry-run` when the preview looks correct:

```bash
php artisan import:ai-job-search-tracker /path/to/job_search_tracker.csv --user=1
```

The `--user` option can be omitted when the database contains exactly one user.

## Quality checks

Run the backend quality checks and test suite:

```bash
composer test
```

This checks PHP formatting, performs static analysis, and runs the Pest test suite. Frontend checks are available separately:

```bash
npm run lint:check
npm run format:check
npm run types:check
```

## Project structure

```text
app/                 Laravel actions, controllers, models, policies, and filters
database/            Migrations, factories, and demo data
resources/js/        Inertia pages, Vue components, types, and generated routes
routes/               Web and account-settings routes
tests/                Pest feature and unit tests
```

## Contributing

Contributions are welcome. Fork the repository, create a focused branch, add or update tests for your change, and open a pull request with a clear description of the problem and solution.

Please run `composer test` and the frontend quality checks before submitting a pull request.

## License

Job Search Hub is open-source software licensed under the [MIT License](LICENSE).
