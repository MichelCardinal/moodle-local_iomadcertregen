# Changes

## 0.6.0-alpha — 2026102306 (unreleased)

- Use Moodle's complete name-field API in the issue list, avoiding developer warnings and supporting configured name formats.
- Add non-JavaScript Behat acceptance scenarios for preparation, acknowledgement, confirmation, HTTP download, audit attribution, permission refusal and pagination.
- Five BrowserKit HTTP scenarios / 71 steps passed locally, including 1,001 saved originals; no remote CI execution is claimed.

## 0.5.0-alpha — 2026102305 (unreleased)

- Package only English language strings for Marketplace submission; retain French and Spanish drafts separately for AMOS.
- Preserve private-laboratory translations through Moodle local language customisations.
- Qualify PostgreSQL 17 and the corrected official IOMAD 4.5.15 file handler as a separate fingerprint variant.
- Limit actor Privacy exports to operation metadata; export PDFs only for their beneficiary.
- Add beneficiary-company branding tests with a different administrator company.
- Prepare a PHP/database CI matrix, contribution guide and issue forms; remote CI has not been executed.


## 0.4.0-alpha — 2026102304

- Qualify the identical native source profile on IOMAD 5.0.9 in addition to 4.5.13.
- Add identity, locale, enrolment-history and missing-prepared-file integration tests.
- Support PHPUnit 9 and 11 metadata without deprecated annotation warnings in PHPUnit 11.
- Verify authenticated HTTP PDF delivery before/after confirmation and anonymous denial.
- Verify cross-site restoration with new fictitious user IDs; declare native saved-original omission.
- Display a translated empty state when no issue matches the search.
- Check required acknowledgement and keyboard focus under Boost and New Learning.


## 0.3.0-alpha — 2026-10-23

- Qualified native saved-file download defect reproduced; original-file link replaced by a clear diagnostic, with the original retained.
- Native course/activity backup and restore for confirmed, unexpired administrative copies.
- Administrator ownership and user-data requirements on both export and restore.
- Native issue and user remapping, original code/date checks and unchanged historical deadlines.
- Restore warnings for omitted copies, hash/format/MIME/size checks and cleanup of invalid imported files.
- Bounded PDF validation shared by rendering and restored copies; MIME and size checked before delivery.
- Integration tests using real Moodle archives, including non-admin owners, date offsets and malformed metadata.

Database version `2026102303` adds no schema field and preserves existing copies and deadlines.

## 0.2.0-alpha — 2026-10-23

- Native site settings for prepared and confirmed copy lifetimes, applied only at their next state transition.
- Existing deadlines preserved through upgrade and configuration changes; confirmed copies remain unlimited by default.
- Expiry shown in the review page; expired confirmed copies denied access and removed by hourly cleanup.
- Exact positive integer file-route validation and simpler owner-link checks.
- Tests organised into service, access, evidence, privacy, lifecycle and retention classes.
- Prepared GitHub Actions qualification workflow pinned to the qualified IOMAD commit; remote execution pending.

Database version `2026102302` changes no schema and does not recalculate existing deadlines. Earlier laboratory ZIPs are retained under their original names and hashes.

## 0.1.0-alpha — 2026-10-23

- Individual preparation, review, confirmation and protected download of an administrative corrected copy.
- Existing native issue and saved-original checks; no native replacement, new issue or email.
- Site-administrator access, module-context capability, request idempotence and issue locks.
- Exact prepared-content confirmation, integrity fingerprints and Moodle events.
- Privacy metadata, export and deletion for beneficiaries and preparing administrators.
- Expired preparation cleanup and lifecycle observers.
- Local English, French and Spanish strings and PHPUnit service tests.

Internal database versions: `2026102300` introduced the operation table; `2026102301` registers lifecycle cleanup while preserving existing records. No Marketplace release has been published.
