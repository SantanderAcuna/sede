# Fixes after verifying phases 0A–0D and DISP-01

Branch: `fix/programador-sin-redis` (from `origin/develop` 3526ba3). TDD: strict (global user rule), runner
`php artisan test` in `backend/` against a disposable Postgres/Redis that mirrors `ci.yml`.

## Objective

Fix what was verified as broken while auditing the phase status (0A–0D, DISP-01), and record, with evidence,
what cannot be fixed without a decision from José.

## Evidence base (verified 2026-11-27)

- Active plan: `plan-correccion-bd-modulos-2026-11-13.md` (v3.5, phases 0–8), approved 2026-11-13. Its three
  files lived in the untracked `claude/` folder and no longer exist on disk or in git. Recovered verbatim
  (2 identical full reads each, contiguous line numbers, no truncated lines) from session `0728262f`:
  plan 222 lines, phase 0 sheet 68 lines, panel status 63 lines. Next phase per v3.5: Phase 1 (entity and
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
  - RDD: risk medium, consent granted, lineage `review-cfb18cedb3f6bb5e` open; the reviewer could not run
    because the `claude` CLI is signed out (`claude auth status`: `loggedIn: false`). Pending.
- [x] T2 `monitoreo:redis` lock expires in 10 min instead of 1440 (inline: 1 source file + test).
  - RED: `Failed asserting that 1440 is identical to 10`; GREEN after `withoutOverlapping(10)`. Measured with
    the file store: lock held at +9 min, free at +11 min. Pint and PHPStan clean; full suite 1044 tests, 1040 passed, 4 skipped, 0 failed.
  - Why: with `pcntl` loaded the lock is released on SIGTERM, but a SIGKILL (OOM in the 256 MB scheduler
    container, `docker kill`) leaves it in place for 24 h and silences Redis monitoring for a day.
- [x] T3 The five daily tasks' locks expire in 23 h instead of 1440 min (inline: 1 source file + test).
  - RED: `consentimientos:depurar` lock still held at 2026-11-28 02:15:00 after a kill at 02:15:05 (expires at 1440 min).
    GREEN with `withoutOverlapping(23 * 60)`. `AgendaCandadosTest` checks every locked task against its own next run.
  - Also from the RDD review of T1/T2 (approved, 2 advisory findings): `AgendaSinRedisTest` now releases the locks in
    setUp/tearDown (no flaky leftovers) and pins that pause/interrupt are off (ADR 0009 states it).
  - Full suite on disposable Postgres/Redis: 1047 tests, 1043 passed, 4 errors = `LectorRedisRealTest` (`Class "Redis"
    not found`: the host PHP has no phpredis, `php -m` confirms; CI installs it). Pint and PHPStan clean.
- [ ] D1 Restore the three plan v3.5 documents in the repository, or keep them outside — decision of José
  (they were deliberately kept untracked; the repository is public).
- [ ] D2 `pm.max_children` value for production — decision of José with load test (sheet 0C).
- [ ] D3 Fix or source the "auditoría H3" reference in the 0C sheet — needs the audit José refers to.
- [ ] D4 SMTP provider: without it no alert reaches a person (`MAIL_MAILER=log` in staging and production).
- [ ] D5 Promote to `main`: `sonda.yml` does not exist on the default branch, so the probe never runs
  (GitHub API 404). PR #753 (develop → staging) is open.
- [ ] D6 nginx `[crit]` alert and cache threshold (ADR 0010): not implemented; depend on D4 to reach anyone.
- [ ] D7 The 0D sheet document does not exist; only ADR 0007 records its approval.

## Next step

RDD review of T3 (lineage of T1/T2 is acknowledged and burned).
