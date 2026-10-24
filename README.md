# IOMAD Certificate Regenerator

Independent Moodle local plugin by Michel Cardinal. Requires IOMAD and its IOMAD Certificate activity. This is a **laboratory alpha**, not a Marketplace-approved release or a Moodle/IOMAD-certified product.

## English

### Purpose

An authorised site administrator can prepare, review, confirm and download a separate corrected PDF for an existing certificate issue. The original issue identifier, code, stored issue date and native files remain unchanged. No email, new issue, grade, completion or enrolment change is performed by the correction service. The corrected copy does **not** replace the learner's activity PDF or IOMAD history.

### Current compatibility

- Qualified branches: IOMAD **4.5, 5.0, 5.1 and 5.2**, with the exact native library/template fingerprints in `classes/local/qualified.json`. Current tested updates are 4.5.15, 5.0.11, 5.1.8 and 5.2.4. See [TESTING.md](TESTING.md) for runtimes, evidence and limits.
- The 0.7.0 functional baseline passes 52 integration tests / 226 assertions on each current branch locally with PHP 8.3.30 and PostgreSQL 17.11. The editorial 0.7.1 revision also passes 52 tests / 226 assertions on IOMAD 5.2.4 with MySQL 8.4.11; Boost and New Learning 12.2.7 have targeted browser workflow checks on that laboratory. Earlier 4.5.13 and 5.0.9 qualification is historical evidence for 0.6, not a new 0.7 runtime claim.
- A different source library or customised/missing template is refused. Matching a Moodle version number or installing successfully does not establish compatibility. Future updates require qualification; this is not a promise to accept every patch automatically.
- Plain Moodle without IOMAD is unsupported. The four branches share the IOMAD Certificate activity; their native tracking interfaces differ.
- The six qualified standard templates are A4 embedded/non-embedded, letter embedded/non-embedded, company and companykanji. Outcome printing is disabled pending separate qualification. Company tests verify the beneficiary's seal when the administrator belongs to another company; other branding configurations are not exhaustively tested.

### Installation and access

Install the ZIP through **Site administration → Plugins → Install plugins**, or place its contents in `local/iomadcertregen` and run Moodle's normal upgrade process. For IOMAD 5.1/5.2, the manual destination is `public/local/iomadcertregen` inside the IOMAD installation; Moodle's ZIP installer resolves it automatically. Required components are `mod_iomadcertificate` (minimum `2024100700`) and `local_iomad` (minimum `2024100700`), both supplied by the qualified IOMAD distribution and declared in `version.php`. No Composer command or server-side build is needed to install this plugin ZIP. Back up the site before installing or upgrading.

This alpha is restricted to site administrators. Its module-context capability, `local/iomadcertregen:regenerate`, has no default role grants. Granting that capability to a manager or teacher does not enable delegated access in this release.

Open the tool from the activity settings menu or from **Site administration → Plugins → Local plugins → IOMAD Certificate Regenerator** and select the activity.

### Workflow

1. Locate an existing issue. An unambiguous native issue and a saved PDF in that activity must be confirmed.
2. Open the preparation page, check the beneficiary, original date/code, saved-original evidence and current template.
3. Acknowledge that the copy is independent, then prepare it.
4. Open the protected PDF for review. Confirming retains exactly those bytes, without recalculating the document.
5. Download the confirmed copy. The latest confirmed copy is also linked from the issue list.

If `savecert` was disabled and no original was saved by the activity, the action is refused even if an issue row exists or an IOMAD history copy exists. A preliminary row is not treated as proof of a previously generated PDF. This alpha does not inspect IOMAD history files as an alternative.

The older qualified IOMAD library has a confirmed defect in its native saved-file download handler. The corrected official library shipped with IOMAD 4.5.15 is qualified separately; its original-file link is enabled only when that exact library fingerprint is present. For the older revision, the preparation page reports this limitation instead of offering the broken original-file link. The file remains stored and unchanged; evidence checking and correction rendering do not invoke that handler. This plugin does not patch IOMAD.

On 4.5/5.0 the saved-original check parses the qualified native reporting function's output as data, with exact PDF MIME, local URL, component, context and issue checks. Its HTML is never rendered directly. On 5.1/5.2 that helper calls a deprecated Moodle function, so the adapter reads the same native area through Moodle File API, requires one PDF at the issue root and uses the current URL API. Unknown or ambiguous evidence is refused.

The current 5.1/5.2 native renderer omits the activity alias required by its templates. The adapter supplies an explicit local template scope and invokes only a fingerprint-qualified native template. It preserves the actor session and verifies the beneficiary/issue aliases and PDF result. No native file is patched; this is a version-specific integration rather than a stable public IOMAD rendering API.

