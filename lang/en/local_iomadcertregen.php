<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * IOMAD Certificate Regenerator.
 *
 * @package    local_iomadcertregen
 * @copyright  2026 Michel Cardinal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'IOMAD Certificate Regenerator';
$string['iomadcertregen:regenerate'] = 'Manage certificate corrections, publication, archives and corrected email';
$string['scope'] = 'Prepare and review a corrected PDF, then confirm its exact contents. Publication to the learner and email are separate explicit actions. The native issue code, date, grades and completion are preserved.';
$string['chooseactivity'] = 'Choose an IOMAD Certificate activity';
$string['moduleversion'] = 'Detected IOMAD Certificate version: {$a}';
$string['qualified'] = 'The renderer and selected native template match a qualified IOMAD source profile.';
$string['unsupported'] = 'This renderer is not supported. Only qualified IOMAD source profiles are enabled. Outcome printing is not supported.';
$string['unsupportedtemplate'] = 'The selected template or an IOMAD library is missing or differs from the qualified version. No correction has been made.';
$string['nooriginal'] = 'No saved original PDF could be confirmed through this activity\'s native reporting interface. A preliminary issue record alone is insufficient.';
$string['invalidissue'] = 'The issue is incomplete or ambiguous. No new certificate can be issued by this tool.';
$string['unavailable'] = 'This copy is unavailable, expired or outside your permitted activity or user scope.';
$string['generationfailed'] = 'The renderer did not produce a usable PDF. The original certificate is unchanged.';
$string['sourcechanged'] = 'The source information has changed. Prepare and review a new copy before confirming.';
$string['busy'] = 'Another operation is using this certificate. Please try again shortly.';
$string['searchlearner'] = 'Search by learner\'s first name or last name';
$string['issuedate'] = 'Original issue date';
$string['eligible'] = 'Saved original confirmed';
$string['ineligible'] = 'No saved original confirmed, or issue unavailable';
$string['prepare'] = 'Prepare corrected copy';
$string['confirm'] = 'Confirm corrected copy';
$string['confirmed'] = 'Corrected copy confirmed';
$string['acknowledge'] = 'I have reviewed the information above and approve the action shown on this form.';
$string['currenttemplate'] = 'Current template: {$a}';
$string['original'] = 'Open saved original PDF';
$string['snapshotnotice'] = 'The PDF uses the current activity settings and current learner information. A printed date or grade may follow the activity\'s completion or grading rules. The original issue date and code in the database will remain unchanged.';
$string['preparedat'] = 'Copy prepared on {$a}';
$string['preview'] = 'Open PDF for review';
$string['download'] = 'Download confirmed PDF';
$string['confirmnotice'] = 'Review the prepared PDF before confirming. Confirmation retains exactly these bytes; it does not regenerate the document.';
$string['lastcopy'] = 'Last confirmed copy';
$string['cleanup'] = 'Remove expired and orphaned certificate copies';
$string['eventprepared'] = 'Corrected certificate copy prepared';
$string['eventconfirmed'] = 'Corrected certificate copy confirmed';
$string['eventdownloaded'] = 'Corrected certificate copy accessed';
$string['eventfailed'] = 'Corrected certificate copy preparation failed';
$string['privacy:metadata:operation'] = 'Metadata linking a corrected certificate PDF to its beneficiary and preparing operator.';
$string['privacy:metadata:files'] = 'Corrected PDFs and retained previous native PDFs contain learner information and are stored in the Moodle File API. Explicit publication also replaces identified existing IOMAD files; their access and lifecycle remain managed by their native components.';
$string['privacy:metadata:operation:contextid'] = 'Activity context.';
$string['privacy:metadata:operation:cmid'] = 'Course module identifier.';
$string['privacy:metadata:operation:issueid'] = 'Original issue identifier.';
$string['privacy:metadata:operation:userid'] = 'Certificate beneficiary.';
$string['privacy:metadata:operation:actorid'] = 'Administrator who prepared the copy.';
$string['privacy:metadata:operation:status'] = 'Preparation or confirmation state.';
$string['privacy:metadata:operation:requestkey'] = 'Hash used to prevent duplicate submissions.';
$string['privacy:metadata:operation:sourcehash'] = 'Fingerprint of rendering inputs, including the beneficiary\'s active company memberships.';
$string['privacy:metadata:operation:pdfhash'] = 'Integrity fingerprint of the PDF.';
$string['privacy:metadata:operation:timecreated'] = 'Preparation timestamp.';
$string['privacy:metadata:operation:timemodified'] = 'Last operation timestamp.';
$string['privacy:metadata:operation:timeexpires'] = 'Stored expiry of the prepared or confirmed copy; zero means no automatic expiry.';

