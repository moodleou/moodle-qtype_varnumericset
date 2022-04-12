@ou @ou_vle @qtype @qtype_varnumericset @javascript
Feature: Test editing a Variable numeric set (varnumericset) question type
  In order to be able to update my Variable numeric set questions
  As an teacher
  I need to edit them.

  Background:
    Given the following "users" exist:
      | username | firstname |
      | teacher  | Teacher   |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype         | name                                 | template       |
      | Test questions   | varnumericset | Variable numeric set 001 for editing | with_variables |

  Scenario: Edit a Variable numeric set question
    When I am on the "Variable numeric set 001 for editing" "core_question > edit" page logged in as teacher
    Then the following fields match these values:
      | Question name | Variable numeric set 001 for editing |
      | Question text | What is [[a]] + [[b]]?               |
      | id_vartype_0  | Predefined variable                  |
      | Variable 1    | a                                    |
      | id_variant0_0 | 2                                      |
      | id_variant1_0 | 3                                      |
      | id_variant2_0 | 5                                      |
      | id_vartype_1  | Predefined variable                    |
      | Variable 2    | b                                      |
      | id_variant0_1 | 8                                      |
      | id_variant1_1 | 5                                      |
      | id_variant2_1 | 3                                      |
      | Variable 3    | c = a + b                              |
      | id_answer_0   | c                                      |
      | id_fraction_0 | 100%                                   |
      | id_feedback_0 | Well done!                             |
      | id_answer_1   | *                                      |
      | id_feedback_1 | Sorry, no.                             |
      | Hint 1        | Please try again.                      |
      | Hint 2        | You may use a calculator if necessary. |
    And I set the following fields to these values:
      | Question name | |
    And I press "id_submitbutton"
    And I should see "You must supply a value here."
    And I set the following fields to these values:
      | Question name | Variable numeric set 001 edited |
    And I press "id_submitbutton"
    Then I should see "Variable numeric set 001 edited"
