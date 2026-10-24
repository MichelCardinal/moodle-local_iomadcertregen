## 0.12.6-alpha

- Align contributor instructions with the actual local_iomad dependency, optional administrative archives and explicit reviewed publication.
- Reconcile the guide's theme limitation with the tested New Learning version and distinguish native Boost acceptance from scoped New Learning laboratory checks.
- Advance the metadata-only savepoint; production behaviour, schema, capabilities and language strings are unchanged from 0.12.5.

## 0.12.5-alpha

- Keep presentation-only row action menus out of native system-report downloads, preventing late navigation/session diagnostics from contaminating CSV data in developer mode.
- Exercise actual system and custom-report CSV responses, quoted course names, both selected certificate templates and explicit native access refusals for authenticated non-administrators.
- Preserve native role/certificate permissions, report audiences, retained PDFs and all correction behaviour. The metadata savepoint advances without a schema migration.
- Retain strict CI failure propagation and update the expected acceptance totals for the additional inventory scenarios.

## 0.12.4-alpha

- Correct publication and email guide wording: authorised preparing operators can act; retaining previous PDFs follows the enforced site policy and reviewed choice.
- Advance only the metadata savepoint. Product runtime, schema, capabilities, language and mail behaviour are unchanged.

## 0.12.3-alpha

- Refresh HTTP acceptance assertions for the current native capability label and the completed batch page, which intentionally has no further publication form.
- Bind batch verification to the identity captured during preparation and check both archived originals as well as reviewed replacement bytes, native issue identity and unchanged mail status.
- Advance only the metadata savepoint; keep manager-default migration at its original boundary. No correction, permission, archive or email behaviour changes.

## 0.12.2-alpha

- Replace the per-role edit workflow with Moodle’s native searchable multiple-role selector, with existing grants preselected and one explicit save.
- Validate the complete selection, refuse unknown roles or stale definitions, and save via the native capability API in a database transaction. Preserve existing refusals for unselected roles and local overrides.
- Keep the manager default grant migration at its original version boundary so later UI updates do not restore a grant an administrator removed.

## 0.12.1-alpha

- Simplify the native role-permission shortcut to a clear Yes/No choice, role-specific action labels and textual access badges.
- Keep detailed role, company-context and prerequisite guidance in a keyboard-operable disclosure. Preserve the link to Moodle standard role settings.
- Translate the simple choice to native permissions: Yes explicitly grants; No removes this role’s grant while preserving an existing prevent/prohibit decision. Other roles and local overrides still apply. Warn before replacing an existing role-definition prohibition.

## 0.12.0-alpha

- Delegate activity corrections through native Moodle role capabilities, with manager defaults and optional teacher grants, without requiring IOMAD company associations.
- Add a site-administrator permission editor for installed and custom roles. Edit only the plugin capability, preserve local overrides and role assignments, validate CSRF and reject stale forms.
- Respect separate-group boundaries for issue lists, correction services, administrative PDF delivery and batch selections.
- Add an institutional archive policy: operator choice, always retain or never retain. Enforce it in individual and batch publication without changing existing archives or audit records.
- Keep the global inventory, custom report source, settings and administrative backup/restore restricted to site administrators.

## 0.11.0-alpha

- Offer administrative archive retention or no retention when publishing an individual correction or a batch.
- Record the archive choice, correction reason and historical publication time independently of the active learner publication.
- Capture optional archive retention deadlines; restrict access immediately at expiry and clean only expired original archives, preserving the corrected PDF and correction record.
- Preserve existing archives during upgrade and include policy metadata in privacy export and course backup/restore.

## 0.10.3-alpha

- Increase the reviewed batch limit from 20 to 30 existing issues, including server validation and local language drafts.
- Exercise preparation, confirmation and publication of 30 real PDFs and rejection of 31 issues.

## 0.10.2-alpha

- Recognise two additional reviewed IOMAD 4.5 native file variants for publication, retaining exact file fingerprints and refusing unknown variants.
- Verify original-file retention and access controls against these variants. No core or theme changes.

# Changes

## 0.10.1-alpha — 2026102401 (unreleased)

