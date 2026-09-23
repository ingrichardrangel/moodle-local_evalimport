# Evaluation Instrument Importer

`local_evalimport` — **0.2.0-beta5**

Import Moodle rubrics from **CSV, XLS or XLSX** with a four-column format shared
with the Chrome rubric importer. Supports assignments and whole-forum grading.

This is a release candidate for validation, not a declaration that every target
version has already passed CI. See [VALIDATION.md](VALIDATION.md).

## Installation and update

1. Back up the existing plugin directory and test in a staging Moodle site.
2. Replace `/local/evalimport` with the `evalimport` directory in this package.
   On Moodle installations with a public web root, the plugin belongs in
   `public/local/evalimport` (the Moodle plugin directory under `$CFG->dirroot`).
3. Open **Site administration → Notifications** to register the new version.
4. Open a course as an editing teacher and select **Import evaluation instrument**.

No new database tables or data migration are required. Existing rubrics and
settings are retained. XLSX uses Moodle's bundled PhpSpreadsheet; XLS uses the bundled MIT-licensed
SimpleXLS reader with local validation and parsing bounds;
there is no plugin-specific Composer installation or external service.

## Teacher workflow

1. Configure a positive numeric maximum grade for the activity. In a forum,
   enable **whole forum grading**; post ratings are not the target.
2. Download a CSV, XLS or XLSX template from the importer. Templates total 20 points.
   XLS templates are prebuilt in English and Spanish; other languages use English.
3. Fill in the four columns, repeating the criterion on every level row.
4. Choose the target activity and upload the file.
5. Select **Preview rubric**. This does not modify the grading configuration.
6. Review the criteria, descriptions and scores, then **Confirm import**.
7. The plugin opens Moodle's rubric editor with a **draft**. Review it and use
   Moodle's native **Save rubric and make it ready** action before grading.

To change a rubric that already exists, use Moodle's editor. This importer does
not overwrite existing rubric definitions or silently switch from grading guides.
A cancelled/expired preview does not create a rubric. Previews expire after
30 minutes and are tied to the logged-in user's session and uploaded content.

## Four-column format

| criterion | level | level_description | score |
|---|---|---|---:|
| Argumentation | Excellent | Clear and supported arguments. | 10 |
| Argumentation | Competent | Some weaknesses in reasoning. | 7 |
| Argumentation | Developing | Arguments are not supported. | 0 |
| Organisation | Excellent | Ideas are organised coherently. | 10 |
| Organisation | Competent | Some organisation difficulties. | 7 |
| Organisation | Developing | No clear organisation. | 0 |

- Exact header names are required; header case/outer whitespace and column order
  are tolerated for legacy compatibility. Templates use the order shown above.
- Each non-empty row needs all four values. Completely blank rows are ignored.
- Criteria are grouped by their trimmed text and keep their first-appearance order.
- `level` is a human-readable label shown in the preview. It is not prefixed to
  the level description: Moodle's rubric model stores descriptions and scores.
- Levels are sorted by descending numeric score. Level labels do not imply scores.
- At least two levels per criterion; no duplicate scores within a criterion.
- The sum of criterion maxima must match the activity maximum, compared at two
  decimal places, retaining the previous plugin policy.
- Optional administrator minimum/maximum rules are preserved. The configured
  minimum is an **exact required level**, not a lower bound on all levels.
- Scores are non-negative, at most 99,999,999, with up to five decimal places.
  Decimal dot or comma is accepted; thousands separators/exponents are not.
- Text is treated as literal text, not imported HTML.

## File rules

**CSV:** UTF-8, optional BOM, comma or semicolon delimiter; quoted commas, double
quotes and multiline descriptions are supported. For CSV, reported row numbers
refer to records, which can span several physical text lines.

**Excel:** real `.xls` (BIFF) or `.xlsx` files. Read the sheet named `Rubrica`,
otherwise the first sheet. Header in row 1; four columns only; no merged cells,
formulas, spreadsheet errors or encrypted workbooks. Paste formula results as
values. Remove unused formatted rows/columns outside the import range.

Limits: 2 MB uploaded, 2,000 data rows, 10,000 characters per cell. XLSX archives
are limited to 1,000 entries and 20 MB uncompressed. Files remain in Moodle's
user draft area, subject to Moodle's normal draft cleanup; temporary parsing
copies are deleted after reading.

Compared with 0.1.1, malformed/partial rows and invalid numeric values now cause
explicit errors instead of being skipped or coerced. See [CHANGELOG.md](CHANGELOG.md).

## Permissions and persistence

Requires `local/evalimport:view` in the course and
`moodle/grade:managegradingforms` in the activity. Visibility, course membership
of the target and separate-group restrictions are rechecked on submission.
`moodle/site:accessallgroups` is respected.

The importer uses the native rubric controller, a delegated database transaction,
and a per-activity Moodle lock for concurrent **plugin** submissions. Moodle's
native editor does not share that lock; avoid editing the same activity from
another session during import.

## Target compatibility and automated testing

The CI matrix targets Moodle **4.1, 4.2, 4.3, 4.4, 4.5, 5.0, 5.1, 5.2 and 5.3 beta**
with compatible PHP versions. It retains 4.1 as the declared installation minimum.
5.3 beta is tracked on `main`; switch the two matrix entries to
`MOODLE_503_STABLE` when that branch exists, to avoid accidentally testing 5.4.

PHPUnit runs on every matrix entry. Behat runs on 4.5 and both 5.3 entries.
Both PostgreSQL and MariaDB are represented. The workflow runs on pushes,
pull requests, manual dispatch and weekly on the repository's default branch.
The CI tool manages the Moodle environment and its dependencies.

From a prepared Moodle Plugin CI environment:

```sh
moodle-plugin-ci phplint
moodle-plugin-ci phpcs --max-warnings 0
moodle-plugin-ci phpdoc --max-warnings 0
moodle-plugin-ci validate
moodle-plugin-ci savepoints
moodle-plugin-ci phpunit --fail-on-warning
moodle-plugin-ci behat --profile chrome
```

Tests and fixtures are under `tests/`. Keep them and `.github/workflows/ci.yml`
in GitHub. No compiled JavaScript or Grunt build is required by this plugin.

Early bird and Automated testing support are goals, not awarded badges in this
package. Publish compatibility only after the relevant CI and Moodle smoke
checks pass. Confirm the official 5.3 Early bird deadline before publication.

## GitHub delivery

Create a branch such as `feature/excel-import`. Copy the **contents** of
`evalimport/` into the existing repository root, including `.github` and `tests`;
do not nest a second `evalimport` directory. Commit and open a PR. Inspect every
required CI job before merging or publishing a release. This package has not
been pushed to GitHub automatically.

## Privacy and licence

The plugin introduces no personal-data database of its own. Uploads use Moodle's
draft file storage; rubric definitions, authorship and subsequent grading data
are handled by core grading. Short-lived draft references/hashes are kept in the
session for confirmation. Nothing is sent to external services.

Copyright 2026 Richard Rangel. GNU GPL v3 or later, matching Moodle.
