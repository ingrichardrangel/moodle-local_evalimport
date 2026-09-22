# Changelog

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