- Record complete minimum-runtime qualification: PHP 8.1 on IOMAD 4.5 and PHP 8.2 on IOMAD 5.0/5.1, each with 122 tests / 399 assertions and strict diagnostics.
- Verify all six local quality checks in both native 4.5 and 5.2 contexts. Keep the native PHP 8.4 test-generator deprecation and unexecuted remote CI explicit.
- Update validation documentation and the native version savepoint only. No functional, schema, language, file or retention behaviour changes. Preserve the previous alpha archive and tag.

## 0.10.0-alpha — 2026102400 (unreleased)

- Add explicit publication of a confirmed, non-expiring correction to the learner's existing activity button. Preserve native issue identity/code/date and serve the exact reviewed PDF through a protected endpoint.
- Retain previous native PDFs before replacing identified activity/history file content through Moodle File API. Match history conservatively by beneficiary, course, date and unique filename; refuse ambiguous names and mixed certificate engines. Preserve older/unmatched histories.
- Add bounded selection of up to 20 issues, separate PDF review/confirmation/publication, idempotent preparation and per-item failures. Do not send collective or automatic email.
- Protect native history file delivery with owner, recorded-company, department and child-company checks, including validation of the selected company's actual visibility. Use forced private downloads for recognised ordinary/token entry points.
- Bind new rendering inputs to active beneficiary company memberships. Refuse stale delegated access and exclude other-company historical records rather than sharing a snapshot across tenant boundaries. Older company-bound preparations require a fresh review.
- Handle later enrolment periods and superseded publications without attaching an old issue to a new course period. Refuse stale sources/targets and malformed archive provenance.
- Extend privacy export/deletion and native backup/restore to retained originals. Restoration does not republish, send mail or restore batch/request identifiers. Keep native IOMAD data under its owner's lifecycle after plugin cleanup/removal.

## 0.9.0-alpha — 2026102310 (unreleased)

- Add explicit email submission of the exact confirmed corrected PDF to its beneficiary, using Moodle mail configuration and recipient language. Native email delivery settings prompt the action without automatically sending.
- Require the preparing administrator, session protection, explicit destination acknowledgement and an unchanged profile address. Reject ineligible recipients and disabled site mail rather than recording a false success.
- Store bounded submission history and a hashed duplicate-protection token; use the existing issue lock. Definite failures require a new explicit request; submitted, interrupted and uncertain attempts are never automatically retried.
- Include mail metadata in privacy exports/deletion and backup/restore; do not export request tokens or send on restoration. Declare the external email data flow. Learner activity/history PDFs remain unchanged.
- Consolidate earlier metadata-only alpha savepoints without changing data or retention deadlines. Keep the real schema migration at this version; comply with PHP Mess Detector limits without disabling rules.

## 0.8.0-alpha — 2026102309 (unreleased)

- Add a read-only, site-administrator certificate activity inventory, with current template identifiers, categories, course and activity visibility, native filters, pagination, export and navigation actions.
- Provide the same configuration entity as a native Moodle custom report source. Restrict both source discovery and data access to site administrators.
- Include activities without issues or IOMAD company links; omit activities being deleted. Previously issued PDF templates are not inferred. No correction or delivery behaviour changes.

## 0.7.1-alpha — 2026102308 (unreleased)

- Correct the qualified/unsupported interface messages, which still named only 4.5/5.0 after the 5.1/5.2 extension. Use version-independent wording tied to the verified source profile.
- Update the French and Spanish translation drafts consistently. No rendering, access, file, privacy or schema behaviour changes.

## 0.7.0-alpha — 2026102307 (unreleased)

- Add separately qualified IOMAD 5.1 and 5.2 native template profiles, retaining the 4.5/5.0 family.
- Use an explicit native template scope on 5.1/5.2, avoiding the missing activity alias in their current handler without modifying IOMAD.
- Read saved-original evidence through Moodle File API on 5.1/5.2, avoiding deprecated native URL generation.
- Replace the removed local_iomad_track dependency with the shared local_iomad component.
- Adapt company fixtures to the new native schema; exercise all six supported PDF templates.
- Use the current course-format deletion API in 5.2 lifecycle tests.
- Extend the proposed CI matrix to the current 4.5, 5.0, 5.1 and 5.2 branches and isolate the Behat database. Remote CI is still pending.

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
