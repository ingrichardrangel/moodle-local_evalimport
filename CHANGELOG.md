# Changelog

## 0.2.0-beta5 — 2026-09-23

- Make the separate-groups PHPUnit scenario independent from Moodle's
  `editingteacher` archetype. The test now uses a dedicated role containing
  only course view, assignment view and rubric-management capabilities.
- Assert the effective group mode and absence of `accessallgroups` before
  testing activity discovery, so the fixture itself is verified explicitly.
- The CI run 96935171680 passed Coding standards and 29 of 30 PHPUnit tests in
  all ten jobs. The group-role fixture was the only failure.

## 0.2.0-beta4 — 2026-09-23

- Correct the remaining PHPUnit failure in all ten CI jobs by making the group
  test explicitly configure separate groups and prohibit `accessallgroups`
  while testing membership restrictions. Moodle capability caches are reset
  after each permission change.
- Preserve PHPUnit 11 coverage attributes while placing the class docblocks
  where Moodle CodeSniffer can associate them with all test methods.
- Fix the multiline exception constructor reported by CodeSniffer.
- Document the two inherited SimpleXLS method names that must retain upstream
  camel case and use Moodle-compliant names for their local parameters.
- Update checkout and setup-node to their Node 24 based v5 actions, disabling
  unused automatic package-manager caching, to remove runner deprecation warnings.
- The supplied CI run completed 30 tests in every environment; 29 passed and
  only the group setup assertion failed. A new run is required for confirmation.

## 0.2.0-beta3 — 2026-09-22

- Fix XLS support: Moodle removes the XLS/OLE components from its bundled
  PhpSpreadsheet. Include an isolated SimpleXLS reader, its MIT licence and
  third-party declaration; reject formulas, errors, booleans, merged cells,
  excessive dimensions and cyclic OLE chains. Preserve raw numeric values.
- Load Moodle excellib explicitly for XLSX imports and templates on 4.1–4.4.
- Ship genuine English/Spanish XLS templates without relying on the missing writer.
- Fix the native-draft test to obtain area ID from the grading controller.
- Fix the groups test to enrol an editing teacher, explicitly deny/allow
  accessallgroups, and refresh module visibility after membership changes.
- Add coverage attributes for PHPUnit 11 while retaining PHP 7.4/PHPUnit 9 support.
- Preserve technical workbook exceptions as debug information, keeping the
  translated user-facing message. Add XLS regression cases and template checks.
- Full Moodle PHPUnit and Behat confirmation remains pending in GitHub Actions.

## 0.2.0-beta2 — 2026-09-22

- Fix all coding-standard violations reported in CI run 96835402304:
  anonymous-function spacing, multiline conditions and a multiline link call.
- Remove redundant MOODLE_INTERNAL guards only from autoloaded service classes
  and PHPUnit test files, as required by the current Moodle coding standard.
  Guards on helper libraries and files with top-level includes remain in place.
- Run independent quality checks and tests after successful environment setup,
  even when another check fails. All checks remain blocking; no warnings are ignored.
- No changes to the file formats, grading rules or rubric persistence behaviour.
- Local PHP 7.4/8.4 CSV validation checks still pass. Full PHPUnit/Behat results
  require a new CI run: the supplied run stopped at coding standards in every job.

## 0.2.0-beta1 — 2026-09-22

### Added

- XLS and XLSX import using Moodle's bundled PhpSpreadsheet, alongside CSV.
- Shared four-column validation and a two-step preview/confirmation workflow.
- Downloadable CSV, XLS and XLSX templates from the course importer.
- Spanish translation and teacher-facing file validation messages.
- PHPUnit regression/integration tests, real binary Excel fixtures and Behat scenarios.
- CI targets Moodle 4.1–5.3 beta, including PHP 8.3/8.4 for 5.3 and scheduled runs.

### Fixed

- Do not activate rubric grading while merely resolving a target or previewing.
- Save through the native rubric controller within a transaction, as an editable draft.
- Serialise concurrent plugin imports and recheck existing rubrics before writing.
- Target whole-forum grading (item 1, area `forum`) instead of post ratings.
- Respect accessallgroups; enforce separate-group restrictions consistently on submission.
- Parse quoted CSV records rather than splitting descriptions at every newline.
- Reject malformed rows, duplicate headers, missing values and non-numeric scores.
- Validate file limits and reject Excel formulas, errors, merged cells and date scores.
- Escape imported text and preview output; avoid displaying raw internal exceptions.
- Fetch grade items in one course query for activity selection.

### Behaviour and release status

- Retains the legacy total-score and optional administrator score policies.
- Existing rubrics and active alternative grading methods are not overwritten.
- `level` labels are retained in the preview, not concatenated into descriptions.
- Levels display in descending score order; criteria retain first appearance.
- Minimum install version remains Moodle 4.1. No new database schema.
- This beta requires CI/integration validation before a stable release or
  compatibility/award claim. See VALIDATION.md.

## 0.1.1

Baseline provided for this update: course-level CSV import for assignments and forums.
