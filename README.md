# Evaluation Instrument Importer

`local_evalimport` lets teachers create Moodle rubrics from CSV, XLS or XLSX
files using a simple four-column format. It supports graded assignments and
whole-forum grading.

## Requirements

- Moodle 4.1 or later.
- Permission to view and use the importer in the course and permission to manage
  grading forms for the target activity.

## Installation

1. Copy the `evalimport` folder into Moodle's `local` directory.
2. Sign in as an administrator and open **Site administration → Notifications**
   to complete installation or upgrade.
3. Open a course and choose **Import evaluation instrument**.

When updating by file copy, replace the previous plugin directory with the
new package before running Moodle's upgrade. This release includes the English
language pack and English templates. Moodle creates a table recording the
scoring rules used for each new import. Existing rubrics and their grading
settings are retained.

## Import a rubric

1. Set a positive maximum grade for the activity. For a forum, enable whole
   forum grading.
2. Download a CSV, XLS or XLSX template from the importer.
3. Complete the four columns described below. Repeat the criterion name on each
   row that defines one of its levels.
4. Select the activity, enter or accept the suggested rubric name, and upload
   the completed file. If visible, adjust the scoring rules and select **Load and review**.
5. Review the rubric name, scoring rules, criteria, descriptions, scores and
   total rubric points, then confirm the import.
6. In Moodle's rubric editor, review the draft and select **Save rubric and
   make it ready** when it is ready to use.

The importer creates a draft for review. It does not replace an existing rubric
or change an activity from another grading method to a rubric. Edit existing
rubrics in Moodle's grading editor. A preview expires after 30 minutes and is
associated with the current user session.

## Spreadsheet format

Use these four columns, with the exact header names shown:

| criterion | level | level_description | score |
|---|---|---|---:|
| Argumentation | Excellent | Clear and supported arguments. | 10 |
| Argumentation | Competent | Some weaknesses in reasoning. | 7 |
| Argumentation | Developing | Arguments are not supported. | 0 |
| Organisation | Excellent | Ideas are organised coherently. | 10 |
| Organisation | Competent | Some organisation difficulties. | 7 |
| Organisation | Developing | No clear organisation. | 0 |

Column order may vary, and header case or surrounding spaces are ignored.
Completely blank rows are skipped. Every other row must contain all four
values. Criterion names are grouped by their text and appear in the order they
first occur. The importer shows level labels in the review step. Moodle stores
each level's description and score.

Each criterion must have at least two levels. Levels are arranged from highest
score to lowest score. Scores must be non-negative and at most 99,999.99999,
with up to five decimal places. Use a decimal point or comma. Thousands
separators and scientific notation are unsupported. Text is imported as plain text.

## Scoring rules and permissions

Under **Site administration → Plugins → Local plugins → Evaluation instrument
importer**, set the defaults for **Enable score validation** and its three rules.
All four are enabled initially.

- Match the sum of the criterion maximums to the activity maximum grade.
- Require unique scores within each criterion.
- Require a zero-point level in every criterion.

Turning off **Enable score validation** skips these three rules. File and
structure checks still apply, including a positive total and a score range that
Moodle can use. An explicitly configured global cap per level or required
non-zero score per criterion also stays in force. Use the dedicated zero-point
rule to control whether each criterion requires a score of zero.

Site administrators can change the rules for any individual import. To delegate
this ability, enable **Allow changes to scoring rules per import** and grant
**Change scoring rules when importing** in Moodle's **Define roles** permissions.
The user also needs access to the course importer and permission to manage
grading forms in the target activity. The standard student role has neither.
Users who can import but cannot change the rules use the saved site defaults.

For a 140-point rubric in a five-point activity, turn off the matching rule
for the import or in the site defaults. The other rules can remain enabled.
Moodle scales the rubric result to the activity maximum when a grade is calculated.

In separate-groups mode, users without Moodle's **Access all groups** permission
must be in a group in the activity's grouping, or in a course group if the
activity has no grouping. An imported rubric belongs to the whole activity.

## Supported files and limits

**CSV:** UTF-8 text, with an optional byte-order mark. Comma and semicolon
separators are supported. Quoted fields, commas, quotation marks and
multi-line descriptions are supported.

**XLS and XLSX:** Use a regular, unencrypted Excel workbook. The importer reads
the sheet named `Rubrica`, or the first sheet if that name is not present. Put
the headers in the first row and use four columns. Merged cells, formulas and
spreadsheet error values are not supported; paste formula results as values.

The maximum upload size is 2 MiB, with up to 2,000 data rows.
Rubric names allow up to 255 characters. Each criterion name, level label and
level description allows up to 10,000 UTF-8 bytes. XLSX files may contain up to
1,000 archive entries and 20 MiB of uncompressed content. Remove unused formatted rows and columns outside
the rubric data.

## Privacy and licensing

Uploaded files are processed through Moodle's draft file storage and normal
file-cleanup process. Rubric data is stored by Moodle's grading system. The
plugin does not send data to external services.

This plugin is distributed under the GNU General Public License, version 3 or
later. The bundled SimpleXLS library has its own MIT license, listed in
`thirdpartylibs.xml`.