$string['retentionsettings'] = 'Certificate copy retention';

$string['preparationlifetime'] = 'Preparation lifetime';

$string['preparationlifetime_desc'] = 'Validity of newly prepared copies, with a minimum of one minute. Existing deadlines remain unchanged. Expired copies become inaccessible and the hourly cleanup task deletes their PDF and operation metadata. The native certificate is preserved.';

$string['confirmedlifetime'] = 'Confirmed copy lifetime';

$string['confirmedlifetime_desc'] = 'Retention period starting when a new copy is confirmed. Set to zero for no automatic expiry. Existing confirmed copies keep their stored deadline. On expiry the copy becomes inaccessible; the hourly cleanup permanently deletes only this plugin’s PDF and operation metadata. Choose a duration according to your institution’s policy before enabling automatic deletion.';

$string['expiresat'] = 'This copy expires on {$a}. Expired copies are removed by the scheduled cleanup task.';

$string['noexpiry'] = 'This confirmed copy has no automatic expiry. Privacy requests and removal of its native activity or participants can still remove it.';

$string['restorecopyomitted'] = 'A corrected copy was omitted because its original issue, users, dates, expiry or PDF integrity could not be preserved. The native restoration continues.';

$string['originalunavailable'] = 'The original PDF is stored, but IOMAD\'s native saved-file download is broken in this version. The corrected copy is created separately and preserves the original.';
$string['noissues'] = 'No certificate issues match this selection.';

$string['inventory'] = 'IOMAD Certificate inventory';
$string['templateidentifier'] = 'Current template identifier';
$string['coursevisible'] = 'Course visible';
$string['activityvisible'] = 'Activity visible';
$string['issuedcertificates'] = 'View issued certificates';
$string['inventoryscope'] = 'This report shows the current settings of each IOMAD Certificate activity, including hidden courses and activities. It does not identify the template used in previously issued PDFs or determine whether a template is appropriate for a course.';
$string['inventoryhelp'] = 'Use the filters to search by course, category, activity or template. Each row represents one activity. Actions open the course, activity settings or issued certificates. No settings or certificates are changed by this report.';

