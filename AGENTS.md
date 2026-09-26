# Repository Guidelines

## Project Structure & Module Organization

This repository has completed its environment and architecture planning. Consult `docs/PLANO_IMPLEMENTACAO.md` for MVP scope, implementation phases, and completion criteria, and `docs/architecture.md` for technical decisions. Application directories and tooling have not yet been created.

Follow the planned monorepo layout when implementing:
- `frontend/src/`: React and TypeScript application; `frontend/public/`: static assets.
- `backend/app/`: Laravel controllers, requests, resources, policies, actions, and AI tools.
- `backend/database/`: migrations, factories, and fictional demonstration seeders.
- `backend/routes/` and `backend/tests/`: API routes and automated tests.
- `docs/architecture.md` and `docs/screenshots/`: architecture notes and UI captures.

Keep controllers small, validation in Form Requests, authorization in Policies, and business rules in Actions or Services.

## Build, Test, and Development Commands

No build, development, or test commands are currently configured. After scaffolding, establish and document these expected commands in `README.md`:
- `cd frontend && npm run dev`: start Vite locally.
- `cd frontend && npm run build`: build the frontend for production.
- `cd backend && php artisan test`: run backend tests.
- `docker compose up --build`: build and start local services once Compose is configured.

Verify scripts and configuration exist before using these commands.

## Coding Style & Naming Conventions

Until formatters are configured, use two-space indentation for TypeScript and four spaces for PHP. Use PascalCase for React components and PHP classes, camelCase for functions and variables, and snake_case for database columns and AI tool identifiers such as `get_latest_order`. Configure linting and formatting during foundation work and document their commands.

## Testing Guidelines

The plan calls for Pest or PHPUnit; neither is configured, and no coverage threshold or frontend framework is defined. Name backend test files `*Test.php` under `backend/tests/`. Prioritize integration tests for user isolation, authentication, status transitions, cancellation, and AI tool validation. Test frontend login, filters, chat, loading, and error states. Simulate Gemini failures without live API calls.

## Commit & Pull Request Guidelines

Create one closing commit per phase, including the updated implementation plan. Use its proposed commit subject, for example `docs: define environment and architecture decisions`. PRs should describe behavior, reference the relevant phase or issue, report validation, and include screenshots for UI changes.

## Security & Configuration

Never commit real `.env` files; provide `.env.example` templates. Keep Gemini and database credentials backend-only. Scope customer queries to the authenticated user and enforce Policies for AI tools. The AI must never access the database directly.
