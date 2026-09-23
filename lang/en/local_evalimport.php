<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language strings for local_evalimport.
 *
 * @package    local_evalimport
 * @category   string
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activitynotvisible'] = 'You do not have access to this activity.';
$string['choosecsvfile'] = 'Rubric CSV file';
$string['choosefile'] = 'Rubric file (CSV, XLS or XLSX)';
$string['column_criterion'] = 'Criterion';
$string['column_level'] = 'Level';
$string['column_level_description'] = 'Description';
$string['column_score'] = 'Score';
$string['confirmimport'] = 'Confirm import';
$string['continueimporting'] = 'Continue importing';
$string['csvmissingcolumns'] = 'Use exactly these four unique headers: criterion, level, level_description, score.';
$string['csvrequired'] = 'You must upload a CSV file.';
$string['downloadtemplate'] = 'Download {$a} template';
$string['emptycell'] = 'A required value is missing.';
$string['emptyrubric'] = 'The file must contain a header and at least one criterion.';
$string['enablemaxlevelscore'] = 'Enable maximum score validation per level';
$string['enablemaxlevelscore_desc'] = 'If enabled, no level score can exceed the configured maximum value.';
$string['enableminlevelscore'] = 'Enable minimum score validation per criterion';
$string['enableminlevelscore_desc'] = 'If enabled, each criterion must include the configured minimum score.';
$string['errormaxexceeded'] = 'In the criterion "{$a->criterion}" the score {$a->score} exceeds the maximum allowed ({$a->max}).';
$string['errorminmissing'] = 'In the criterion "{$a->criterion}" the required minimum score ({$a->min}) is missing.';
$string['errormismatchtotal'] = 'The sum of the rubric maximum scores ({$a->sum}) does not match the activity maximum grade ({$a->grademax}).';
$string['errorrepeatedscores'] = 'There are repeated scores within the criterion "{$a}".';
$string['errorrubricexists'] = 'This activity already has a rubric defined.';
$string['evalimport:view'] = 'View evaluation instrument importer';
$string['filehelp'] = 'Use criterion, level, level_description and score as the four column headers. Repeat the criterion on every level row. Excel: use the Rubrica sheet, or the first sheet if Rubrica is absent. Limits: 2 MB and 2,000 data rows. Templates total 20 points; adapt them to the activity maximum.';
$string['filerequired'] = 'Upload exactly one rubric file.';
$string['formulanotallowed'] = 'Cell {$a} contains a formula or spreadsheet error. Replace it with a plain value.';
$string['fourcolumns'] = 'Each non-empty row must contain exactly four cells.';
$string['gotoadvancedgrading'] = 'Go to advanced grading';
$string['importbusy'] = 'Another import is running for this activity. Try again shortly.';
$string['importedrubricname'] = 'Imported rubric {$a}';
$string['importfailed'] = 'The import could not be completed. Contact the site administrator if the problem persists.';
$string['importinstrument'] = 'Import evaluation instrument';
$string['importrubric'] = 'Import';
$string['importsuccess'] = 'The rubric was imported as a draft. Review it and save it as ready for use in the Moodle editor.';
$string['invalidcell'] = 'Cell {$a} contains a date or boolean instead of the required value.';
$string['invalidcsvencoding'] = 'Save the CSV in UTF-8 and keep it below 2 MB.';
$string['invalidfilesize'] = 'The file is empty or exceeds the import limits (2 MB uploaded; 20 MB uncompressed for XLSX).';
$string['invalidfiletype'] = 'Use a CSV, XLS or XLSX file with its original extension.';
$string['invalidgrading'] = 'The activity must have a positive numeric maximum grade. For forums, enable whole forum grading.';
$string['invalidscore'] = 'Use a non-negative number with at most five decimal places and no thousands separators.';
$string['invalidtext'] = 'Text contains an invalid character or exceeds 10,000 characters.';
$string['invalidworkbook'] = 'The workbook could not be read. Check its format and remove password encryption.';
$string['maxlevelscore'] = 'Maximum score per level';
$string['maxlevelscore_desc'] = 'Maximum allowed value for an individual level.';
$string['mergedcells'] = 'Use a worksheet without merged cells.';
$string['minlevelscore'] = 'Minimum score per criterion';
$string['minlevelscore_desc'] = 'Minimum value that must exist within each criterion.';
$string['noactivitiesavailable'] = 'No compatible activities available for importing evaluation instruments.';
$string['nogradingarea'] = 'No compatible advanced grading area was found for this activity.';
$string['notenoughlevels'] = 'Criterion "{$a}" must contain at least two levels.';
$string['othergradingmethod'] = 'Another advanced grading method is active. Review it in Moodle before switching to a rubric.';
$string['pageheading'] = 'Import evaluation instrument';
$string['pluginname'] = 'Evaluation instrument importer';
$string['previewexpired'] = 'This preview has expired or its file has changed. Upload the file and preview it again.';
$string['previewimport'] = 'Preview rubric';
$string['previewnotice'] = 'No grading changes have been saved yet. Confirm to create a draft rubric. Level names are shown for reference; Moodle stores the level descriptions and scores. Levels are displayed in descending score order.';
$string['privacy:metadata'] = 'The plugin has no personal-data store of its own. Uploaded files use Moodle draft file storage; rubric definitions and their authors are managed by the core grading subsystem. Temporary confirmation references are kept in the user session.';
$string['rowerror'] = 'Row {$a->row}, column {$a->column}: {$a->reason}';
$string['sample_argument'] = 'Argumentation';
$string['sample_argument_competent'] = 'Presents arguments with some weaknesses.';
$string['sample_argument_developing'] = 'Does not support the arguments.';
$string['sample_argument_excellent'] = 'Presents clear and well-supported arguments.';
$string['sample_competent'] = 'Competent';
$string['sample_developing'] = 'Developing';
$string['sample_excellent'] = 'Excellent';
$string['sample_structure'] = 'Organisation';
$string['sample_structure_competent'] = 'Organises ideas with some difficulties.';
$string['sample_structure_developing'] = 'Does not present a clear organisation.';
$string['sample_structure_excellent'] = 'Organises ideas clearly and coherently.';
$string['selectactivity'] = 'Evaluation activity';
$string['toomanyrows'] = 'The file exceeds 2,000 data rows.';
$string['workbookdimensions'] = 'Use at most 2,000 data rows and four columns. Remove unused formatted rows or columns.';