$string['sendcorrectedemail'] = 'Email the corrected certificate';
$string['emailacknowledge'] = 'I authorise sending this confirmed PDF to the beneficiary shown above.';
$string['emailnotice'] = 'The confirmed PDF will be attached to an email sent to the current beneficiary profile address. This is an explicit send; activity settings do not send it automatically. Sending does not update the learner activity PDF or IOMAD history. A sent email cannot be recalled by this plugin.';
$string['emailconfigured'] = 'This activity uses email delivery. Corrections are not emailed automatically; use the separate email action after reviewing the confirmed copy.';
$string['emaildisabled'] = 'Email sending is disabled on this site. No message was sent.';
$string['emailrecipientchanged'] = 'The beneficiary email address changed. Reload this page and review the destination before sending.';
$string['emailrecipientinvalid'] = 'This beneficiary cannot currently receive email. Check the account status, confirmed address and email restrictions. No message was sent.';
$string['mailstatus_notrequested'] = 'This corrected copy has not been submitted by email.';
$string['mailstatus_sending'] = 'An email attempt is in progress or was interrupted. Check mail logs before any further action; no automatic retry is performed.';
$string['mailstatus_submitted'] = 'Moodle accepted this corrected PDF for email submission. This does not confirm receipt or reading by the learner.';
$string['mailstatus_failed'] = 'The email service reported a failure. The PDF remains available; review the configuration before making a new explicit attempt.';
$string['mailstatus_unknown'] = 'The email submission result is uncertain. Check mail logs; the plugin will not retry automatically or risk a duplicate.';
$string['emailsubject'] = 'Corrected certificate — {$a}';
$string['emailbody'] = 'Please find your corrected certificate for the course "{$a}" attached as a PDF. Keep this corrected version for your records.';
$string['eventemailattempted'] = 'Corrected certificate email attempted';
$string['privacy:metadata:operation:mailstatus'] = 'Result of an explicit corrected-PDF email attempt; not a receipt confirmation.';
$string['privacy:metadata:operation:mailtime'] = 'Time of the last explicit email attempt.';
$string['privacy:metadata:operation:mailrequestkey'] = 'Hashed request identifier used to prevent duplicate email submissions.';
$string['privacy:metadata:email'] = 'After explicit confirmation by an authorised operator, the corrected PDF is submitted to the site email service for delivery to its beneficiary. The plugin cannot recall delivered emails.';
$string['privacy:metadata:email:address'] = 'The beneficiary current profile email address used as the destination.';
$string['privacy:metadata:email:pdf'] = 'The exact confirmed corrected PDF, containing the beneficiary certificate information.';
$string['publish'] = 'Publish corrected certificate for the learner';
$string['published'] = 'This correction is published for the learner.';
$string['publishnotice'] = 'Publication replaces the saved activity PDF and only history PDFs with an exact course, learner, issue-date and unique filename match, without guessing a company association. Older, unrelated-company or ambiguous history entries are excluded. The archive choice below controls retention of previous PDFs. The native activity button will serve this confirmed correction. No email is sent. Publication requires a confirmed copy with no expiry.';
$string['publishtargets'] = 'Native PDF files to replace (including the activity PDF): {$a}. Review the list before proceeding.';
$string['publicationunavailable'] = 'This correction cannot be published. Use a confirmed copy with no expiry, prepared by your account, and check the native files and learner issue.';
$string['ambiguoushistory'] = 'The history cannot be matched safely because certificate activities may share a filename. Give each certificate activity a unique name and prepare a new correction.';
$string['retainedoriginal'] = 'Previous native PDF: {$a}';
$string['eventpublished'] = 'Corrected certificate published';
$string['privacy:metadata:operation:publishedtime'] = 'Time this operation became the active learner publication; zero when it is not active.';
$string['privacy:metadata:operation:issuehash'] = 'Fingerprint binding the publication to the native beneficiary, issue code and historical date.';
$string['bulk'] = 'Correct several learners';
$string['bulkprepare'] = 'Prepare selected corrections';
$string['bulknotice'] = 'Select up to 30 existing issues for this activity. Each PDF is prepared separately for review. Nothing is published or emailed automatically. Repeat with another selection for larger groups.';
$string['bulkresult'] = 'Prepared copies are listed below. Open each PDF, confirm it and publish it only after review. Ineligible issues are reported individually.';
$string['bulkselection'] = 'Existing certificate issues';
$string['bulkinvalid'] = 'Select between 1 and 30 distinct issues belonging to this activity.';
$string['bulkerror'] = 'This issue could not be prepared. Open its individual correction page for details.';
$string['prepared'] = 'Prepared copy';
$string['bulkconfirm'] = 'Confirm reviewed copies';
$string['bulkpublish'] = 'Publish reviewed copies for learners';
$string['privacy:metadata:operation:batchkey'] = 'Hashed identifier grouping a bounded selection prepared by one operator.';
$string['mixedhistory'] = 'This course also contains another certificate engine. Native history does not identify its source activity safely, so publication is refused. Separate administrative corrections and email remain available.';
$string['archivedpublication'] = 'This copy is an archived publication. Retained PDFs remain available. Prepare a new correction to publish again.';
$string['publicationexpiry'] = 'This copy has an expiry and cannot be published. To publish a correction, configure unlimited retention for future confirmed copies, then prepare and confirm a new copy. Existing deadlines are preserved.';
$string['publicationreviewrequired'] = 'This published certificate needs review by an authorised correction operator before you can view it. The learner retains their personal copy.';

$string['keeporiginal'] = 'Keep the previous PDF in an administrative archive?';
$string['archiveyes'] = 'Yes — keep an administrative archive';
$string['archiveno'] = 'No — do not retain the previous PDF';
$string['correctionreason'] = 'Reason for the correction';
$string['reasondefault'] = 'Correction of the certificate template selected in the activity.';
$string['invalidreason'] = 'Enter a correction reason of no more than 255 characters.';
$string['archivehelp'] = 'The learner receives the corrected certificate only. If you choose not to retain the previous PDF, it will not be available after successful publication. The correction record remains. Existing archives from earlier corrections are not changed.';
$string['archivelifetime'] = 'Administrative archive retention period';
$string['archivelifetime_desc'] = 'Retention for previous PDFs archived by future publications. Zero means no automatic expiry. At expiry the archive becomes unavailable and scheduled cleanup removes it; the corrected certificate and correction record remain. Existing archives keep their stored deadline.';
$string['correctionrecord'] = 'Correction recorded on {$a->date} by {$a->actor}. Reason: {$a->reason}';
$string['archivechosen'] = 'Archive choice: retain the previous PDF for authorised correction operators.';
$string['archivenotkept'] = 'Archive choice: do not retain the previous PDF. The correction record is preserved.';
$string['archiveexpiry'] = 'Administrative archive access ends: {$a}';
$string['privacy:metadata:operation:keeporiginal'] = 'Whether the previous native PDFs are retained under the chosen archive policy.';
$string['privacy:metadata:operation:originalexpires'] = 'Stored expiry of the administrative original archive, or zero for no automatic expiry.';
$string['privacy:metadata:operation:correctiontime'] = 'Historical publication time retained for the correction record.';
$string['privacy:metadata:operation:correctionreason'] = 'Administrative reason entered for the certificate correction.';

