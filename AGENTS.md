# Gymnamite Project Index

This repository uses direct, demand-triggered skills. Do not use role-based
agents, orchestration chains, or subagent handoffs. Work in the current
conversation and keep responsibility for the request from inspection through
verification.

## Communication

Communicate with the user in Portuguese. Keep this file, skills, source code,
identifiers, commit messages, and technical artifacts in English unless an
existing project convention requires Portuguese UI text.

## Stack

- Laravel 13, PHP 8.3, MySQL
- Inertia.js v3, Vue 3, Vuetify 3
- PHPUnit and Laravel Pint

The frontend is Vue and Vuetify. Do not introduce React or Tailwind unless the
user explicitly requests and approves that change.

## Mandatory Baseline

1. Load `.agents/guardrails/base.md` for every task.
2. Inspect existing code before changing application files.
3. Reuse established project patterns and make the smallest correct change.
4. Never undo unrelated worktree changes.
5. Do not add dependencies, base directories, or documentation files unless explicitly requested.

## Skill Selection

Load only the skills relevant to the request. Combine skills when a task spans
multiple domains.

| Situation | Required skill or skills |
| --- | --- |
| User explicitly asks to plan, design, organize, estimate, or discuss implementation before coding | `project-planning` |
| User asks to implement a plan or any code/configuration change | `direct-implementation` |
| Laravel/PHP changes, code review, migrations, Eloquent, validation, jobs, security, tests, or performance | `laravel-best-practices` |
| Any Gymnamite application module, access control, report, service, command, route, Inertia page, or project test | `gymnamite-project-patterns` |
| SaaS modules, tenants, organizations, subscriptions, billing, queues, integrations, or production hardening | `saas-php-laravel` |
| Vue, Inertia, Vuetify, layouts, forms, tables, dialogs, navigation, responsive UI, or icons | `vuetify-development` |
| Asaas, `app/PaymentGateways`, gateway billing, payment sync, gateway accounts, cards, transfers, customers, or postbacks | `asaas-payment-gateway` |
| User types exactly `commitar` | `git-commit` |

When the user asks to proceed with an existing plan, also load `plan-execution`.
It implements the plan directly and does not delegate work.

## Implementation And Verification

- Use Laravel generators with `--no-interaction` when applicable.
- Follow existing module controller and `Route::module()` patterns where they apply.
- Keep UI in `resources/js/pages` and use existing Vuetify layouts and components.
- Use PHPUnit classes and existing factories. Add or update focused tests for changed behavior.
- After PHP changes, run `vendor/bin/pint --dirty --format agent`.
- Run the narrowest meaningful test command, normally `php artisan test --compact` with a focused filter.
- Report completed work, verification results, unresolved blockers, and required manual actions directly to the user in Portuguese.

## Source Of Truth

The canonical project guidance is under `.agents/`. When this index is less
specific than a relevant guardrail or skill, the more specific file takes
precedence.
