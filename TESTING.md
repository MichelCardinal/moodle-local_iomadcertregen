# Validation and reproducibility

## RC4 candidate qualification

RC4 uses version `2026112800`, release `1.0.0-rc4`, maturity `MATURITY_RC`. Its operational PHP, schema, capabilities, language strings, tests and workflow are those of the qualified development commit `5e13e0ea687cc2a43f1b2d890358de4b70fc1b3d` (version2026112703). Promotion changes only version/maturity metadata, a native metadata-only upgrade savepoint, and documentation. Earlier development ZIPs and RC3 releases remain immutable. Candidate-specific checks are recorded separately below; the preceding runtime results are not silently relabelled as a new candidate matrix.

[Development run46082578934](https://github.com/MichelCardinal/moodle-local_iomadcertregen/actions/runs/46082578934) has completed: **13 of 16 profiles pass**. All sixteen suites complete **183 tests /789 assertions**, including the three strict PHP8.4 profiles that fail due to one native generator deprecation. All six quality checks pass. Each of the four PHP8.3/PostgreSQL HTTP profiles passes **13 scenarios /163 steps**. The overall workflow is **failure**, not green. No native source or diagnostic was changed to obtain these results.

The reference modern baseline is PHP8.3.37 on the pinned IOMAD4.5.15,5.0.11,5.1.8 and5.2.4 variants, with MySQL and PostgreSQL as selected in the workflow. Historical4.5.5 passes PHP8.2.36/MySQL8.4 and PHP8.3.37/MySQL8.0. PHP8.2.36 also passes the selected5.0/5.1 MySQL profiles. PHP8.1.34 is a CLI compatibility check only, not a current hosting recommendation. Exact native commits are pinned in the workflow; matching a branch/version label alone does not qualify a modified or newer distribution.

A private remote IOMAD4.5.5 /PHP8.2.36 /MySQL8.4.9 /NewLearning12.0.0 site was upgraded to development2703. Fictitious issued-certificate corrections verified a site-specific template with the correct beneficiary and then a native template with newly enabled issue-date printing. Both native report PDF downloads equal their confirmed previews byte-for-byte; both administrative archives equal the preceding native PDFs. The issue ID, code, recorded date and one-learner count remain unchanged. No email was sent. The remote student's own activity button was not re-tested; that route is exercised in local historical HTTP and modern PostgreSQL acceptance. These private branded PDFs, backups and site data are excluded from public sources and ZIPs. This is a scoped theme check, not universal theme certification.

The upstream generator issue [2822](https://github.com/iomad/iomad/issues/2822) is already closed as completed, and newer official revisions include a fix. The three failing pinned source commits predate that fix. Newer revisions also change native history/publication interfaces and require a separate compatibility review and full qualification. RC4 does not qualify them solely because the generator changed, and no pending IOMAD reply is implied.

### Candidate promotion checks on 28 November 2026

Only version.php and the metadata-only savepoint differ in executable source from development2703. The final promotion passes the 14 permission/retention/delegation regression cases (42 assertions) on historical4.5.5 and5.2.4 isolated PHPUnit environments. The six quality commands pass, including savepoints and PHPDoc. The earlier32-case promotion attempt also passed on both branches, but its receipt predates savepoint consolidation and is not used as exact final-source evidence. The initial additional savepoint exceeded PHPMD's NPath threshold; consolidating metadata-only savepoints resolves it without suppressing the rule or removing any data migration.

Candidate runtime, tests and workflow remain byte-identical to development2703 except the two declared metadata paths. Source, tag and installation-archive identity are included in the release manifest. The associated GitHub commit checks supply the candidate-specific Linux matrix; the development run linked above remains separately identifiable. No Marketplace approval is inferred from promotion.

### Local development evidence

The current2703 strict full suite passes **183 tests** in each isolated environment below. PHP **8.3.36** is used throughout, with `max_input_vars=5000` and `session.upload_progress.enabled=0`. No ordinary laboratory course tables or native runtime source are changed.

| Native framework | Database | PHPUnit | Result |
| --- | --- | --- | --- |
| IOMAD 4.5.5, build 20250609 | MySQL 8.0.46 | 9.6.18 | 183 tests / 789 assertions pass |
| IOMAD 4.5.13, build 20260810 | MySQL 8.0.46 | 9.6.34 | 183 tests / 789 assertions pass |
| IOMAD 5.0.9, build 20260810 | MySQL 8.4.11 | 11.5.55 | 183 tests / 790 assertions pass |
| IOMAD 5.1.6, build 20260810 | MySQL 8.4.11 | 11.5.55 | 183 tests / 790 assertions pass |
| IOMAD 5.2.4, build 20261005 | MySQL 8.4.11 | 11.5.55 | 183 tests / 789 assertions pass |

The historical environment uses the complete official distribution at `ab4393b55c705df9962fd4c23018036039229f5c`. Its framework and history interfaces form a separate coherent fingerprint bundle. Combining older native interfaces with a newer framework failed during qualification and is not an accepted historical bundle. The 4.5.13 environment separately exercises historical certificate libraries without replacing its modern history/framework interfaces.

Eleven custom-template cases (76 assertions) cover current custom layouts/date settings on an existing issue, exact publication bytes, original issue identity/date preservation, changed PHP between preparation/confirmation/publication, native preference reads, broken/noisy/identity-changing templates and unsafe paths. Four legacy-route cases and a mixed-framework regression exercise the bounded historical download bridge. Syntax, Moodle CodeSniffer, PHPMD, savepoints, structure validation and PHPDoc pass. The temporary PHPDoc checker is removed after use; no diagnostic or quality rule is suppressed.

Historical HTTP acceptance passes **13 scenarios / 163 steps**, including privileged native downloads, the real learner button, protected original/history files, bounded publication, direct first system-inventory CSV, custom-report CSV and authenticated export refusals. A supported login callback prepares the administrator inventory's native table preferences before Moodle's download writer closes the session. Four additional unit cases cover silent/idempotent preparation, preservation of selected sorting, ordinary-user refusal and unrelated/custom reports. The initial page sort, native filters and download formats remain in use; no native source, diagnostic, permission or session safeguard is suppressed.

The fixture opens the Moodle site home after login, avoiding the historical company-dashboard error for company-less fictitious users. No plugin scenario is excluded. Earlier failing attempts remain recorded separately and are not presented as passing qualification.

The workflow has 16 pinned profiles, including historical4.5.5 on PHP8.2 and8.3. Run46079864887 exposed workflow issues (PHPUnit9 flags and an outdated acceptance count), and a privileged native download intercepted too late. Build2703 corrects these, and the completed replacement run is reported above. No strict clean PHP8.4 or SMTP inbox-delivery qualification is claimed. No managed security scan completed for this change because the available scan service could not open this worktree; source inspection and regression tests are not presented as an independent audit.

English strings are packaged; French and Spanish remain matching local drafts outside the ZIP, without an AMOS-publication claim. Updating to RC4 includes the template entry file in reviewed input fingerprints: pending earlier preparations need fresh review. Existing published personal snapshots and administrative archives are retained. A saved native original PDF is still required. Templates must follow the native rendering contract; hard-coded dates or other ignored settings are not automatically repaired, and arbitrary PHP or its external effects are not guaranteed.

## Completed GitHub qualification of RC3 — 27 November 2026

[Run 46049214689](https://github.com/MichelCardinal/moodle-local_iomadcertregen/actions/runs/46049214689) tested commit `dbfc0c55397eb71c897780f96c354e56e62c0911` against the 14 pinned profiles in the workflow. Eleven profiles passed. Three strict PHP 8.4.28 profiles failed on the native IOMAD Certificate generator's implicit-nullability deprecation; the overall workflow conclusion remains **failure**, not a completely green matrix.

All fourteen suites completed **163 tests / 687 assertions**. The three PHP 8.4 profiles additionally report one PHP deprecation in `mod_iomadcertificate_generator::create_instance()`. All six quality checks passed on every profile. The four PostgreSQL/PHP 8.3 HTTP runs each passed **12 scenarios / 145 steps**. No native source was patched and no strict diagnostic was suppressed.

Passing runtimes were PHP **8.1.34** (legacy CLI compatibility on 4.5 only), **8.2.36** (5.0/5.1 MySQL), and **8.3.37** (4.5/5.0/5.1/5.2 with both MySQL and PostgreSQL). The native commits remain those listed in the workflow. Modern PHP 8.3 is the verified baseline; PHP 8.1 is not a secure hosting recommendation. PHP 8.4 remains unqualified for these pinned native revisions. This result does not qualify newer IOMAD history/publication implementations.

Subsequent publication-evidence edits affect documentation only; PHP, tests, version and workflow bytes remain those of the tested commit. These documentation edits do not claim another matrix run. RC3 remains a prerelease, without Marketplace approval.

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

The earlier RC1 Linux matrix below remains historical. The completed RC3 GitHub matrix is documented above, including its PHP 8.4 failures. This candidate is not a stable or Marketplace-approved release. Earlier archives are immutable.

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
