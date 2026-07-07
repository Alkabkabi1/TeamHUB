# Contributing to TeamHUB

Thank you for your interest in contributing to TeamHUB. This project is an Arabic-first teamwork platform built with Laravel, Inertia.js, and Svelte.

## Before you start

1. Read [README.md](./README.md) and [docs/ENGINEERING_PRINCIPLES.md](./docs/ENGINEERING_PRINCIPLES.md).
2. Search [existing issues](https://github.com/Alkabkabi1/TeamHUB/issues) before opening a duplicate.
3. For large changes, open an issue first to discuss scope.

## Development setup

```bash
git clone https://github.com/Alkabkabi1/TeamHUB.git
cd TeamHUB
composer setup
composer dev
```

On Windows, run `php artisan serve` and `npm run dev` in separate terminals if `composer dev` is inconvenient.

## Quality gates

Before opening a pull request, run:

```bash
composer ci:check
composer analyse
npm run build
```

## Pull request guidelines

- Keep changes focused; avoid unrelated refactors.
- Follow existing naming: **Workspace → Project → Task**.
- Do not rename historical database migrations.
- Add or update Pest tests for behavior changes.
- Update user-facing documentation when setup or workflows change.

## Code style

- **PHP:** Laravel Pint (`composer lint` / `composer lint:check`)
- **Frontend:** ESLint + Prettier (`npm run lint` / `npm run format:check`)
- **Types:** Svelte check (`npm run types:check`)
- **Static analysis:** PHPStan level 5 with baseline (`composer analyse`)

## Reporting bugs

Use the [bug report template](.github/ISSUE_TEMPLATE/bug_report.md) and include steps to reproduce, expected behavior, and environment details (PHP, Node, OS).

## Security

Do not open public issues for security vulnerabilities. See [SECURITY.md](./SECURITY.md).

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](./LICENSE).
