@ou @ou_vle @qtype @qtype_varnumericset
Feature: Test all the basic functionality of varnumericset question type
  In order evaluate students calculating ability
  As an teacher
  I need to create and preview variable numeric questions.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teacher   |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  @javascript
  Scenario: Create, edit then preview a variable numeric sets question.
    # Create a new question.
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

    # Preview it.
    When I choose "Preview" action for "My first variable numeric set question" in the question bank
    And I switch to "questionpreview" window
    And I set the following fields to these values:
      | How questions behave | Interactive with multiple tries |
      | Marked out of        | 3                               |
      | Question variant     | 1                               |
      | Marks                | Show mark and max               |
    And I press "Start again with these options"
    Then I should see "What is 2 + 8?"
    And the state of "What is 2 + 8?" question is shown as "Tries remaining: 3"
    When I set the field "Answer:" to "2"
    And I press "Check"
    Then I should see "Sorry, no."
    And I should see "Please try again."
    When I press "Try again"
    Then the state of "What is 2 + 8?" question is shown as "Tries remaining: 2"
    When I set the field "Answer:" to "10"
    And I press "Check"
    Then I should see "Well done!"
    And the state of "What is 2 + 8?" question is shown as "Correct"
    And I should see "Mark 2.00 out of 3.00"
    And I switch to the main window

    # Backup the course and restore it.
    When I log out
    And I log in as "admin"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    When I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    Then I should see "Course 2"
    When I navigate to "Question bank" in current page administration
    Then I should see "My first variable numeric set question"

    # Edit the copy and verify the form field contents.
    When I choose "Edit question" action for "My first variable numeric set question" in the question bank
    Then the following fields match these values:
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
    And I set the following fields to these values:
      | Question name | Edited question name |
    And I press "id_submitbutton"
    Then I should see "Edited question name"
