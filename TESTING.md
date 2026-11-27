# Validation and reproducibility

## RC3 detailed inventory — 27 November 2026

RC3 exposes 36 selectable report columns, covering all 29 fields of the native IOMAD Certificate configuration table plus course/activity context and the derived date-enabled flag. All four installed native branches (4.5, 5.0, 5.1, 5.2) have the same certificate schema and form for these settings. Schema comparison is not a full runtime qualification of every branch.

Candidate-specific tests use PHP 8.3.36 with `max_input_vars=5000` and `session.upload_progress.enabled=0`, isolated database prefixes and fictitious data. No native source or third-party theme is changed. New cases cover all settings selected together, existing report preservation, date/delivery/time filters, default and unknown format values, safe text display, course-local grade/outcome references and explicit missing references. Existing administrator-only report/export checks remain in place.

| Candidate-specific environment | Result |
| --- | --- |
| IOMAD 4.5.13 (build 20260810), PHP 8.3.36, MySQL 8.0.46 | Strict full suite: 163 tests / 688 assertions pass |
| IOMAD 5.2.4 (build 20261005), PHP 8.3.36, MySQL 8.4.11 | Strict full suite: 163 tests / 687 assertions pass |
| IOMAD 5.2.4, PHP 8.3.36, PostgreSQL 17.11 | Focused inventory suite: 12 tests / 90 assertions; HTTP inventory CSV: 4 scenarios / 16 steps pass |

The focused inventory suite also passes 12 tests / 90 assertions on each MySQL profile. PostgreSQL uses the unchanged native commit `ef8e8a1e6093518091783b37666d9cb1f4bd8a7d`. Strict flags are those in the Commands section; initialization logs retain separate native task diagnostics.

The six Moodle Plugin CI commands pass; PHPDoc output covers all 86 PHP files. French and Spanish remain local drafts outside the ZIP, with 205 matching identifiers and placeholders. They are not claimed as published AMOS translations.

The earlier RC1 Linux matrix below remains historical. RC3 does not establish a new full PHP/IOMAD matrix or remove the PHP 8.4 boundary for the pinned native sources. This candidate is not a stable or Marketplace-approved release. A fresh GitHub matrix is required before candidate distribution. Earlier archives are immutable.

## RC2 Marketplace review follow-up

RC2 replaces the direct settings-table read with Moodle's configuration API. The regression suite exercises forced settings as well as the normal certificate correction and delivery paths. The RC1 matrix below is historical; it is not a full qualification of RC2. No change to the required IOMAD components or the PHP 8.4 support statement is inferred from this fix.

The historical 1.0.0-rc1 was a release candidate, not a stable or Marketplace-approved release. Product runtime, schema, capabilities, renderer profiles and language bytes are unchanged from 0.12.6-alpha. The new version/savepoint and its migration-test baseline passed focused local verification: six tests / sixteen assertions on an isolated IOMAD5.2/MySQL clone. All six quality commands passed, including PHPDoc coverage of all84 PHP files. These candidate-specific checks used PHP8.3.30 for CLI compatibility only; they do not qualify a patched web runtime. The full-suite results below belong to the exact RC1 commit. Later documentation-only updates do not claim another full matrix or change the tested runtime.

## Completed Linux qualification of RC1

The [GitHub Actions run](https://github.com/MichelCardinal/moodle-local_iomadcertregen/actions/runs/40116840450) executed commit `ea44c7088897d8bf7a272f7dc5178afd36db3c5a` using Ubuntu24.04 and Moodle Plugin CI4.5.11. Source, issue tracking and CI evidence are available in the repository. Use the private vulnerability-reporting route in SECURITY.md for suspected security issues. Exact native commits are pinned in `.github/workflows/qualification.yml`.

All fourteen profiles complete 158 tests / 624 assertions and the six quality commands. Eleven jobs succeed. Three PHP 8.4 jobs fail strictly on the native IOMAD Certificate test generator; they are not relabelled successful. All four PHP8.3/PostgreSQL HTTP profiles pass twelve scenarios / 145 steps.

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

## Known PHP 8.4 dependency boundary

The unmodified native generator declares `array $options = null` in `mod/iomadcertificate/tests/generator/lib.php:41` (under public/ on5.1/5.2). PHP 8.4 deprecates that implicit nullability. Each strict profile finishes158/624 with one deprecation and exits nonzero. No native source is patched and no diagnostic is suppressed. Native authentication libraries also emit separate diagnostics during initialisation. A clean strict PHP 8.4 qualification is not established. The native generator diagnostic was reported in [IOMAD issue 2822](https://github.com/iomad/iomad/issues/2822), now closed as completed. Official [IOMAD change d581d6a](https://github.com/iomad/iomad/commit/d581d6a4d0287ded7682cae083d57fc3cc041068) corrects the declaration in newer native revisions. The pinned commits in this historical matrix predate that fix. Newer native runtime revisions have separate history/publication changes and require their own qualification; this candidate does not approve them solely because the generator was fixed. PHP8.3 at a corrected patch level is the recommended verified baseline.

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

For RC1, English was packaged and French/Spanish drafts remained outside distribution with166 matching identifiers/placeholders; no AMOS publication is claimed. Private logs, lab data, screenshots, runtime archives and translations are excluded from the ZIP and source repository. GPL licence and legitimate third-party attributions are retained.

## Publication status

Candidate-specific checks and the final archive/source comparison are complete for v1.0.0-rc1. Review the native PHP 8.4 boundary and the final stable metadata/archive before a stable release. Public repository publication does not itself submit the plugin to Marketplace; obtain separate submission authorisation and review the current terms. The Marketplace decides acceptance. No Moodle distinction or certification is guaranteed or claimed. Earlier ZIPs/tags stay immutable.