IOMAD already supplies native stored-certificate regeneration, including in older branches. It replaces the IOMAD history file. This plugin provides a separate administrative copy with preparation, review and confirmation; it does not replace that native tool or the learner's PDF.

The qualified native course/activity restore omits the saved original PDF, although the issue record is restored. The plugin restores its own confirmed copy independently. That copy remains downloadable, but a new correction of the restored issue is refused while native saved-original evidence is missing. This plugin does not reconstruct or insert a native original; use complete site backups for full recovery.

### Dates and source changes

Rendering uses current learner information and current activity settings. Printed dates and grades follow the native template's rules and may differ from a historical document. Keeping the stored issue date does not reconstruct missing historical grades or text. The separate preparation/confirmation timestamp records the correction operation.

Printed language/date formatting follows the native renderer and the administrator session at preparation time. Changing the administrator locale after review does not change the reviewed PDF. A detectable change to the beneficiary language or timezone requires a fresh review. Historical certificates remain administratively accessible after unenrolment; regeneration does not reinstate enrolments.

The plugin fingerprints native issue/activity/identity/grade/completion/settings inputs and rejects detectable changes before confirmation. It preserves the reviewed bytes. Its lock serialises its own operations; it does not lock external IOMAD edits. Changes to external image assets or other untracked rendering dependencies may require a new preparation and are not claimed to be fully detected.

### Privacy and retention

Prepared and confirmed PDFs and operation metadata are stored under this plugin, in the activity context. Both beneficiary and administrator are declared in the Privacy API. The beneficiary can export their own PDF; an administrator acting only as preparer exports action metadata without other beneficiaries' PDFs. An approved erasure involving either person removes the shared corrected copy and owned metadata, without deleting the native certificate. Moodle manages its own event logs separately.

Open **Site administration → Plugins → Local plugins → Certificate copy retention** to set institutional lifetimes. Preparations default to 24 hours (minimum one minute); confirmed copies default to zero, which disables automatic expiry. The preparation deadline is captured when the PDF is prepared; the confirmed deadline is captured when it is confirmed. Changing settings does not change existing deadlines. The review page displays the stored expiry, or the absence of automatic expiry for a confirmed copy.

Expired copies are inaccessible immediately and the hourly task removes their PDF and operation metadata. This affects only this plugin's copies, never the native certificate. Set a confirmed-copy lifetime only after choosing the institution's retention policy. A zero default preserves existing copies until another authorised lifecycle or privacy action removes them. Activity/user deletion and reset of the native issues clean the associated copies. No external service or telemetry is used.

### Backup, reset and removal

Full site backups must include the database and Moodle file storage. Course and activity backups can also transfer confirmed, unexpired copies when **the backup owner is a site administrator and user data is included**. Temporary preparations and expired copies are excluded. A teacher's backup does not export these administrative PDFs, even if the teacher can back up ordinary course user data.

Restoring administrative copies is also restricted to a site-administrator restore owner with user data enabled. Moodle must map both the beneficiary and preparing administrator; their user records are therefore included in an authorised user-data backup through Moodle's native annotations. Treat such archives as personal data. Old numeric user identifiers are never used as a fallback.

Native IOMAD restoration creates new activity/issue identifiers; this plugin remaps its own links to those restored records. The restored native issue must belong to the restored activity and mapped beneficiary, with the same code and recorded issue date. Historical preparation/confirmation timestamps and expiry remain absolute. The reviewed PDF is restored without regeneration and its hash, size, format and effective PDF MIME type are checked. A changed native award date, missing mapping or invalid file causes that copy to be omitted with a Moodle restore warning; the native course restore continues. For example, moving the course start date can shift native issue dates and exclude the historical copy. Existing historical names and text in the PDF are preserved; current user profile names may differ.

A course reset removing native issues also removes their corrected copies. Resetting unrelated settings leaves copies whose issues remain. Moodle's normal plugin uninstall removes its schema, configuration, tasks and owned files while preserving native certificates. Full site backup remains necessary for disaster recovery; importing a course backup is not a rollback of the original site's history.

### Testing

The package includes nine focused PHPUnit test classes for the correction service, saved-original requirement, roles, cross-activity identifiers, CSRF, idempotence, source changes, integrity, privacy, beneficiary-company branding, lifecycle cleanup and real course-backup round trips. The candidate passed 52 tests / 226 assertions on each current branch listed above. All six supported native PDF templates are exercised. Only PHP 8.3.30 has been executed locally; PHP 8.1 and 8.2 matrix jobs remain pending.

