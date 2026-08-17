---
iteration: 1
max_iterations: 20
plan_path: ".claude/PRPs/plans/upci-nz-site-overhaul.plan.md"
input_type: "plan"
started_at: "2026-08-17T14:20:00+12:00"
---

# PRP Ralph Loop State

## ⚠️ Completion criterion — read before emitting the promise

**The validation gate already passes with ~60 of 72 tasks unimplemented.** It is
green because it tests what exists, not what is missing. Emitting
`<promise>COMPLETE</promise>` on a green gate would be a lie.

Completion here means **the plan's tasks are done**, verified against the T0–T71
list, with the gate green as a floor rather than the definition.

## Codebase Patterns

- **Gate**: `vendor/bin/pint --test --dirty && npm run build && ./vendor/bin/pest <5 project-owned test files>`. Bare `pint --test` exits 1 on 83 pre-existing files and short-circuits the `&&` chain — `--dirty` is load-bearing.
- **Filament v4**: `form(Schema $schema): Schema` + `->components([])`. Layout in `Filament\Schemas\Components\*`, fields in `Filament\Forms\Components\*`, actions in `Filament\Actions\*`. Tables use `->recordActions()` / `->toolbarActions()`.
- **A missing policy means ALLOW**, not deny. Every new model needs one, and `SecurityRegressionTest` asserts this.
- **A custom Filament `Page` never consults a policy at all** — `canAccess()` hard-returns true. It must gate itself, and the test must assert 403 on the URL.
- `disabled()` without `dehydrated()` is not a fix — Filament re-hydrates disabled fields on save.
- **SQLite migrations are not transactional.** Verify `migrate:rollback` explicitly; keep `up()` idempotent.
- `morphs()` fails on a populated SQLite table — use `nullableMorphs()`. `dropConstrainedForeignId()` fails if `up()` declared a separate index — `dropIndex()` first.
- **File copies belong in an artisan command, not a migration** — migrations run under `RefreshDatabase` on every test.
- New columns need adding to `$fillable` or `update()` silently no-ops.
- Tests build models directly (`Model::create`, `Region::firstOrCreate`). Only `UserFactory` exists; no factories needed.
- `git commit -F <file>` for any message containing quotes, and verify `HEAD` afterwards — a failed `-m` leaves work staged while the follow-up merge/push reports success.
- `public/build` is gitignored; deploys must run `npm run build`.

## Current Task

Work the T0–T71 execution order. Requirement 1 complete; region foundation (T12) in.
Next: T13 → T14 → T15, which unblock requirement 11.

## Plan Reference

`.claude/PRPs/plans/upci-nz-site-overhaul.plan.md`

## Progress Log

### Iteration 1 — starting

Entering with 23 commits already landed this session: six security fixes, the
P0 storage bug, contact inbox, logo pack, requirement 1a+1b, the T12 region
rename, and the locator now listing coordinate-less churches.

Gate: 42 passing. Requirement 1 done. Requirement 5 partially done.

---
