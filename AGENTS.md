# Repository Guidelines

## Project Structure & Module Organization

The local application foundation is implemented. Consult `docs/PLANO_IMPLEMENTACAO.md` for MVP scope, phase status, and completion criteria, and `docs/architecture.md` for technical decisions. `README.md` documents setup and daily commands.

Follow this monorepo layout:
- `frontend/src/`: React and TypeScript application; `frontend/public/`: static assets.
- `backend/app/`: Laravel controllers, requests, resources, policies, actions, and AI tools.
- `backend/database/`: migrations, factories, and fictional demonstration seeders.
- `backend/routes/` and `backend/tests/`: API routes and automated tests.
- `docs/architecture.md` and `docs/screenshots/`: architecture notes and UI captures.

Keep controllers small, validation in Form Requests, authorization in Policies, and business rules in Actions or Services.

## Build, Test, and Development Commands

- `bash scripts/setup.sh`: prepare dependencies, environment files, migrations, and local services.
- `docker compose up -d --wait`: start the prepared environment.
- `docker compose run --rm --no-deps frontend npm run build`: check TypeScript and build the frontend.
- `docker compose run --rm --no-deps backend php artisan test`: run backend tests.
- `docker compose down`: stop services while preserving database data.

Run PHP and Composer in containers; the host PHP version differs from the application's runtime.

## Coding Style & Naming Conventions

Use two-space indentation for TypeScript and four spaces for PHP. Use PascalCase for React components and PHP classes, camelCase for functions and variables, and snake_case for database columns and AI tool identifiers such as `get_latest_order`. Run Laravel Pint for PHP and ESLint plus Prettier for frontend changes.

## Testing Guidelines

Pest runs backend tests in `backend/tests/Feature/`; use `bash scripts/test-backend.sh` because it creates a disposable schema in the isolated `database-test` PostgreSQL service. Vitest/React Testing Library covers `frontend/src/**/*.test.*`; Playwright smoke tests are in `frontend/e2e/`. Name backend tests `*Test.php`. Prioritize user isolation, authentication, status transitions, cancellation, and AI tool validation; simulate external failures. No numeric coverage threshold is defined.

## Commit & Pull Request Guidelines

Create one closing commit per phase, including the updated implementation plan. Use its proposed commit subject, for example `docs: define environment and architecture decisions`. PRs should describe behavior, reference the relevant phase or issue, report validation, and include screenshots for UI changes.

## Security & Configuration

Never commit real `.env` files; provide `.env.example` templates. Keep Gemini and database credentials backend-only. Scope customer queries to the authenticated user and enforce Policies for AI tools. The AI must never access the database directly.
