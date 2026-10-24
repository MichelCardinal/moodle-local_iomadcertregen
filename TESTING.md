# Validation history and current local candidate

The current candidate is local 0.12.5-alpha / 2026102410; see the final sections for its scoped and pre-1.0 results. The earlier matrices below apply only to their recorded revisions. This is a private, unreleased laboratory candidate. Local results do not establish Marketplace approval, remote CI success, accessibility certification or any Moodle distinction. Previous archives and tags remain immutable.

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


## Additional local 0.10.2 qualification

PHP 8.3.30 / PostgreSQL 17.11: 123 tests and 401 assertions pass with the two additional reviewed IOMAD 4.5 legacy native files in the isolated 4.5.15 harness. This checks those native variants; it does not requalify every enclosing IOMAD installation. A new negative test refuses unknown or missing native files. The six Moodle Plugin CI checks pass in the 5.2 context. Other branch/PHP results above describe their recorded earlier revisions; a new complete release matrix is not claimed for 0.10.2.


## Batch size 30 (local 0.10.3)

Eight focused batch tests pass with 114 assertions on PHP 8.3.30 / PostgreSQL 17.11 in the isolated IOMAD 4.5.15 harness. They prepare, confirm and publish 30 actual PDFs, verify archived originals and unchanged native issues, and reject 31 issues. The harness retains its 0.10.2 installation metadata while executing the candidate batch code, tests and English strings. This is targeted batch validation, not a new complete cross-version release matrix or a response-time guarantee for other servers.


## Administrative archive policy (local 0.11.0)

Final complete suites pass on IOMAD 4.5.15 / PHPUnit 9.6.34 and IOMAD 5.2.4 / PHPUnit 11.5.55, each with 132 tests and 559 assertions, PHP 8.3.30 and PostgreSQL 17.11. Coverage includes no-archive publication, default archiving, expiry access and cleanup, immutable superseded decisions, reason bounds, explicit archive-choice validation, privacy metadata export, native identity/original preservation and real backup/restore (including malformed new archive metadata). Six Moodle Plugin CI checks pass; the last author-label display change was checked in IAB and by the tools.

MAMP 4.5.13 upgrade preserves the existing three publications and archived bytes, their codes/dates and learning data. The individual and batch publication forms are inspected under New Learning, with archive retention selected by default and a bounded reason. A separate fictitious activity is left confirmed but unpublished to inspect the new controls. Neither previous archives nor real mail are removed/sent during this UI demonstration. Fresh no-archive publication and expiry are exercised in isolated tests, not claimed as a full student-browser scenario on the ordinary MAMP site.

This local revision has not repeated the complete PHP/branch/Behat release matrix of earlier revisions. There is no new public release, ZIP, tag or remote CI run. Earlier versioned release evidence remains historical and unchanged.


## Native Moodle role editor and institutional archives (local 0.12.0)

Complete suites pass on IOMAD 4.5.15 and 5.2.4, PHP 8.3.30 / PostgreSQL 17.11: **144 tests / 600 assertions per branch**. The 5.2 run uses all strict diagnostic flags listed above. Tests exercise real manager publication without company associations; optional teacher permission and revocation; native role editing/events; preserved assignments and local prohibitions; stale forms and CSRF; separate-group lists and batch rejection; and both forced archive decisions with unchanged existing records. Existing privacy, native publication, mail and backup/restore suites pass as well.

Six quality checks pass in the native 5.2 context. Later wording updates clarify that authorised correction operators may use the delegated functions; English, French and Spanish files pass PHP syntax and key/placeholder parity checks. Documentation updates do not change the tested functional classes.

Normal MAMP 4.5.13 / New Learning migration preserves the existing published certificates, archives, issue identities and learning data. IAB verifies the administrator role list and teacher edit form, saving the original unset value without granting teacher access. It displays the three institutional archive choices; the laboratory stays in operator-choice mode. The new archive demonstration remains confirmed but unpublished. No real mail is sent and no existing archive is removed.

