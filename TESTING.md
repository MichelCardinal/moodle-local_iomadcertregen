# Validation of 0.10.1-alpha / 2026102401

This is a private, unreleased laboratory candidate. Local results do not establish Marketplace approval, remote CI success, accessibility certification or any Moodle distinction. Previous archives and tags remain immutable.

0.10.1 updates validation documentation and the native version savepoint only. All functional PHP, language, schema, fixture and acceptance files are byte-identical to the tested 0.10.0 tag. The complete suites below executed on that functional baseline. The editorial update is checked by native migration preservation, the six quality checks and an exact source comparison; a metadata-only revision does not justify repeating every functional suite.

## Executed PHP 8.3 matrix

Qualification uses PHP 8.3.30, macOS Darwin 25.6.0 arm64, independent test databases and file roots. PHPUnit 9.6.34 is used on 4.5 and PHPUnit 11.5.55 on 5.0–5.2.

| IOMAD | Exact official commit | PHPUnit database | Result |
| --- | --- | --- | --- |
| 4.5.15 | `3e7d23c1b60018f8f540c1fbf929e1baa6755b7f` | PostgreSQL 17.11 | 122 tests / 399 assertions |
| 5.0.11 | `addcdf8f4647b853fd85b6817571d5529caf6cfd` | PostgreSQL 17.11 | 122 tests / 399 assertions |
| 5.1.8 | `7738c4391469132ec54b9ea7505fd29922c2b015` | PostgreSQL 17.11 | 122 tests / 399 assertions |
| 5.2.4 | `ef8e8a1e6093518091783b37666d9cb1f4bd8a7d` | MySQL 8.4.11 | 122 tests / 399 assertions |

The four official stable branch heads were checked on 24 October 2026. This qualifies those source profiles, not every historical or future patch. Native rendering and publication interfaces are checked by SHA-256 at runtime. Plain Moodle and unknown/custom native revisions are refused.

Commands run from each native project root:

```sh
/path/to/php8.3.30 vendor/bin/phpunit --testsuite local_iomadcertregen_testsuite --fail-on-warning --fail-on-risky --fail-on-skipped
```

On 5.1/5.2, the executed command also uses `--fail-on-notice --fail-on-deprecation --fail-on-phpunit-deprecation`. On 5.0 the PHP 8.3 log contains no diagnostics; its PHP 8.4 command and the prepared remote workflow include all those strict flags. No ignored rules or suppressed diagnostics are counted as a clean result.

Tests cover six native PDF templates; beneficiary/company branding; permissions and CSRF; independent-connection issue locks; reviewed byte integrity; source changes; expiry; duplicate requests; enrolment periods; publication supersession; retained originals; exact native issue/code/date preservation; bounded and partial batches; hidden/suspended learner access; actual company, department and child-company policy; stale selected companies; transfers and multiple memberships; privacy export/deletion; lifecycle cleanup; and real native backup/restore archives.

The email tests capture the real Moodle MIME message before SMTP, inspect beneficiary and exact attachment bytes, and exercise duplicate submission, recipient changes, disabled mail, transport failure and interrupted/uncertain attempts. No real email is sent. SMTP delivery, inbox receipt and email-client rendering are not established.

## HTTP acceptance

On all four commits above, PHP 8.3.30 with PostgreSQL 17.11 passes **8 scenarios / 129 steps per branch**, using BrowserKit and actual HTTP forms/downloads. Behat 5.2 uses a separate database to avoid the native OIDC installer's fixed PostgreSQL index name; PHPUnit 5.2 uses MySQL as listed above.

Scenarios verify individual review/confirmation and exact PDF response bytes/headers, mandatory acknowledgement, administrative permission refusal, real event attribution, configured middle names, pagination/filtering across 1,001 native issues/originals, two-learner batch publication, retained original downloads, the learner's native activity button including ignored-user/action query variants, the beneficiary's native history download, and refusal for an unrelated logged-in learner.

The repository-only setup helper checks native test-site readiness and exact third-party test-file hashes. It excludes duplicate policy steps on all branches, an incompatible SAML context on 4.5/5.0 and a misplaced custompage generator on 5.2. The fixed modern SAML context remains. No plugin scenario or runtime component is removed; changed test contexts require a review. These results do not qualify the excluded third-party suites.

```sh
/path/to/php8.3.30 vendor/bin/behat --config=/isolated/behat.yml --tags=@local_iomadcertregen --format=progress
```

