@report @report_studentfeedback
Feature: Generate student feedback reports
  In order to write feedback for a class without retyping every name
  As a teacher
  I need the report to list the students enrolled in my course

  Background:
    Given the following "courses" exist:
      | fullname       | shortname | category |
      | English Camp   | EC1       | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Sarah     | Johnson  |
      | student1 | Ada       | Lovelace |
      | student2 | Zoe       | Muller   |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | EC1    | editingteacher |
      | student1 | EC1    | student        |
      | student2 | EC1    | student        |

  @javascript
  Scenario: A teacher sees the enrolled students listed
    Given I log in as "teacher1"
    And I am on "English Camp" course homepage
    When I navigate to "Reports" in current page administration
    And I click on "Generate feedback reports" "link"
    Then I should see "Ada Lovelace"
    And I should see "Zoe Muller"
    And I should see "Download Word reports"

  @javascript
  Scenario: The student count reflects what is ticked
    Given I log in as "teacher1"
    And I am on "English Camp" course homepage
    When I navigate to "Reports" in current page administration
    And I click on "Generate feedback reports" "link"
    Then I should see "2 student(s) selected"
    When I click on "Select none" "button"
    Then I should see "0 student(s) selected"

  # The important one. A student reaching this page could read the whole class
  # list, so this asserts the capability check actually holds.
  Scenario: A student cannot open the report
    Given I log in as "student1"
    When I am on the "EC1" "Course" page
    And I visit "/report/studentfeedback/index.php?id=" for course "EC1"
    Then I should see "Sorry, but you do not currently have permissions to do that"