This is scoped validation, not a new full 5.0/5.1/PHP/Behat release matrix. The global inventory and custom report source remain site-administrator-only. Public repository, ZIP, Marketplace and AMOS steps remain unperformed.


## Simplified permission UI (local 0.12.1)

This update changes the permission editor presentation and adds a thin mapping of Yes/No to native permission constants. Targeted strict suites pass on IOMAD 4.5.15 / PHPUnit 9.6.34 and IOMAD 5.2.4 / PHPUnit 11.5.55, PHP 8.3.30 / PostgreSQL 17.11: **44 tests / 75 assertions each**. All eight transitions from the four native states to Yes/No are exercised, together with existing CSRF, stale-form, context and group tests. The complete 144-test results above apply to 0.12.0; the full release matrix is not repeated for this UI update.

Six Moodle checks pass. IAB verifies the simplified teacher form under New Learning without changing its current access. The disclosure uses native HTML semantics; selectors, acknowledgement and buttons use Moodle forms. No custom JavaScript or CSS is introduced. Normal MAMP 4.5.13 upgrade preserves existing native certificates, archives, operation metadata and learning data. EN/FR/ES have 159 matching keys/placeholders; translations remain local drafts.


## Native multiple-role selector (local 0.12.2)

Targeted strict suites pass on native IOMAD 4.5.15 and 5.2.4, PHP 8.3.30 / PostgreSQL 17.11: **50 tests / 91 assertions each**. Coverage includes native multi-role grant/removal, empty selection, retained refusals, unchanged assignments and unrelated permissions, stale/invalid input and CSRF/nonadmin rejection. A real native version-savepoint test verifies that a UI upgrade does not restore an intentionally removed manager grant. Six quality checks pass after placing the new selection tests in their own focused class.

IAB verifies the native autocomplete multiple-selection field under New Learning: two existing grants are preselected, searching for an editing teacher shows suggestions, and saving the unchanged selection succeeds. Before/after normal MAMP 4.5.13 snapshots confirm all native role-capability rows, role assignments and correction records are identical across migration and the unchanged-selection save. No additional role is granted during the UI demonstration. EN/FR/ES have 166 matching identifiers/placeholders. No custom JavaScript/CSS, public release, new ZIP or AMOS contribution. The full release matrix has not been rerun for this scoped revision.


## Pre-1.0 local qualification of 0.12.2

On 24 October 2026 the complete current candidate passes **158 tests / 624 assertions per branch** on IOMAD 4.5.15, 5.0.11, 5.1.8 and 5.2.4 with PHP 8.3.30 and PostgreSQL 17.11. The exact four official commits listed earlier remain the stable branch heads checked that day. This new matrix covers the current functionality, including optional administrative archives, institutional retention, Moodle-scoped correction and the native multiple-role selector. Fresh isolated 5.0/5.1 test installations are initialised with this candidate. Strict full suites also pass on PHP 8.1.34 for 4.5 and PHP 8.2.35 for 5.0/5.1, each with 158 tests / 624 assertions. PHP 8.3 is the tested minimum for 5.2.

The normal MAMP 4.5.13 / New Learning demonstration publishes a separate fictitious learner's confirmed correction. Before/after hashes establish exact reviewed PDF bytes in the native issue, the original bytes in the administrative archive, unchanged complete issue data and unchanged grade, completion and enrolment tables. The native learner button reaches the corrected-copy delivery route, with a web download event attributed to the impersonated fictitious learner and the real administrator. The in-app browser does not visibly render the PDF, so complete visual browser/download verification is not established. The course overview also redirects to the front page during impersonation; direct native activity access succeeds. This observation is recorded without attributing its cause to the plugin or changing the enclosing laboratory. The administrator session is restored and no real email is sent.