BrowserKit does not execute JavaScript or test assistive technology. Ordinary native history HTTP downloads are tested end to end. Token/webservice entry points use the same normalised-path guard and have been source-traced; actual token-authenticated HTTP and Moodle-app integration are not claimed tested.

## Executed minimum PHP runtimes

The full functional baseline also passes strict qualification on the minimum supported runtime for each older branch:

| IOMAD | PHP CLI | Database | Result |
| --- | --- | --- | --- |
| 4.5.15 | 8.1.34 | PostgreSQL 17.11 | 122 tests / 399 assertions, no diagnostics |
| 5.0.11 | 8.2.35 | PostgreSQL 17.11 | 122 tests / 399 assertions, no diagnostics |
| 5.1.8 | 8.2.35 | PostgreSQL 17.11 | 122 tests / 399 assertions, no diagnostics |

These are official Homebrew bottles extracted into a separate command-line test directory, with archive hashes and commands retained outside the distribution. No MAMP runtime, site setting or global Homebrew installation is replaced. Isolated library lookup and explicit CLI settings provide memory_limit=1024M, max_input_vars=5000, zend.assertions=1 and date.timezone=UTC. All strict PHPUnit flags above apply; the minimum runs also fail on incomplete tests. IOMAD 5.2's minimum PHP 8.3 is covered by the main matrix. This does not establish the unexecuted Linux/GitHub Actions matrix.

## PHP 8.4 boundary

The complete suite is additionally executed with PHP 8.4.17 on the three modern branches. Functional assertions pass, but strict qualification reports a deprecation in the native `mod_iomadcertificate` test generator: `create_instance()` implicitly marks `$options` nullable. The plugin does not patch that component or suppress the diagnostic. Retained logs identify the exact file and line. This is not a clean PHP 8.4 qualification and must be resolved or reassessed against an official native revision before a stable compatibility claim. The remote CI matrix remains unexecuted. The minimum-runtime results above are local macOS CLI evidence and are not presented as GitHub Actions results.

## Quality and migration

Moodle Plugin CI 4.5.11 runs savepoints, PHP syntax, Moodle coding style, PHP Mess Detector, XMLDB/plugin validation and PHPDoc. All six pass in both the native IOMAD 4.5 and 5.2 contexts with no disabled rule. The wrapper inspects PHPMD output as well as its exit status. Historical 0.7.1/0.8 exit-only summaries missed complexity violations; they do not prove a clean PHPMD run. The underlying code was corrected in 0.9 and earlier release archives are unchanged.

0.10 adds publication timestamp, issue identity hash and batch identifier through a native XMLDB migration. Fresh isolated PHPUnit installations and the normal laboratory's 0.9-to-0.10 upgrade are verified. Before/after evidence confirms existing native original/code/date and earlier confirmed PDFs survive migration. Publication is a separate explicit action: it intentionally replaces identified native file content only after archiving the previous bytes. Retained originals and correction PDFs are covered by privacy and backup/restore tests. Restored operations have no active publication or batch identifier, and never email or republish automatically. Native restore omits its own saved original, so a fresh preparation requires restoring that evidence separately.

Normal MAMP 5.2/Boost browser acceptance publishes two fictitious learners together, then checks exact reviewed native bytes, original archives, unchanged issue codes/dates, unchanged earlier administrative copies and disabled mail. PDFs are parsed and visually reviewed. No core, IOMAD or third-party theme file is patched.

## Limits before public release

A permanent private vulnerability-reporting route, public tracker/links and actual remote matrix results remain publication work. The 0.10.1 native version-only upgrade preserves the complete operation rows, certificate files and relevant native learning records in the normal MAMP laboratory. New Learning 12.2.7 has a deferred native-dashboard problem on IOMAD 5.2; final acceptance uses Boost. Full screen-reader/accessibility review, concurrent-load guarantees, iPad/NAMO and native Moodle-app support are not claimed. No certification of Moodle, IOMAD or the enclosing server follows from these checks.

Release provenance outside the distributable records the final commit/tag, ZIP SHA-256, commands, logs, file manifests and limits. Every ZIP byte is compared with the tagged source and qualified copies. The ZIP contains English only; French/Spanish are separate local translation drafts awaiting AMOS publication. No private reports, screenshots, credentials or laboratory data are included.
