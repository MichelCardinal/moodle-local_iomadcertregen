# IOMAD Certificate Regenerator

Independent Moodle local plugin by Michel Cardinal, component `local_iomadcertregen`. Requires IOMAD and its IOMAD Certificate activity. This is a **laboratory alpha**, not a Marketplace-approved release or a Moodle/IOMAD-certified product.

## Purpose

Find courses using IOMAD Certificate, review their current template settings, and correct certificates that have already been issued. Preparing and confirming a correction creates an independent reviewed PDF. **Publishing** that correction is a separate explicit action: it makes the reviewed PDF available through the learner's existing activity button and replaces the identified saved native PDFs. Former native PDFs may be retained as administrative archives according to the site policy and the reviewed publication choice.

The plugin preserves the native issue identifier, certificate code and recorded issue date. It does not create another issue, change grades, completion or enrolments, or impersonate a learner. Printed dates and grades still follow the native template's current printing rules. Email is a separate explicit action; neither confirmation nor publication sends it automatically.

## Requirements and installation

- Qualified IOMAD branches: **4.5, 5.0, 5.1 and 5.2**. Exact native variants are listed in `classes/local/qualified.json` and `classes/local/publication_profiles.json`. Matching a version number alone is insufficient. Unknown native libraries, policy implementations and customised/missing templates may be refused.
- Tested source updates and runtimes are recorded in [TESTING.md](TESTING.md). Do not interpret the branch range as proof of every patch, PHP version, database or theme configuration.
- Dependencies: `mod_iomadcertificate` and `local_iomad`, minimum version `2024100700`. Plain Moodle without IOMAD is unsupported.
- Six standard native templates are qualified: A4 embedded/non-embedded, letter embedded/non-embedded, company and companykanji. Outcome printing is excluded pending separate qualification.

Back up the complete site before installing or upgrading. Older company-bound preparations may need to be prepared again to validate their company scope; existing confirmed PDFs remain administratively available. Install the ZIP through **Site administration → Plugins → Install plugins**, or place its contents in `local/iomadcertregen` and use Moodle's normal upgrade process. On IOMAD 5.1/5.2 the manual destination is `public/local/iomadcertregen` inside the installation. Moodle's ZIP installer resolves this automatically. No plugin Composer command or JavaScript build is required.

Activity corrections, publication, administrative archives and explicit email require both `local/iomadcertregen:regenerate` and `mod/iomadcertificate:manage` in the activity context. Moodle manager archetypes are allowed the plugin capability by default; editing teachers require an explicit administrator decision. Category, course and activity role assignments and overrides apply, including separate-group restrictions. No IOMAD company/course/user association is required for this activity workflow. An IOMAD company role alone does not grant Moodle course permissions.

Under **Site administration → Plugins → Local plugins → IOMAD Certificate Regenerator → Access and permissions**, site administrators select authorised roles in Moodle's native searchable multiple-selection field. Existing grants are preselected. Click a suggested role to add it; remove its selected chip to withdraw its grant. Review and acknowledge the complete selection before saving. An empty selection leaves site administrators with access. The page edits only the plugin capability in role definitions using the native Access API, which records events and invalidates role caches. Existing refusal states for unselected roles remain intact; selecting a prohibited role explicitly replaces its role-definition prohibition, with a warning. Unknown role IDs and stale selections are rejected before changes; the selection is saved in a native database transaction.

This does not assign roles, enrol users, grant course access or change native certificate-management permissions. Other roles and lower-context overrides remain effective. Advanced Moodle role settings remain linked. The global inventory, custom report source, plugin settings, role editor and backup/restore of administrative copies remain site-administrator-only.

Under the same plugin's **Retention settings**, choose the administrative archive policy: operator choice (default), always retain or never retain. A forced choice is displayed on individual and batch publication forms and enforced by the publication service. A contradictory stale or forged request is refused before any PDF replacement. Policy changes affect future publications only; existing archives, their deadlines and correction audit records remain unchanged.

## Find courses and templates

Open **Site administration → Plugins → Local plugins → IOMAD Certificate Regenerator**. The ready-made inventory has one row per IOMAD Certificate activity, including hidden activities/courses and activities with no issues or company association. Activities pending deletion are omitted.

Columns describe category, course name/short name, activity name, current native template identifier and visibility. Use native filters, sorting, pagination and exports. Actions open the course, its activity settings or its issued certificates.

For native **Custom reports**, create a report using source **IOMAD Certificate inventory** under **Site administration → Reports → Report builder → Custom reports**. The same entity, columns, filters and conditions are available. Both discovery and data access require site administration rights; sharing a report audience does not grant these rights.

The inventory reports current configuration. It cannot identify the template used in an old PDF, decide whether a template is appropriate, or describe every background, seal and signature from its template identifier. Inspect the native activity settings where necessary. Reports and their exports do not modify certificates.

## Correct one learner