Historical BrowserKit/Behat acceptance above remains evidence for 0.10, not a newly executed acceptance matrix for 0.12.2. The new complete PHPUnit matrix does not by itself prove current JavaScript, screen-reader, SMTP receipt, all-database or remote CI coverage. Private versioned logs, commands, source fingerprints, package manifests and the remaining release gates are recorded outside the distributable.

PHP 8.4.17 is rerun on all three modern current branches: 158 tests / 624 assertions complete in each, but each strict process exits 1 because `mod_iomadcertificate/tests/generator/lib.php:41` declares `$options` implicitly nullable. This is an upstream test-generator diagnostic, not a clean qualification. No native code or diagnostic rule is changed. The current candidate has not repeated a full MySQL suite or the HTTP/Behat matrix; these remain stable-release work alongside remote CI and a complete visual learner download check.


## Acceptance refresh and additional database evidence (local 0.12.3)

The complete 0.12.2 suite additionally passes on **IOMAD 5.2.4 / MySQL 8.4.11 / PHP 8.3.30: 158 tests / 624 assertions**, with every strict diagnostic flag enabled. A separate native test tree, file root and unused PHPUnit table prefix isolate it from the ordinary laboratory. Before/after hashes confirm the normal laboratory's certificate issues, corrections, tracking, grades, completion and enrolment tables are unchanged. Its earlier installed plugin is not upgraded by this test.

A fresh run of the existing HTTP acceptance suite on 0.12.2 exposes two obsolete assertions: an earlier capability label and the assumption that an action form remains after a batch is fully published. Those failing logs are retained. Version 0.12.3 updates the assertions and splits batch acceptance into its own context to meet the unchanged complexity rules. The completed batch is bound to the key captured during preparation; both archived originals, reviewed native bytes, unchanged issue identity and no mail attempt are checked. No product runtime, schema, capability definition or language string changes. The latest metadata savepoint advances, while the manager-default migration stays at its original boundary.

With the exact 0.12.3 candidate, all four current official IOMAD source profiles pass **8 scenarios / 129 steps each** under PHP 8.3.30 / PostgreSQL 17.11. Native sites are freshly initialised, the existing reviewed third-party context exclusions/remapping remain explicit, and no plugin scenario is excluded. Servers bind only to 127.0.0.1 and are stopped; Behat sites are disabled after each run. Tests validate actual HTTP response bytes and headers for corrected copies, archived originals, the beneficiary's native activity button and native history, and refuse another authenticated beneficiary. These are BrowserKit HTTP tests, not JavaScript or screen-reader tests.

Six focused savepoint/role-selection tests pass with 16 assertions on both native 4.5 and 5.2. The six changed PHP files pass direct syntax checks; savepoints, coding style, complexity, XMLDB/plugin validation and PHPDoc pass with Moodle Plugin CI. A first complexity failure is retained and addressed by separating batch evidence, without suppressing a rule. An exact comparison with the previous immutable archive proves all product runtime, schema and language bytes unchanged; only tests, documentation and the version-only savepoint differ. The complete earlier functional matrices therefore remain evidence for their identical runtime baseline, rather than being described as newly rerun 0.12.3 suites.

In the ordinary 4.5 / New Learning laboratory, the multiple-role selector is checked by keyboard: search, Down/Enter selection, Shift-Tab/Right/Enter removal, then Tab to acknowledgement and submit. The temporary teacher selection is removed and nothing is saved. This is a scoped keyboard check, not a complete accessibility certification.

A separate fictitious download-mode activity publishes reviewed bytes with original retention. Issue and learning-table hashes remain unchanged and the real learner is refused the administrative archive. Its native button triggers the protected copy-download event, but the in-app browser download API times out and no downloaded test file is found. The PDF read through File API is rendered and visually checked with Poppler; this does not establish successful visual rendering or file saving in that browser. The passing native HTTP tests establish exact delivery independently. No real email is sent. The ordinary laboratory stays on the prior functionally identical revision; no general enclosing-site or third-party-theme qualification is inferred.

