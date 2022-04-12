@ou @ou_vle @qtype @qtype_varnumericset @javascript
Feature: Test creating a variable numeric (varnumeric) question type
  In order evaluate students calculating ability
  As an teacher
  I need to create a variable numeric questions.

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

  Scenario: Create, edit then preview a variable numeric sets question.
    When I am on the "Course 1" "core_question > course question bank" page logged in as "teacher"
    And I add a "Variable numeric set" question filling the form with:
      | Question name | My first variable numeric set question |
      | Question text | What is [[a]] + [[b]]?                 |
      | id_vartype_0  | Predefined variable                    |
      | Variable 1    | a                                      |
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
    Then I should see "My first variable numeric set question"