1. Save the desired template and related printing settings in the **native IOMAD Certificate activity settings**.
2. Open **IOMAD Certificate Regenerator** from the activity menu and locate an existing issued certificate.
3. Check the learner, original code/date and current settings, acknowledge the action and **Prepare corrected copy**.
4. Open the protected PDF. Check its actual appearance, beneficiary, code, printed date and text.
5. **Confirm corrected copy**. This retains exactly the reviewed bytes and does not render a different PDF.
6. To update learner access, choose **Publish corrected certificate for the learner**. Review the precise native files listed, acknowledge and publish.
7. If required, use the separate **Email the corrected certificate** action.

A unique existing issue and a saved original activity PDF are required. A preliminary issue row, or only a history file, is insufficient. If no PDF was saved by the activity, preparation is refused.

## Correct several learners

Choose **Correct several learners** from the activity's issued-certificate list. Select up to **30 existing issues per request**; selection pages are separate from the individual list. For larger groups, repeat with another selection.

Prepare the selection, open each labelled PDF link, then **Confirm reviewed copies**. A further review page offers **Publish reviewed copies for learners**, listing the native targets. Publication is never combined silently with confirmation or email.

Each beneficiary has an independent operation and result. An ineligible issue is reported by learner/code with a link to its individual page; successful preparations remain available. Repeating the same preparation form reuses its preparations. Confirmation/publication are idempotent per operation. A request interruption is not a claim that every item completed: reopen the batch or retry the same form and review the recorded results.

Changing activity settings or relevant learner information during a batch can invalidate its preparations. Prepare a new selection after resolving those changes. This is bounded web processing, not an unbounded background queue or automatic mass mailing.

## Publication and native history

Publication requires a confirmed copy with **no expiry**, prepared by the publishing operator with current correction permissions. Duration changes apply to future operations and never erase an existing deadline. If a copy expires, configure unlimited retention for future confirmed copies and prepare a new correction before publishing.

Publication replaces native file content using Moodle File API. When the enforced site policy and reviewed choice retain originals, each previous native PDF is first copied into the plugin's protected `original` area; otherwise no former PDF is retained there by that publication. Native file routes/names and native issue code/date are preserved. Replacement metadata is committed with the operation and its Moodle event. Only the latest publication for that learner/activity is active; previous reviewed PDFs and retained originals remain administratively available.

The native activity button serves the exact published PDF through a protected endpoint. It respects learner ownership, active enrolment, course/activity restrictions and the native minimum-course-time rule. A later enrolment period is not diverted to an obsolete issue. A subsequently changed template does not silently alter an already published correction.

For **IOMAD history**, the plugin replaces only existing files with an exact beneficiary, course, issue-date and unique native activity filename match. Older or unmatched history records are excluded and are not described as corrected. Company-specific history also requires a match to an unambiguous current beneficiary company; personal history remains owner/admin scoped. The review lists the actual targets. Duplicate activity filenames and courses mixing other certificate engines are refused rather than guessed. Renamed activities or independently recorded completion dates can prevent a history match. Review those cases separately; there is no automatic historical reconstruction.

Native history file entry points additionally enforce the owner, site administrator or authorised IOMAD manager's company/department scope. Company selection alone is insufficient; native selectable-company and capability checks apply, including explicit child-company rights. Historical owner access does not depend on a current course enrolment. Delegated activity access is refused for ambiguous multi-company membership or changed publication inputs; an administrator must review a new correction. Owner and site-administrator access to the retained personal PDF is preserved. Native history downloads are forced attachments with private, zero-lifetime delivery. The same policy applies to recognised ordinary and token-based file entry points; this is not Moodle mobile-app integration.

The plugin locks its own operations. Native settings edits, native certificate regeneration and other external processes do not share that lock. Avoid running those operations concurrently with correction/publication. A later native action can replace a native file again; prepare and review a new correction when needed.

## Email and message customisation

Email requires a confirmed, unexpired copy and its preparing operator with current correction permissions. The current beneficiary/address is displayed and must be acknowledged. No arbitrary destination is accepted. Disabled site mail and ineligible accounts/addresses are refused before transport.

Moodle's mail API receives the exact confirmed PDF, with the site's mail configuration and no-reply sender. The subject/body use the recipient's language and course name. Customise strings `emailsubject` and `emailbody` through **Site administration → Language → Language customisation → local_iomadcertregen.php**. Preserve `{$a}`, the course-name placeholder. These are language-wide defaults, not a per-course or per-send editor.

A successful API result means submission was accepted, not delivery/read confirmation. Submitted, interrupted and uncertain attempts are not automatically retried. A reported failure needs a new explicit attempt after reviewing configuration and mail logs. Mail history survives backup; request tokens and automatic sends do not.

The native activity's email-delivery setting prompts this separate action. Opening a published certificate does not automatically send another message. Copies already delivered by email cannot be recalled by changing Moodle files.