The upstream PHP 8.4 generator deprecation, remote CI, a permanent reporting channel and external publication remain separate gates. Previous ZIPs and tags are preserved. Current commands, logs, source comparisons and archive hashes live in the private versioned qualification report, excluded from the package.


## Documentation reconciliation (local 0.12.4)

The guide now consistently describes conditional original retention and authorised preparing operators rather than site administrators alone. This is an editorial correction, with a version-only savepoint and the matching upgrade-test baseline. Product runtime, schema, capabilities and language bytes are unchanged from 0.12.3. Its complete four-branch HTTP acceptance and identical 0.12.2 functional matrices remain the recorded evidence; they are not claimed as newly executed here. Targeted savepoint tests and current quality/package comparisons are recorded in the private versioned provenance.

The fixed 0.10.1-to-0.12.3 security diff review covers all 48 changed paths and reports no plausible vulnerability candidates. This scoped static review is not certification of Moodle/IOMAD or the deployment. Its private report preserves the exact reviewed revisions and limits. The 0.12.4 changes are documentation and version metadata only; prior ZIPs, tags and scan artifacts remain unchanged. PHP 8.4 native-generator diagnostics, remote CI and browser download-file verification remain open as described above.


## Inventory download qualification (local 0.12.5)

The new native HTTP scenarios verify real CSV exports from both the system inventory and a custom report, including a course name containing a comma and quotes, one header and two exact activity/template rows. They also require actual authenticated non-administrator sessions and explicit native permission-error codes, including an all-users report audience. A login redirect, generic server error, HTML response or matching text alone is not a successful export or refusal.

The first fixture attempt omitted a system-export parameter and did not create the viewer through Moodle's native Behat user step. Those failures are retained. With corrected fixtures, the custom CSV and access checks pass, while the system CSV reveals a real late-session diagnostic appended after its data. A native Moodle users-report control reproduces the same behaviour. An ineffective report-parameter trial is removed. The final integration reads the validated native download-format parameter and avoids preparing HTML action menus during export. Moodle caches the report before adding the format to its report parameters, so that distinction matters. No core source, session diagnostic or data assertion is weakened. The focused native 5.2 export scenarios pass (4 scenarios/16 steps). Final full HTTP acceptance passes on all four exact official profiles above: **12 scenarios / 145 steps per branch**, PHP 8.3.30 / PostgreSQL 17.11, with strict execution and all plugin scenarios included. The normal inventory page is opened before its export, matching the native UI workflow. Servers are stopped and test sites disabled after execution.

Michel also confirms opening and saving a fictitious published PDF in Safari. The supplied saved file is byte-identical to the confirmed document and native publication; its rendered page is reviewed. This verifies that example's saved file, without claiming an automated in-app-browser download or universal browser coverage. Three current screenshot drafts replace an obsolete presentation image, outside the plugin ZIP.

The final 0.12.5 source also passes the complete strict PHPUnit suite on all four exact official profiles with PHP 8.3.30 / PostgreSQL 17.11: **158 tests / 624 assertions each**. Minimum-runtime full suites pass with the same totals on PHP 8.1.34 / IOMAD 4.5 and PHP 8.2.35 / IOMAD 5.0 and 5.1. IOMAD 5.2 / MySQL 8.4.11 / PHP 8.3.30 passes **158/624** in the isolated test namespace; normal-lab protected records remain byte-identical before/after. Fresh test installation, six focused savepoint/role tests (16 assertions on each of 4.5 and 5.2), and all six Moodle Plugin CI quality checks pass. Full syntax checking covers 84 PHP files. The old test baseline is not substituted for this new runtime qualification. Commands, logs and hashes are retained in versioned private provenance; the controlled package is compared byte-for-byte with the qualified source copies. The upstream PHP 8.4 generator diagnostic remains unchanged and unresolved; remote CI and permanent reporting/public links remain external release work. No stable 1.0 or Marketplace approval is claimed by this alpha.
