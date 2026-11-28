# Plan: open items of phase 0 (D2, D3, D6, D7, D8, review suggestions, 0C audit)

Branch for this document: `docs/plan-pendientes-fase-0` (from `origin/develop` `898e839`). Each task below gets its
own branch and PR from `develop`. TDD: strict (global user rule); backend runner `php artisan test` on a disposable
Postgres/Redis like `ci.yml`; shell guards run with `bash`. Status: **plan only, nothing implemented**.

## Decisions taken by José (2026-11-28)

| Item | Decision |
|---|---|
| D4 SMTP | Will not be done. |
| D6 alert channel | A task on the server opens a GitHub issue with a fine-grained token (issues only), stored only in `/etc/sede/<entorno>.secretos.env` (ADR-0019). |
| D6 cache threshold | Alert when nginx serves `STALE` copies for 5 minutes in a row. |
| D2 measurement | Local load test against the local stack; no server access. CAP-01 stays pending until a production-equivalent environment exists. |
| D3, D7, D8 | Replace unsourced citations with the sources that exist; record that the 0C and 0D sheets are not preserved. |
| D9 GitHub secrets | Out of this plan (evidence recorded below). |
| 0C audits | Verify each item, include only the open ones, and version both audit files. |

## Evidence base (verified 2026-11-28, `origin/develop` `898e839`)