Use a dedicated IOMAD PHPUnit database prefix and dataroot. Moodle's PHPUnit initialisation resets that test schema and test files; never point it at the ordinary site data.

```sh
php admin/tool/phpunit/cli/init.php --disable-composer
vendor/bin/phpunit --configuration phpunit.xml --testsuite local_iomadcertregen_testsuite
```

On IOMAD 5.1/5.2, the PHPUnit initialisation script is under `public/admin/tool/phpunit/cli/init.php`; run `vendor/bin/phpunit` from the installation root.

The repository includes `.github/workflows/qualification.yml` with a proposed PHP/database matrix, Moodle Plugin CI 4.5.11 and pinned IOMAD commits. Matrix preparation does not establish that every runtime was tested. This workflow is prepared but has not run remotely; no CI badge or broader compatibility is claimed. Repository CI configuration is excluded from the installable ZIP.

The repository also contains five non-JavaScript Behat scenarios, executed locally on each current IOMAD branch listed above with PHP 8.3.30 / PostgreSQL 17.11 and Moodle BrowserKit HTTP: 5 scenarios and 71 steps passed on each branch. They cover the real forms, required acknowledgement, permission denial, protected PDF bytes/headers, event attribution, pagination across 1,001 saved issues and a configured middle name. They do not test JavaScript, a PDF viewer or assistive technology.

Use a separately configured Behat URL, database prefix and dataroot. For IOMAD 5.2/PostgreSQL, configure a separate `behat_dbname`: the native OIDC install uses a fixed index name that collides across prefixes in one database. Install that disposable acceptance site with Moodle's `admin/tool/behat/cli/util_single_run.php --install`, then run the repository-only `.github/scripts/prepare_behat.php /absolute/path/to/moodle` (pass the `public` web directory on 5.1/5.2) and use its returned configuration with `vendor/bin/behat --suite=default --tags=@local_iomadcertregen --format=progress --strict`. Start a local HTTP server serving that test URL first. The helper checks Moodle's native test-site readiness and does not modify runtime source.

The helper reports narrowly scoped native test-context adjustments by exact file hashes. Policy duplicates core steps on all branches. The older SAML context is incompatible on 4.5/5.0; its corrected 5.1/5.2 context is retained under its actual class name. On 5.2 a custompage generator is misplaced in the step-context directory and is excluded there. Runtime components remain installed and unchanged; no plugin scenario is excluded. Changed contexts require review. These plugin acceptance results do not qualify the excluded third-party suites. The helper is included in the source repository and excluded from the installable ZIP.

No custom JavaScript, stylesheet or third-party PDF library is bundled. The source module supplies the rendering engine. The installable package contains English strings only. French and Spanish drafts are maintained separately for AMOS submission after approval; no availability through AMOS is claimed. Private laboratory local language overrides are outside the package. Check current Marketplace packaging requirements before submission.

## Français

### Utilisation

Cet outil crée une **copie administrative corrigée distincte** d'un certificat existant. Il conserve la délivrance originale, son code, sa date enregistrée et ses fichiers. Il ne remplace pas le PDF proposé à l'apprenant dans l'activité ou l'historique IOMAD. Il n'envoie aucun courriel et ne modifie ni note, ni achèvement, ni inscription.

Installer le ZIP par l'installateur de plugins Moodle ou dans `local/iomadcertregen`, puis lancer la mise à jour native. L'accès est réservé aux administrateurs du site. Ouvrir l'outil depuis les réglages de l'activité ou **Administration du site → Plugins → Plugins locaux**.

Choisir une délivrance, vérifier la preuve d'enregistrement de l'original et le modèle courant, préparer la copie, ouvrir sa prévisualisation, puis confirmer et télécharger. La confirmation conserve le même PDF. La liste donne également accès à la dernière copie confirmée.

### Limites de cette version

Version alpha de laboratoire, non approuvée par le Marketplace. Les profils activés couvrent les branches IOMAD 4.5, 5.0, 5.1 et 5.2 qualifiées dans `classes/local/qualified.json` : mises à jour testées 4.5.15, 5.0.11, 5.1.8 et 5.2.4. Les bibliothèques et modèles modifiés ou inconnus sont refusés; une mise à jour future exige une nouvelle qualification. L'impression des objectifs n'est pas prise en charge.

