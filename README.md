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

The plugin does not create additional database tables. Existing rubrics and
grading settings are retained.

## Import a rubric

1. Set a positive maximum grade for the activity. For a forum, enable whole
   forum grading.
2. Download a CSV, XLS or XLSX template from the importer.
3. Complete the four columns described below. Repeat the criterion name on each
   row that defines one of its levels.
4. Select the activity, upload the completed file and preview the rubric.
5. Review the criteria, descriptions and scores, then confirm the import.
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
first occur. Level labels are shown to teachers and students; the description
contains the level's explanatory text.

Each criterion must have at least two levels, with no repeated score. Levels
are arranged from highest score to lowest score. The sum of the highest scores
for all criteria must equal the activity's maximum grade. Scores must be
non-negative, can have up to five decimal places and can use a decimal point or
comma. Thousands separators and scientific notation are not supported. Text is
imported as plain text.

## Supported files and limits

**CSV:** UTF-8 text, with an optional byte-order mark. Comma and semicolon
separators are supported. Quoted fields, commas, quotation marks and
multi-line descriptions are supported.

**XLS and XLSX:** Use a regular, unencrypted Excel workbook. The importer reads
the sheet named `Rubrica`, or the first sheet if that name is not present. Put
the headers in the first row and use four columns. Merged cells, formulas and
spreadsheet error values are not supported; paste formula results as values.

The maximum upload size is 2 MB, with up to 2,000 data rows and 10,000
characters per cell. XLSX files may contain up to 1,000 archive entries and
20 MB of uncompressed content. Remove unused formatted rows and columns outside
the rubric data.

## Privacy and licensing

Uploaded files are processed through Moodle's draft file storage and normal
file-cleanup process. Rubric data is stored by Moodle's grading system. The
plugin does not send data to external services.

This plugin is distributed under the GNU General Public License, version 3 or
later. The bundled SimpleXLS library has its own MIT license, listed in
`thirdpartylibs.xml`.
