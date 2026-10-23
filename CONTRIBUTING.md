# Contributing

Report ordinary defects through the repository issue tracker. Use the private route in SECURITY.md for suspected vulnerabilities. Do not upload learner certificates, names, email addresses, credentials, database exports or private server logs. A fictitious example and the exact component versions are sufficient to start a report.

## Development environment

Use an isolated IOMAD laboratory. The component directory is `local/iomadcertregen`. Install IOMAD and its bundled `mod_iomadcertificate` and `local_iomad_track` dependencies first. The plugin ZIP does not require Composer or any build step. Composer is needed only to prepare Moodle's development tools and PHPUnit dependencies.

Configure Moodle's PHPUnit prefix and dataroot separately from the ordinary site, then use the native commands:

```sh
php admin/tool/phpunit/cli/init.php --disable-composer
vendor/bin/phpunit --configuration phpunit.xml --testsuite local_iomadcertregen_testsuite
```

Initialisation resets test tables and test files. Never use ordinary site data for these settings. Tests create fictitious accounts and certificates through native Moodle/IOMAD fixtures.

Use Moodle coding, security, accessibility and Privacy APIs. Keep English strings in `lang/en/`; community translations belong in AMOS after approval. Tests support the PHPUnit versions supplied by the supported Moodle branches; do not suppress warnings to pass CI. Preserve native issue identifiers, issue dates, codes, PDF files, grades, completion and enrolments. Do not modify Moodle, IOMAD Certificate or a theme to make this plugin work.

The qualified renderer deliberately rejects unrecognised source fingerprints or templates. A compatibility change requires source review and meaningful generation, integrity and access tests. A version number alone does not qualify an interface.

## Changes and releases

Explain the user-visible trigger and result, and include relevant validation. Add tests for behaviour and risks rather than implementation details. Database changes need increasing versions, native migrations and savepoints. Keep legitimate third-party attribution and the GPL licence. Do not copy a real certificate or private laboratory configuration into a patch.

Before release, validate the actual archive, dependencies, source identity, upgrade path and compatibility claims. Release tags and ZIP files already distributed must remain immutable. No contribution or automated check implies Moodle certification or Marketplace approval.