L’ancienne bibliothèque IOMAD qualifiée présente un défaut confirmé dans son téléchargement natif des fichiers enregistrés. La bibliothèque officielle corrigée dans IOMAD 4.5.15 est qualifiée séparément; son lien vers l’original est proposé uniquement si son empreinte exacte est reconnue. La préparation des anciennes révisions l'indique et ne propose pas ce lien défectueux vers l'original. Le fichier d'origine est conservé; sa preuve d'enregistrement et le rendu corrigé ne passent pas par ce téléchargement. Le plugin ne modifie pas IOMAD.

Un PDF original doit avoir été enregistré dans l'activité source. Une ligne de délivrance seule ou une copie présente uniquement dans l'historique IOMAD ne suffit pas. Les dates et notes imprimées suivent les réglages actuels du moteur; la copie n'est pas une reconstitution garantie des informations historiques.

Les réglages **Administration du site → Plugins → Plugins locaux → Conservation des copies de certificats** définissent les durées institutionnelles. Par défaut, les préparations expirent après 24 heures et les copies confirmées n'ont pas d'expiration automatique (valeur zéro). Le minimum d'une préparation est d'une minute.

L'échéance est enregistrée lors de la préparation, puis lors de la confirmation selon le réglage alors en vigueur. Modifier les réglages ne change pas les échéances déjà enregistrées. La page de vérification affiche l'échéance ou l'absence d'expiration automatique. À l'expiration, la copie devient inaccessible, puis la tâche horaire supprime son PDF et les métadonnées de l'opération; le certificat natif est conservé. Choisir la durée des copies confirmées selon la politique de l'établissement avant d'activer leur expiration. L’export Privacy du bénéficiaire inclut son PDF; celui d’un administrateur préparateur contient les traces de ses actions sans les PDF d’autres bénéficiaires. Les demandes de suppression Privacy pour le bénéficiaire ou l’administrateur suppriment la copie et ses données propres. Le certificat natif reste géré par IOMAD.

Les sauvegardes de cours et d'activités incluent les copies confirmées non expirées si leur propriétaire est administrateur du site et si les données utilisateur sont incluses. Les préparations et copies expirées sont exclues. Une sauvegarde réalisée par un enseignant n'exporte pas ces copies administratives, même s'il peut sauvegarder les données ordinaires du cours.

La restauration des copies exige également un administrateur du site et les données utilisateur. Les comptes du bénéficiaire et de l'administrateur préparateur sont annotés par les API natives pour conserver leur correspondance : protéger ces archives comme des données personnelles. Aucun ancien identifiant numérique n'est repris sans remappage.

La restauration native IOMAD crée de nouveaux identifiants d'activité et de délivrance; le plugin remappe ses propres liens vers ces enregistrements. Le code et la date enregistrée de la délivrance restaurée doivent rester identiques. La restauration conserve le PDF revu, ses dates de préparation/confirmation et son échéance absolue, sans nouvelle génération ni prolongation. Elle vérifie le fichier et son type PDF effectif. Si une correspondance manque, si les dates natives sont décalées ou si le fichier est invalide, la copie concernée est omise avec avertissement; le cours continue d'être restauré. Un changement de date de début du cours peut donc exclure une copie historique. Les noms et informations historiques imprimés dans le PDF restent inchangés, même si un profil utilisateur a évolué.

La restauration native qualifiée omet le PDF original enregistré, même si la délivrance est restaurée. La copie corrigée confirmée est restaurée séparément et reste téléchargeable. Une nouvelle correction est refusée tant que la preuve native de l’original manque. Le plugin ne recrée pas cet original; une sauvegarde complète du site reste nécessaire pour une reprise intégrale.

La langue et le format des dates imprimées suivent le moteur natif et la session de l’administrateur lors de la préparation. Un changement de langue de l’administrateur après vérification ne régénère pas le PDF. Un changement détectable de langue ou de fuseau du bénéficiaire exige une nouvelle vérification. Une désinscription ne recrée ni délivrance ni inscription.

Une réinitialisation supprimant les délivrances natives supprime aussi leurs copies corrigées. La désinstallation native retire les données propres du plugin et conserve les certificats natifs. Maintenir une sauvegarde complète du site pour la reprise après incident.

Les essais New Learning et Boost sont décrits séparément dans le rapport de validation. Les essais sur appareil mobile sont hors périmètre de cette V1. Les traductions françaises et espagnoles sont préparées séparément pour AMOS; le ZIP ne contient que les chaînes anglaises. Une interface responsive ne constitue pas une intégration dans l'application Moodle ni une certification d’accessibilité.

## Licence

GNU GPL v3 or later. Existing third-party copyrights and licences remain with their respective components. The plugin does not bundle IOMAD, Moodle or New Learning.