$string['invalidarchivepolicy'] = 'Choose a valid administrative archive policy.';

$string['superseded'] = 'This correction has been superseded by a later publication.';
$string['reasonlegacy'] = 'Reason not recorded by the earlier plugin version.';

$string['accesspermissions'] = 'IOMAD Certificate Regenerator — Access and permissions';
$string['rolepermissionack'] = 'I understand that this changes the plugin permission for this role throughout the site, subject to local overrides.';
$string['rolepermissionsscope'] = 'This page changes only the correction permission in the Moodle role definition. It does not assign roles, enrol users or grant access to additional courses. Category, course and activity overrides still apply. Site administrators retain access. The site-wide inventory remains restricted to site administrators.';
$string['rolepermissionsiomad'] = 'All installed roles are listed, including custom Moodle and IOMAD roles. A role assigned only in an IOMAD company does not automatically apply in a Moodle course. No company association is required for corrections from the activity.';
$string['rolepermissionsnative'] = 'Corrections also require the native Manage certificate activity permission (mod/iomadcertificate:manage). This page does not change that permission. Use the standard role settings if it is missing. Separate-group restrictions still apply.';
$string['invalidrolepermission'] = 'Select an existing role and a valid Moodle permission.';
$string['rolepermissionchanged'] = 'This role permission changed since you opened the form. Reload the page and review the current value.';
$string['archivepolicysetting'] = 'Administrative archive policy';
$string['archivepolicysetting_desc'] = 'Applies to future certificate publications, individually and in batches. A forced decision cannot be changed by the correction operator. Existing archives and their deadlines are unchanged. The correction audit is retained even without an original PDF archive.';
$string['archivepolicychoice'] = 'Let the correction operator choose';
$string['archivepolicykeep'] = 'Always retain the former PDF';
$string['archivepolicydiscard'] = 'Never retain the former PDF';
$string['archivepolicyforced'] = 'This archive decision is imposed by the site administrator.';
$string['archivepolicychanged'] = 'The archive choice conflicts with the current site policy. Reload the publication page and review the imposed choice.';
$string['roleaccessquestion'] = 'Allow this role to use the correction tools?';
$string['roleaccessyes'] = 'Yes — grant access through this role';
$string['roleaccessno'] = 'No — do not grant access through this role';
$string['roleaccessscope'] = 'Access applies only where the person has this role. Course access, native certificate permissions and group restrictions still apply. Another role may also grant access.';
$string['roleaccessprohibition'] = 'This role currently has a Moodle prohibition. Choosing Yes will replace that prohibition in the role definition; local overrides remain unchanged.';
$string['roleaccesssave'] = 'Save this role’s access';
$string['roleaccessintro'] = 'Choose a role, then select Yes or No. This controls preparation, publication, administrative archives and explicit corrected-certificate email.';
$string['roleaccessdetails'] = 'How access and Moodle roles work';
$string['roleaccessstatus'] = 'Access granted by this role';
$string['roleaccessenabled'] = 'Authorised';
$string['roleaccessdisabled'] = 'Not authorised by this role';
$string['roleaccessblocked'] = 'Prohibited in Moodle';
$string['roleaccessedit'] = 'Manage access: {$a}';
$string['rolepickerlabel'] = 'Authorised roles';
$string['rolepickerlabel_help'] = 'Search for and select the roles that should grant access to the correction tools. Selected roles are granted the plugin permission. Removing a role removes its existing grant; an existing refusal is preserved. Assignments, other roles and local overrides still apply. Site administrators retain access.';
$string['rolepickernone'] = 'No roles selected — site administrators retain access';
$string['rolepickerprohibited'] = 'Existing Moodle prohibitions for these roles: {$a}. Selecting one explicitly replaces its role-definition prohibition. Local overrides are unchanged.';
$string['rolepickerack'] = 'I approve granting access through the selected roles and removing grants from the other roles.';
$string['rolepickersave'] = 'Save the selected roles';
$string['rolepickerintro'] = 'Select the roles that may use the correction tools. Roles already authorised are preselected. Add or remove roles, then save your selection.';
