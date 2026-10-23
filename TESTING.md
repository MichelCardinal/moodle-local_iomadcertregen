# Validation of 0.6.0-alpha

Local qualification recorded on 23 October 2026. This is an unreleased laboratory candidate. Remote GitHub Actions have not run, no Marketplace approval or accessibility certification is claimed, and no Moodle badge has been awarded.

## Executed integration matrix

All runs used PHP 8.3.30 on macOS Darwin 25.6.0 arm64 with separate PHPUnit database prefixes and file roots.

| IOMAD source | Database | PHPUnit | Result |
| --- | --- | --- | --- |
| 4.5.13, `7bca43dee4cde3acb5d6e822e29e02f66a530dcb` | MySQL 8.0.44 | 9.6.34 | 46 tests, 197 assertions |
| 5.0.9, `0616692c833c495436eda551a4e972c2934f6257` | MySQL 8.4.11 | 11.5.55 | 46 tests, 197 assertions |
| 4.5.13, same commit | PostgreSQL 17.11 | 9.6.34 | 46 tests, 197 assertions |
| 4.5.15, `3e7d23c1b60018f8f540c1fbf929e1baa6755b7f` | PostgreSQL 17.11 | 9.6.34 | 46 tests, 196 assertions |

The corrected native saved-file handler follows a different assertion path from the older defective handler. No warning, skipped test or risky result is counted as a passing compatibility proof. PHP 8.1 and 8.2 remain proposed CI targets, not locally executed runtimes. Only the exact native library/template variants in `classes/local/qualified.json` are accepted. IOMAD 5.1/5.2 and plain Moodle are unsupported.

The nine PHPUnit classes cover administrator and module permissions, CSRF, cross-activity identifiers, ownership, duplicate submission, source/PDF changes, expiry, native preservation, beneficiary/actor privacy, company branding, lifecycle cleanup and real backup/restore archives. They use fictitious data. The tests do not establish the security of the enclosing server or every native dependency.

## HTTP acceptance

**Five scenarios and 71 steps passed** using non-JavaScript Behat with Behat 3.14.0, Mink 1.11.0 and BrowserKit 2.2.0 on the current 4.5.15/PostgreSQL environment. The scenarios use actual form submissions, an authenticated learner refusal, a protected PDF response and the standard Moodle event log. Preparation bytes are captured before confirmation; the subsequent PDF download must have the same SHA-256, PDF MIME type and attachment disposition. Its preparation, confirmation and access events must identify the correct operation, module context, actor and beneficiary.

The list scenario creates 1,001 native issues and saved originals, checks their exact counts, then tests the 30-row page boundary and a search from the second page. This is a single-user qualification workload, not a concurrent capacity benchmark or a production response-time guarantee. A separate scenario checks a configured full name including a middle name. These tests exposed and verified the fix for incomplete name fields passed to Moodle's fullname API.

The complete feature took 1 minute 57.55 seconds, including fixture creation. The observed initial list navigation took 0.283 seconds on this laboratory. The detailed log and workload metrics are retained by the maintainer. See README.md for setup commands. The repository-only helper `.github/scripts/prepare_behat.php` uses Moodle's test-site readiness and configuration APIs. It excludes two exact, incompatible IOMAD acceptance contexts: SAML has an incompatible method override; the policy context duplicates core policy definitions. Their runtime components and files remain unchanged. No plugin scenario is excluded, and these results do not qualify the excluded SAML/policy contexts. A changed test-file hash requires review.

BrowserKit does not execute JavaScript, render a PDF viewer or test assistive technology. Previous targeted keyboard checks under Boost and New Learning and two action-link contrast measurements are useful evidence, not a complete accessibility audit. iPad/NAMO and native Moodle-app integration are outside this administrative V1.

## Quality and lifecycle

Six Moodle Plugin CI 4.5.11 checks pass locally: savepoints, PHP syntax, Moodle CodeSniffer with zero warnings, PHP Mess Detector, component validation and PHPDoc with zero warnings. The installable PHP files are checked; the repository-only CLI helper has separate syntax and Moodle style checks. The proposed CI also runs the acceptance feature on the current IOMAD/PHP 8.3/PostgreSQL job. Its remote result remains unknown.

The 0.5 to 0.6 upgrades preserve both normal laboratories' ten monitored native table hashes, their themes, existing certificate files and correction deadlines. This release changes no schema or lifecycle rule. Fresh isolated PHPUnit sites were installed for 0.6. The prior 0.5 uninstall/reinstall test removed only owned schema/files/configuration/tasks and preserved native evidence; that destructive test is not presented as rerun for 0.6.

Native course/activity restoration still omits the original module PDF. This plugin restores its own confirmed PDF separately and refuses a new correction without native original evidence. Full site backup is necessary for full recovery.

## Release evidence

Before a release is published, identify its source commit/tag and exact ZIP SHA-256, verify every archived byte against that source, check English-only language packaging and keep private laboratory files out of the repository. Do not rewrite an earlier release archive. This document deliberately does not embed its own commit or archive hash, which belong to the external release manifest.

A permanent private security-reporting route, the public issue tracker and the remote runtime matrix remain publication gates. The maintainer must verify those services before listing active links or submitting the Marketplace package. Security reviews and tests do not constitute Moodle or IOMAD certification.
