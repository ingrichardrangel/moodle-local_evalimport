@local @local_evalimport @javascript
Feature: Import a rubric using a reviewed file
  In order to prepare advanced grading consistently
  As an editing teacher
  I need to preview and import CSV and Excel rubrics

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | One | teacher@example.com |
      | student1 | Student | One | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course one | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | grade |
      | assign | Essay | Write an essay | C1 | essay | 20 |

  Scenario Outline: Import equivalent file formats into the native editor
    Given I log in as "teacher1"
    And I open the rubric importer for course "C1"
    When I upload "local/evalimport/tests/fixtures/rubric.<extension>" file to "Rubric file (CSV, XLS or XLSX)" filepicker
    And I press "Preview rubric"
    Then I should see "No grading changes have been saved yet"
    And I should see "Clear and well-supported arguments."
    When I press "Confirm import"
    Then I should see "The rubric was imported as a draft"
    And I should see "Clear and well-supported arguments."
    Examples:
      | extension |
      | csv |
      | xls |
      | xlsx |

  Scenario: Cancel a preview without creating a rubric
    Given I log in as "teacher1"
    And I open the rubric importer for course "C1"
    When I upload "local/evalimport/tests/fixtures/rubric.csv" file to "Rubric file (CSV, XLS or XLSX)" filepicker
    And I press "Preview rubric"
    And I press "Cancel"
    Then I should see "Rubric file (CSV, XLS or XLSX)"
    When I upload "local/evalimport/tests/fixtures/rubric.csv" file to "Rubric file (CSV, XLS or XLSX)" filepicker
    And I press "Preview rubric"
    Then I should see "No grading changes have been saved yet"

  Scenario: Reject formulas before confirmation
    Given I log in as "teacher1"
    And I open the rubric importer for course "C1"
    When I upload "local/evalimport/tests/fixtures/formula.xlsx" file to "Rubric file (CSV, XLS or XLSX)" filepicker
    And I press "Preview rubric"
    Then I should see "Cell D2 contains a formula"
    And I should not see "Confirm import"

  Scenario: Reject direct student access
    Given I log in as "student1"
    When I open the rubric importer for course "C1"
    Then I should see "Sorry, but you do not currently have permissions to do that"
