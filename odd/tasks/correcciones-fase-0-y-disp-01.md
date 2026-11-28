# Fixes after verifying phases 0A–0D and DISP-01

Branch: `fix/programador-sin-redis` (from `origin/develop` 3526ba3). TDD: strict (global user rule), runner
`php artisan test` in `backend/` against a disposable Postgres/Redis that mirrors `ci.yml`.

## Objective

Fix what was verified as broken while auditing the phase status (0A–0D, DISP-01), and record, with evidence,
what cannot be fixed without a decision from José.

## Evidence base (verified 2026-11-27)

- Active plan: `plan-correccion-bd-modulos-2026-11-13.md` (v3.5, phases 0–8), approved 2026-11-13. Its three
  files are not on `develop` and not on disk, but they ARE in git: commit `88c48e2` (José, 2026-11-17 23:57Z)
  on `origin/feat/0b-t6-panel-api`, made 20 min after PR #621 merged (23:37Z), so it never reached `develop`.
  Same commit: `sede-electronica-doc/03-servicios-tramites/decisiones-reconstruccion-tramites.md`. Against the
  copies recovered from session `0728262f`: plan and panel status identical except a trailing blank line; the
  phase 0 sheet in git is newer (updated 2026-11-17, 82 lines vs 68). Corrected 2026-11-28: this line said
  earlier that they no longer existed in git, which was wrong. Next phase per v3.5: Phase 1 (entity and
  departments); not started (`entidads` table still exists).
- `pm.max_children = 5` is the `www.conf` default of `php:8.6-fpm-alpine`; the repository configures no FPM
  pool. Archived plan §15.3 rule with the current `mem_limit: 1.5g` and `memory_limit = 128M` gives 9.
- Phase 0D is not in plan v3.5; it starts 2026-11-18 (PRs #637–#644). Only ADR 0007 records the approval of
  the 0D sheet; the sheet document itself exists nowhere.
- `verificacion-fase-0c-2026-11-26.md:51` cites "auditoría H3" for `pm.max_children`; no document in the
  repository or in the local transcripts defines that H3 (H3 of the phase 0 sheet is about backups).

## Tasks

- [x] T1 The schedule runs with Redis down (inline: 1 source file + test). Commit `f6b6bb8`.
  - Before: `schedule:run` exit 1 on `Redis::connect()` in the `sede-app` image; no task ran.
  - After: exit 0, the six locks are taken and a concurrent second run is skipped. `AgendaSinRedisTest`
    RED→GREEN; full suite 1044 tests, 1040 passed, 4 skipped, 0 failed; Pint and PHPStan clean.
  - RDD: risk medium, consent granted; reviewed with T2 (lineage `review-100e243f5700cdd7`), approved and
    acknowledged after `claude auth login`.
- [x] T2 `monitoreo:redis` lock expires in 10 min instead of 1440 (inline: 1 source file + test).
  - RED: `Failed asserting that 1440 is identical to 10`; GREEN after `withoutOverlapping(10)`. Measured with
    the file store: lock held at +9 min, free at +11 min. Pint and PHPStan clean; full suite 1044 tests, 1040 passed,
    4 skipped, 0 failed.
  - Why: with `pcntl` loaded the lock is released on SIGTERM, but a SIGKILL (OOM in the 256 MB scheduler
    container, `docker kill`) leaves it in place for 24 h and silences Redis monitoring for a day.
- [x] T3 The five daily tasks' locks expire in 23 h instead of 1440 min (inline: 1 source file + test).
  - RED: `consentimientos:depurar` lock still held at 2026-11-28 02:15:00 after a kill at 02:15:05 (expires at 1440
    min).
    GREEN with `withoutOverlapping(23 * 60)`. `AgendaCandadosTest` checks every locked task against its own next run.
  - Also from the RDD review of T1/T2 (approved, 2 advisory findings): `AgendaSinRedisTest` now releases the locks in
    setUp/tearDown (no flaky leftovers) and pins that pause/interrupt are off (ADR 0009 states it).
  - Full suite on disposable Postgres/Redis: 1047 tests, 1043 passed, 4 errors = `LectorRedisRealTest` (`Class "Redis"
    not found`: the host PHP has no phpredis, `php -m` confirms; CI installs it). Pint and PHPStan clean.
- [ ] D1 Bring the plan v3.5 documents from `88c48e2` to `develop`, or leave them only on that branch —
  decision of José. They are already public there. No decision to keep them unversioned exists: on
  2026-11-17 22:05Z an agent reported them as untracked and left them alone because they looked like his
  deliverables, and they were committed 2 h later (see the evidence base).
- [ ] D2 `pm.max_children` value for production — decision of José with load test (sheet 0C).
- [ ] D3 Fix or source the "auditoría H3" reference — needs the audit José refers to. It was added by
  `a06ff56` (#741, 2026-11-26) with no source. Searched with no match: every local and remote branch, the
  PR #741 and #744 bodies, and all local transcripts. The only H3 in git (phase 0 sheet) is about backups.
- [ ] D4 SMTP provider: without it no alert reaches a person (`MAIL_MAILER=log` in staging and production).
- [ ] D5 Promote to `main`: `sonda.yml` does not exist on the default branch, so the probe never runs
  (GitHub API 404). `develop` and `staging` are in sync since PR #753 (`d205d08`, deployed to staging OK);
  `main` lacks 44 commits of `develop`, and has none that `develop` lacks (250 merge commits, no own patch).
- [ ] D6 nginx `[crit]` alert and cache threshold (ADR 0010): not implemented; depend on D4 to reach anyone.
- [ ] D7 The 0D sheet document does not exist; only ADR 0007 records its approval.
- [ ] D8 The 0C sheet was never versioned either: `ci.yml:206,211` and `sitio/server/utils/almacen-respaldo.ts:141`
  cite «ficha 0C» findings H35 and B4, but no commit on any branch contains a 0C sheet. This is also why
  the origin of D3's H3 cannot be checked.

## Next step

T1–T3 are on `develop` and `staging` (PR #754). José decides D1–D8.
