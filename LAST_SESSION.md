# Last Session

Updated: 2026-08-11

## Agent Configuration

- `planner`, `commander`, and `executor` are disabled intentionally.
- `AGENTS.md` now requires a direct implementation workflow.
- Their skill files were renamed from `SKILL.md` to `DISABLED.md` and their
  role guardrails to `*.disabled.md`.
- Start a new OpenCode session or reload the workspace for this discovery
  change to take effect. Do not re-enable the pipeline unless requested.

## Docker Environment

- Docker files added: `Dockerfile`, `docker-compose.yml`, `.dockerignore`,
  `docker/mysql/init-test-database.sql`, and `docker/php/test-memory-limit.ini`.
- `app` is available at `http://localhost:8000` and `db` is healthy.
- Start the application with `docker compose up --build`.
- Run isolated tests with `docker compose --profile test run --rm test`.
- The test service uses `memory_limit=512M` only; the application service
  keeps the image default.
- `package-lock.json` was regenerated from `package.json`. Docker now uses
  `npm ci --legacy-peer-deps` successfully.

## Implemented Changes

- Plans: `modality_quantity` is now validated and persisted through the
  request, DTOs, and create/update Actions. `PlanPersistenceTest` was updated.
- Frontend: server validation errors are rendered for receivable fiscal
  requests and municipal gateway account configuration.
- Repositories: `BaseEloquentRepository::newQuery()` was made public to match
  `RepositoryInterface`, removing the prior PHP fatal during test boot.

## Verification Blocker

- Host PHPUnit is unusable because the OpenCode runner's stdout does not
  support the PHPUnit EventLogger exclusive lock. Use Docker PHPUnit instead.
- Docker PHPUnit now boots past the repository visibility fatal but
  `tests/Feature/PlanPersistenceTest.php` exhausts 512M before assertions:

  `Allowed memory size of 536870912 bytes exhausted`

- Investigate the plan creation dependency graph/container resolution for a
  recursion or runaway allocation before raising the limit further. No
  frontend, authorization, or coverage work was started after this blocker.

## Pending Work

1. Diagnose and fix the memory exhaustion in `PlanPersistenceTest`, then rerun it in Docker.
2. Audit the completed plan/receivable/gateway frontend corrections and fix the
   TypeScript errors in `resources/js/components/TablePage.vue` near lines 588/594.
3. Retain controller-level authorization as the documented Action standard;
   enforce and test existing permissions for dashboard and select-box.
4. Update `REFACTORING.md` with verified facts: Roles action migration, current
   counts, unused invoice-generation Actions, authorization standard, and test status.
5. Add focused tests for Modality, Product, GatewayAccount, and
   `gateway:sync-invoices`; then run Docker PHPUnit and PHPStan.

## Worktree Safety

- The worktree is intentionally dirty. Do not reset or revert unrelated work.
- `REFACTORING.md` and `yarn.lock` were already modified when this work began;
  review their diffs before changing or committing them.
- Run `git status --short` before continuing.
