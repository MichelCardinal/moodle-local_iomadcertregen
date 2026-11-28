# RC4 release candidate

RC4 extends corrections to site-installed certificate templates and fixes the first native inventory export on the verified historical distribution. After changing an activity's template or date settings, an authorised operator can prepare, inspect, confirm and explicitly publish the corrected PDF for an already issued certificate.

## Upgrade and operation

- Existing native saved PDF evidence remains mandatory. The issue ID, code and recorded issue date are retained.
- Review each installed custom layout with fictitious data. Trusted server PHP must follow the native rendering contract and use the settings it claims to implement. Arbitrary PHP, external side effects and hard-coded settings are not guaranteed.
- Pending older corrections require a fresh preparation. Previously published PDFs and retained administrative archives survive the metadata-only promotion.
- Publication, archive choice and individual email remain separate reviewed actions; no automatic email or new role grant is introduced.
- Install the attached ZIP through Moodle's normal installer after a complete backup and UAT. Do not use GitHub's automatic source archive as the installation package.

## Validation boundaries

The operational development commit `5e13e0ea687cc2a43f1b2d890358de4b70fc1b3d` completed 183 tests in all 16 pinned profiles. Thirteen passed; three strict PHP8.4 profiles failed on the native IOMAD Certificate test-generator deprecation. Four PostgreSQL/PHP8.3 HTTP runs each passed 13 scenarios /163 steps. Local historical HTTP acceptance and five strict local suites also passed. A private remote IOMAD4.5.5 fictitious UAT verified both custom-template and issue-date corrections, publication and exact archive/native file bytes.

RC4 changes version/maturity, a native savepoint and documentation relative to that tested runtime. Candidate-specific checks and source identity are recorded in TESTING.md and the release manifest. A failed PHP8.4 matrix is not a claim of complete qualification. The upstream generator is fixed in newer IOMAD revisions, which also need separate runtime qualification.

The recommended verified modern baseline is PHP8.3.37 with the exact pinned distributions. Branch labels do not guarantee every patch or site modification. No production-host security certification, exhaustive accessibility certification, mobile-app integration or SMTP inbox-delivery guarantee is claimed.

## Marketplace review

The plugin remains under review. The configuration API issue raised by Moodle is fixed and tested. Eligibility of mandatory `local_iomad` and `mod_iomadcertificate` dependencies remains an open reviewer decision. They are supplied by the official IOMAD distribution; plain Moodle without them is unsupported. No Marketplace approval or publication date is promised.

See README.md for installation and permissions, TESTING.md for exact evidence, docs/guide-fr.md for the French operator guide, and SECURITY.md for private vulnerability reporting.
