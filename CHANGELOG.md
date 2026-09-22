# Changelog

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
