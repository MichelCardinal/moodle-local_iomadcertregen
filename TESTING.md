# Validation of 0.7.1-alpha

Local qualification on 23 October 2026. This is an unreleased laboratory candidate. GitHub Actions have not run; Marketplace approval, accessibility certification and Moodle badges are not claimed.

## Editorial revision and normal MAMP laboratory

0.7.1-alpha (2026102308) corrects two compatibility messages; the renderer, access controls and data model are unchanged from 0.7.0. The four-branch matrix below was executed at 0.7.0, and is retained as the functional baseline rather than reported as a new run of the editorial patch. All six Moodle Plugin CI checks were rerun successfully for 0.7.1.

Additionally, 0.7.1 passed 52 tests / 226 assertions on IOMAD 5.2.4, PHP 8.3.30 and MySQL 8.4.11 in a normal MAMP laboratory. Browser checks under Boost and New Learning 12.2.7 prepared, reviewed and confirmed separate fictional PDFs; the native original, code and date were unchanged. A Boost browser download matched the reviewed bytes. These targeted theme checks do not qualify all pages, every theme version or assistive technology.

## Executed integration matrix (0.7.0 functional baseline)

PHP 8.3.30, PostgreSQL 17.11, macOS Darwin 25.6.0 arm64. Each environment has an independent database and file roots. PHPUnit 9.6.34 is used on 4.5; PHPUnit 11.5.55 on 5.0–5.2.

| IOMAD update | Official commit | PHPUnit |
| --- | --- | --- |
| 4.5.15 | `3e7d23c1b60018f8f540c1fbf929e1baa6755b7f` | 52 tests, 226 assertions |
| 5.0.11 | `addcdf8f4647b853fd85b6817571d5529caf6cfd` | 52 tests, 226 assertions |
| 5.1.8 | `7738c4391469132ec54b9ea7505fd29922c2b015` | 52 tests, 226 assertions |
| 5.2.4 | `ef8e8a1e6093518091783b37666d9cb1f4bd8a7d` | 52 tests, 226 assertions |

The retained runs have no warning, notice, skipped or risky result. Modern PHPUnit commands explicitly fail on these diagnostics. Earlier attempts exposing missing native template variables, deprecated URL generation and a deprecated lifecycle test call are retained separately, not counted as passing runs.

Tests exercise all six qualified native PDF templates, exact native issue/file preservation, beneficiary/company branding with an administrator from another company, permissions, CSRF, cross-activity identifiers, ownership, duplicate submissions, source/PDF changes, expiry, privacy exports/deletions, lifecycle cleanup and real backup/restore archives. They use fictitious data. Plain Moodle is unsupported. Unknown native library/template revisions are refused; future patch releases need qualification.

Only PHP 8.3.30 has been executed for this candidate. Earlier 0.6 MySQL 8.0/8.4 results on IOMAD 4.5.13/5.0.9 are historical evidence, not a new 0.7 runtime proof. The proposed remote matrix covers the current branches with MySQL/PostgreSQL and additional PHP versions; its result remains unknown.

## Native interfaces

4.5/5.0 use the qualified historical rendering function. 5.1/5.2 moved that responsibility into local_iomad; their current handler omits an activity variable required by its native templates. The adapter supplies an explicit local template scope without changing native files. It verifies the returned PDF type and beneficiary/issue aliases.

On 5.1/5.2 the native reporting helper uses file_encode_url, deprecated by Moodle. The adapter uses Moodle File API and core\url for the same saved-original area, requiring exactly one PDF at the issue root. It does not call a native issuer, change enrolment or overwrite a native file. The declared dependency is the shared local_iomad component, with each interface family's exact files checked before rendering.

Native stored-certificate regeneration already exists in IOMAD. It replaces an IOMAD history file; this independent administrative-copy workflow preserves originals and provides review before confirmation. Do not claim that IOMAD has no regeneration feature.

## HTTP acceptance

The five non-JavaScript Behat scenarios use actual HTTP forms and protected downloads with BrowserKit. They verify preparation/confirmation, mandatory acknowledgement, authenticated learner refusal, exact PDF bytes and response headers, standard event attribution, pagination/search on 1,001 native issues with 1,001 saved originals, and a configured middle name. All four current branches passed **5 scenarios / 71 steps each**. Measured feature durations were 2m0.01s (4.5), 2m2.48s (5.0), 2m2.89s (5.1) and 2m1.51s (5.2) on these laboratories. Exact logs and commands are retained in the maintainer's release report. A single-user workload does not establish concurrency capacity or a response-time guarantee.

The repository-only helper checks native test-site readiness before enabling the test URL. By exact test-file hashes it excludes duplicate policy steps on all branches, incompatible SAML on 4.5/5.0, and a misplaced custompage generator on 5.2. The fixed SAML context on 5.1/5.2 is retained under its actual class name. No plugin scenario or runtime component is removed. Changed native test files require review. These results do not qualify the excluded third-party suites.

IOMAD 5.2's native OIDC installer creates a fixed PostgreSQL index name. A separate behat_dbname avoids a collision between PHPUnit and Behat prefixes; no native index or source is changed. Use separate disposable databases and file roots, as documented in README.md.

BrowserKit does not execute JavaScript, render a PDF viewer or test assistive technology. Historical targeted keyboard checks under Boost and New Learning do not constitute a complete accessibility audit. New Learning is not claimed tested on every new branch. iPad/NAMO and native Moodle-app integration are outside this administrative V1.

## Release boundaries

The 0.7 upgrade adds no schema or lifecycle change and preserves existing correction deadlines. Fresh isolated PHPUnit sites were installed at 0.7. Course/activity restore still omits the native saved original PDF; this plugin restores only its own confirmed copy and refuses a fresh correction without original evidence. Full site backup remains necessary for full recovery.

Run the six Moodle Plugin CI checks with no disabled rules. Identify the final source commit/tag and exact ZIP SHA-256 in an external manifest, compare every archive byte with that source and the qualified copies, package English strings only, and exclude private laboratory material. Never rewrite an earlier archive.

A permanent private security-reporting route, the public tracker and remote runtime matrix remain publication gates. Security reviews and tests do not certify Moodle, IOMAD or the enclosing server.