- **PHP-FPM.** No pool is configured in the repository; the image's `www.conf` rules: `pm = dynamic`,
  `pm.max_children = 5`, `start_servers = 2`, `min/max_spare = 1/3`. `docker/app/php.ini`: `memory_limit = 128M`.
  `compose.yaml` service `app`: `mem_limit: 1.5g`. Idle worker in `sede-app-1`: ~5 MB anonymous RSS. `ab` is
  installed on this PC. Archived plan `docs/archivo/plan.md` §15.3 (lines 1839–1848): `max_children = container
  limit × 0.75 ÷ memory_limit` (= 9 with today's numbers); line 2604: "recálculo con carga real (F10.3)".
- **Requirements.** CAP-01 (`entidad-transparencia/.../criterios-aceptacion-fuente-de-verdad.md:322`): 1000 VUs ×
  10 min, 0 × 5xx, p95 ≤ 500 ms, "contra un entorno equivalente a producción, no contra staging compartido".
  REND-01: TTFB p95 ≤ 200 ms with 100 VUs × 5 min. Staging and production share one server: `DEPLOY_HOST` is a
  single repository-level secret.
- **Alerts today.** `sonda.yml` opens an issue assigned to the owner (no SMTP needed). `VigilarRedis` sends
  `AvisoRedis` by mail (`VigilarRedis.php:98-104`); with `MAIL_MAILER=log` it reaches only the log. nginx logs
  `cache=$upstream_cache_status` to stdout (`nginx.conf:75-81`); caches: images 1 GB, documents 512 MB
  (`nginx.conf:198-199`). ADR 0010 line 34 assigns "the alert threshold and the `[crit]` notice" to DISP-01 without
  defining them.
- **Sources searched for D3/D7/D8 with no match:** whole disk (home, `/tmp`, worktrees), the 10 project folders of
  `~/.claude`, `~/.config/Claude` session stores, Engram, every local and remote branch and the full git history,
  GitHub PRs and issues. What exists: the 0D sheet name `ficha-fase-0d-integridad-bd-2026-11-18` (PRs #637–#642);
  the `ioredis` options "de la ficha (H35, B4)" (PR #704 body); "(auditoría H3)" added by `a06ff56` (#741) with no
  source. The 0C/0D sheets were produced in cloud sessions whose transcripts are not on this PC.
- **D9 (out of plan, evidence only).** Environments `staging` and `production` hold 8 application secrets each,
  created 2026-09-29/30 and never updated. Only two jobs can receive them (`despliegue.yml` `staging` and
  `produccion`, per GitHub Docs: "only available to workflow jobs that reference the environment"); no branch and
  no commit in history reads those names, `toJSON(secrets)`, `secrets[…]` or `secrets: inherit`. No Dependabot or
  Codespaces secrets, no `pull_request_target`/`workflow_run`, no self-hosted runners. Only
  `deploy/rotar-secretos.py:214,385` (`--github`, `--empujar`) writes them. ADR-0019 says they are not in GitHub
  Actions: that sentence is false.

## 0C audit items, verified one by one

Source: `docs/investigacion/auditoria-pendientes-fase-0c-revision-2-2026-11-27.md` §7 (unversioned in the main
checkout).

| Audit item | State today | Evidence |
|---|---|---|
| Redis monitor cannot alert when Redis is down | Fixed | #754: `Schedule::useCache('file')`, `withoutInterruptionPolling()` |
| `publicar-entorno.sh` rewrites `.env`/`compose.yaml`; `chown docker/` | Fixed | #750; `publicar-entorno.sh:28-31` skips a deployed environment; no `chown` left |
| Probe treats `cf-mitigated` as down; checks `X-Sede-Respaldo` | Fixed | #751; `sonda-sitio.sh:57,71` → "NO VERIFICABLE"; ADR 0009 line 77 corrected |
| ADR 0009 "Redis 8 sin medir" | Fixed | ADR 0009 line 79 |
| Local container `sede-prism` | Fixed | not in `docker ps -a` |
| Production branch policy and domain | **Open, blocks D5** | Policy allows `master` (does not exist) and `staging`; last production deploy (run `40796242910`) rejected: "Branch "main" is not allowed to deploy to production due to environment protection rules". Environment `production` has no variables (`SEDE_DOMINIO` undefined). |
| `SEDE_DESPLIEGUE_PRODUCCION=false` until ready | **Open** | Repository variable is `true` |
| Recipient + SMTP for Redis alerts | Superseded | D4 dropped; replaced by the D6 issue channel |
| Retire `rotar-secretos.py --github` | Excluded | Part of D9, out of plan |
| Local checkout up to date | **Open (local)** | main checkout on `develop`, 12 commits behind `origin/develop` |
| Local `horizon`/`scheduler` on old image | **Open (local)** | both run `6b1182752276`; `app` runs `sede/sede-app:dev` (`cb2d69c…`) |
| Docs that contradict the code (§4.6) | **Open** | see T4 |
| Ley 594/2000 consultation | **Open (legal, José)** | outside the code |

## Tasks

Planning heuristic: about 400 authored lines per task (advisory only). Route per task and evidence of each trigger
go in the PR.

- [ ] **T1 Lock tests: lower bound and margin** (inline, 1 test file). `AgendaCandadosTest`: a lock taken at the
  first run is still held partway through a legitimate run, and every expiry leaves at least 5 minutes before the
  next run. RED: change one expiry in a scratch copy and watch each new assertion fail. Checks: suite, Pint, PHPStan.
- [ ] **T2 Secret guard: review suggestions** (inline, 1 script). Comment why the self-test nests 3000 brackets
  (RecursionError) and why messages are cut at 100 characters; one helper for the hand-written self-tests;
  `cargar_prohibidos()` returns the set instead of filling a global; a self-test that an unreadable list exits 3.
  RED first for the new self-test. Checks: `bash deploy/pruebas/guarda-secretos-workflows.sh`, mutation on a copy of
  the real workflows.
- [ ] **T3 D3/D7/D8 citations** (delegated writer: 4 files). `docs/verificacion-fase-0c-2026-11-26.md:51` cites the
  archived plan §15.3 and F10.3 instead of "auditoría H3", and states that the global connection timeout is now
  2 s (`nginx.conf:162`). `ci.yml:206,211` and `sitio/server/utils/almacen-respaldo.ts:141` cite PR #704 instead of
  "ficha 0C". Both verification documents record that the 0C and 0D sheets are not preserved. `ci.yml` is CI
  configuration: comment-only change, called out in the PR.
- [ ] **T4 Documentation that contradicts the code** (delegated writer: 3 docs + 2 new files). Verified today:
  `verificacion-fase-0c:53` (sweep "not seen"; the revision-2 audit records a controlled test on 2026-11-27 01:50
  — cited as the audit's measurement), `:57` (`SEDE_DESPLIEGUE_PRODUCCION` is readable: `true`; file count
  recomputed when written); `docs/despliegue-secretos.md:25-36` ("six" mandatory secrets: today seven, and S3 is
  optional; "la canalización" generates none), `:66` ("no pipeline": `despliegue.yml` exists), `:83` ("no
  protection rules": `production` has a branch policy); `deploy/README.md:324-326` (says production runs by hand
  from `staging`; the job requires `github.ref_name == 'main'`, line 478, so it never runs). Version both 0C
  audits in `docs/investigacion/`. The ADR-0019 sentence on GitHub Actions secrets (`docs/adr/README.md:711`) is
  corrected to state the D9 facts.
- [ ] **T5 D2 `pm.max_children`** (delegated writer: pool file, Dockerfile, test, ADR). Load the local stack with
  `ab` on `/`, `/api/v1/tramites` and `/api/v1/entidad`, sampling the peak RSS of every FPM worker; compute the value
  with §15.3 using the measured peak instead of `memory_limit`, capped by 1.5 GB × 0.75. Add a versioned pool file
  copied by `docker/app/Dockerfile`, a guard that the image's effective `pm.max_children` equals it
  (`php-fpm -tt`), and a decision record. **Gate:** José approves the number before the PR merges. CAP-01 stays
  open.
- [ ] **T6 D6 alerts as GitHub issues** (delegated writer; split into two PRs).
  - T6a `monitoreo:redis` opens or updates one issue per signal type instead of mail, deduplicated like
    `sonda.yml` (label, one open issue). HTTP to the GitHub API through Laravel's `Http`, faked in tests (no real
    calls). Token read from config, new optional entry in `deploy/secretos-requeridos.txt`; without a token it
    logs an error and exits 1, as today. Removes `AvisoRedis` and the mail path made unused by this change.
  - T6b `[crit]` lines and `STALE` for 5 minutes in a row. nginx writes its access log to stdout and its error
    log to stderr (`nginx.conf:11,81`), so the scheduler cannot read them; the design (extra log on a shared volume vs another source) is measured first in this task.
    **Gate:** the measured design is shown to José before the code.
  - **José:** create the fine-grained token (Issues: read and write, this repository only) and install it with
    `publicar-entorno.sh`. Live check only after that.

## Actions for José (not code)

- [ ] J1 Production branch policy: replace `master` with `main` (GitHub → Environments → production). Blocks D5.
- [ ] J2 Decide `SEDE_DESPLIEGUE_PRODUCCION` (today `true`) and the production domain `SEDE_DOMINIO` (D-20).
- [ ] J3 Local: `git pull` in the main checkout (12 behind) and recreate `horizon`/`scheduler` on the current image.
- [ ] J4 Ley 594/2000 consultation (legal).
- [ ] J5 Create the GitHub token for T6.

## Out of scope

D4 (SMTP), D9 (GitHub copies of application secrets, `--github`), native review of the whole branch against
`585bcde`.

## Order

T1, T2, T3 and T4 are independent. T5 and T6 are independent of each other; T6's live check waits for J5. D5
(promote to `main`) waits for J1 and J2.