## Retention, privacy and recovery

Preparation expiry defaults to 24 hours. Confirmed copies have no automatic expiry by default; institutional settings can change future durations. Hourly cleanup removes expired/orphaned owned data. Publication is available only for non-expiring copies so its learner mapping and retained originals are not aged out automatically.

Privacy API metadata covers the beneficiary, preparing administrator, operation integrity, publication, batch and email history. Beneficiary export includes corrected PDFs and retained originals; an actor-only export does not include another learner's PDFs. Request/idempotency tokens are excluded. Approved deletion removes the plugin's own files and records. Native IOMAD files, including published replacements, remain under their native components' access and lifecycle rules.

Copies and retained originals are personal data. Course/activity removal, reset and privacy operations can remove the plugin's archives. This is not a legal archiving or permanent-retention guarantee. There is no one-click rollback feature; retained originals can be downloaded by administrators.

Administrator-owned course/activity backup with user information includes eligible confirmed copies and retained originals. Restoration validates native issue/user mappings, code/date and file integrity. It restores administrative copies only: no automatic publication or email, and no reuse of old batch/request keys. The qualified native activity restore omits its own saved original PDF; a new correction therefore remains refused until genuine native saved evidence exists. Full site backup is necessary for complete recovery.

Uninstall removes the plugin's tables/files, not the native IOMAD issue or PDFs. It also removes this plugin's download integration and additional native-history access guard. Review native ownership, access and recovery requirements before removal.

## Languages, themes and support boundaries

English is packaged in `lang/en`. French and Spanish drafts are maintained separately for local customisation/AMOS preparation; they are not claimed published in AMOS or included as packaged language directories. No customer-specific names, credentials or laboratory data belong in the distributable.

Native HTTP acceptance uses Boost; scoped normal-laboratory checks also cover New Learning on IOMAD 4.5. The independently identified New Learning 12.2.8 native-dashboard issue on IOMAD 5.2 remains outside this change and deferred. No core/module/theme patch is installed. iPad/NAMO and Moodle app integration are outside the agreed scope. No screen-reader or comprehensive accessibility certification is claimed.

See [TESTING.md](TESTING.md), [CHANGES.md](CHANGES.md) and [SECURITY.md](SECURITY.md) for evidence, limits and reporting readiness. This independent GPL v3-or-later plugin uses the installed IOMAD renderer; it does not claim IOMAD lacks native certificate regeneration.

## Guide rapide en français

Dans les paramètres natifs de l'activité, choisissez d'abord le bon modèle et ses réglages. Dans **IOMAD Certificate Regenerator**, préparez les corrections, ouvrez les PDF pour les vérifier, puis confirmez-les. La **publication** est une étape distincte qui met la version corrigée à la disposition de l’élève; la conservation des anciens PDF dépend de la politique d’archive et du choix autorisé. La sélection collective traite au plus 30 élèves par demande. Le **courriel** reste une action volontaire distincte.

L'inventaire est aussi disponible dans les **Rapports personnalisés**, avec la source **IOMAD Certificate inventory**. Il décrit le modèle actuellement configuré; il ne peut pas décider quel modèle devrait être utilisé ni identifier le modèle d'un ancien PDF. Les entrées d'historique sans correspondance certaine ne sont pas remplacées. Cette version reste une alpha de laboratoire.

## Acknowledgements

PDF generation uses the installed IOMAD Certificate module, whose native source credits Mark Nelson and IOMAD contributors. Native code and assets retain their own copyright and licence. No third-party theme code or laboratory files are bundled.

Version 0.10.2 also recognises two reviewed legacy IOMAD 4.5 source variants. Native files with unknown fingerprints remain unsupported; no core or theme modification is required.


### Previous PDFs and correction records (0.11)

Before individual or collective publication, choose whether to **keep an administrative archive** (default) or **not retain the previous PDF**, and enter a correction reason. The policy applies to the exact native PDFs replaced in that publication. If no archive is selected, their former content is not retained as a plugin file after successful replacement. Earlier administrative archives and historical entries excluded from the publication are not deleted by this choice. Learners use the corrected publication; original archives require administrative tool access.

The correction record retains the actor, learner/issue references, historical publication time, reason and archive choice, including when superseded. Existing privacy/lifecycle deletion rules still apply to that personal data. Replaying a publication does not rewrite its original decision. A superseded publication must be corrected through a new reviewed operation.

**Administrative archive retention period** in plugin settings applies to future retained archives only. Zero means no automatic expiry. The stored deadline does not change when settings change. Expired archives are unavailable immediately, and Moodle cron removes their plugin files without removing the corrected PDF or correction record. Existing archives keep their recorded deadline (unlimited for earlier versions). The policy does not remove external downloads, emails, server backups or other components’ unrelated files. Privacy exports can include personal records and retained files as appropriate; administrative-only UI access is not a statement about exemption from data-subject rights.
