# Validation and reproducibility

This staged 1.0.0-rc1 candidate is not published or stable-approved. Product runtime, schema, capabilities, renderer profiles and language bytes are unchanged from 0.12.6-alpha. The new version/savepoint and its migration-test baseline passed focused local verification: six tests / sixteen assertions on an isolated IOMAD5.2/MySQL clone. All six quality commands passed, including PHPDoc coverage of all84 PHP files. These candidate-specific checks used PHP8.3.30 for CLI compatibility only; they do not qualify a patched web runtime. Previous full-suite results below belong to their executed commits, not a newly claimed run of this staged candidate.

## Completed Linux qualification baseline

The [GitHub Actions run](https://github.com/MichelCardinal/moodle-local_iomadcertregen/actions/runs/40113929250) executed commit `01ccdc758b1ff5a21fdada75cc2119b9b1becf97` using Ubuntu24.04 and Moodle Plugin CI4.5.11. The repository is currently private; no public accessibility of these links is claimed. Exact native commits are pinned in `.github/workflows/qualification.yml`.

All fourteen profiles complete 158 tests / 624 assertions and the six quality commands. Eleven jobs succeed. Three PHP8.4 jobs fail strictly on the native IOMAD Certificate test generator; they are not relabelled successful. All four PHP8.3/PostgreSQL HTTP profiles pass twelve scenarios / 145 steps.

| IOMAD | PHP | Selected database | Strict PHPUnit | HTTP acceptance |
| --- | --- | --- | --- | --- |
| 4.5.15 | 8.1.34 | mysqli 8.0.46 | Pass | Not selected |
| 4.5.15 | 8.3.36 | mysqli 8.0.46 | Pass | Not selected |
| 4.5.15 | 8.3.36 | pgsql 17.11 | Pass | 12 scenarios / 145 steps pass |
| 5.0.11 | 8.2.35 | mysqli 8.4.13 | Pass | Not selected |
| 5.0.11 | 8.3.36 | mysqli 8.4.13 | Pass | Not selected |
| 5.0.11 | 8.3.36 | pgsql 17.11 | Pass | 12 scenarios / 145 steps pass |
| 5.0.11 | 8.4.27 | pgsql 17.11 | Strict failure: native deprecation | Not selected |
| 5.1.8 | 8.2.35 | mysqli 8.4.13 | Pass | Not selected |
| 5.1.8 | 8.3.36 | mysqli 8.4.13 | Pass | Not selected |
| 5.1.8 | 8.3.36 | pgsql 17.11 | Pass | 12 scenarios / 145 steps pass |
| 5.1.8 | 8.4.27 | pgsql 17.11 | Strict failure: native deprecation | Not selected |
| 5.2.4 | 8.3.36 | mysqli 8.4.13 | Pass | Not selected |
| 5.2.4 | 8.3.36 | pgsql 17.11 | Pass | 12 scenarios / 145 steps pass |
| 5.2.4 | 8.4.27 | mysqli 8.4.13 | Strict failure: native deprecation | Not selected |

The startup probe verifies `session.upload_progress.enabled=0`. Modern profiles meet the official21October2026 security-release floors (PHP8.2.35,8.3.36,8.4.27); the session setting follows the23October advisory. The PHP8.1 profile is a legacy CLI-only compatibility check, not proof of a patched runtime or a hosting recommendation. No PHP8.1 web acceptance server runs. Regression tests and version checks do not certify the enclosing server or prove resistance to every vulnerability.

## Known PHP8.4 dependency boundary

The unmodified native generator declares `array $options = null` in `mod/iomadcertificate/tests/generator/lib.php:41` (under public/ on5.1/5.2). PHP8.4 deprecates that implicit nullability. Each strict profile finishes158/624 with one deprecation and exits nonzero. No native source is patched and no diagnostic is suppressed. Native authentication libraries also emit separate diagnostics during initialisation. A clean strict PHP8.4 qualification is not established. PHP8.3 at a corrected patch level is the recommended verified baseline.

## Commands

The workflow prepares unmodified native source and Composer dependencies, creates separate ordinary/PHPUnit/Behat databases and invokes native CLI installers. It then runs:

```sh
ci/bin/moodle-plugin-ci phplint plugin
ci/bin/moodle-plugin-ci phpcs --max-warnings=0 plugin
ci/bin/moodle-plugin-ci phpmd plugin
ci/bin/moodle-plugin-ci phpdoc --max-warnings=0 --moodle=moodle plugin
ci/bin/moodle-plugin-ci validate --moodle=moodle plugin
ci/bin/moodle-plugin-ci savepoints plugin
```

The workflow additionally verifies actual PHPDoc coverage and PHPMD output, uses fail-fast Bash pipelines and runs PHPUnit with `--fail-on-warning --fail-on-risky --fail-on-incomplete --fail-on-skipped`. Modern PHPUnit also receives `--fail-on-notice --fail-on-deprecation --fail-on-phpunit-deprecation`. BrowserKit Behat runs with `--strict` on PHP8.3/PostgreSQL. Exact commands and fixture-bound handling of native third-party Behat contexts remain in the workflow/scripts. No plugin scenario is excluded.

## Scope and other evidence

The suite covers existing-original evidence, exact confirmed PDF bytes, explicit publication, native learner/history delivery, group/company boundaries, thirty-issue batches, optional or imposed archives, expiry, role selection and refusals, Privacy API export/deletion, native backup/restore and individual MIME delivery. HTTP acceptance includes real CSV attachments with quoted values and authenticated nonadministrator refusals.

Earlier local strict full suites cover the same0.12.5/0.12.6 product baseline, all four native profiles and minimumPHP versions. Native uninstall was verified after a fictitious published correction and administrative archive: plugin-owned data was removed while ten protected native tables and the corrected native PDF remained intact. Michel opened/saved a fictitious PDF in Safari; the supplied file matched confirmed/native bytes. A CSV was also downloaded in the in-app browser. These are scoped examples, not universal browser or SMTP inbox guarantees.

The sealed security review covers the fixed0.10.1→0.12.3 diff; a bounded later review covers the CSV integration. Neither is claimed as a new security scan of this candidate. Accessibility evidence covers keyboard role selection and two Boost pages (fourteen named controls,29 sampled text contrasts); there is no screen-reader or exhaustive accessibility certification. iPad/NAMO and native Moodle-app integration are outside scope.

English is packaged. French/Spanish drafts remain outside distribution with166 matching identifiers/placeholders; no AMOS publication is claimed. Private logs, lab data, screenshots, runtime archives and translations are excluded from the ZIP and source repository. GPL licence and legitimate third-party attributions are retained.

## Publication status

Before stable publication, complete the candidate-specific checks and final archive/source comparison, establish effective public documentation/tracker and a permanent private reporting route, then obtain publication/submission authorisation. The Marketplace decides acceptance. No Moodle distinction or certification is guaranteed or claimed. Earlier ZIPs/tags stay immutable.
