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
$string['iomadcertregen:regenerate'] = 'Prepare and confirm corrected certificate copies';
$string['scope'] = 'This tool creates a separate corrected copy for the administrator. It preserves the original issue, its code and date, grades, completion and native files. It does not replace the PDF in the learner\'s activity or IOMAD history and does not send email.';
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
$string['acknowledge'] = 'I understand that this is a separate administrative copy and that the learner\'s original files will remain in place.';
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
$string['privacy:metadata:operation'] = 'Metadata linking a corrected certificate PDF to its beneficiary and preparing administrator.';
$string['privacy:metadata:files'] = 'Prepared and confirmed PDF copies, containing learner information, are stored in the Moodle File API.';
$string['privacy:metadata:operation:contextid'] = 'Activity context.';
$string['privacy:metadata:operation:cmid'] = 'Course module identifier.';
$string['privacy:metadata:operation:issueid'] = 'Original issue identifier.';
$string['privacy:metadata:operation:userid'] = 'Certificate beneficiary.';
$string['privacy:metadata:operation:actorid'] = 'Administrator who prepared the copy.';
$string['privacy:metadata:operation:status'] = 'Preparation or confirmation state.';
$string['privacy:metadata:operation:requestkey'] = 'Hash used to prevent duplicate submissions.';
$string['privacy:metadata:operation:sourcehash'] = 'Fingerprint of rendering inputs.';
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
