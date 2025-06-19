# Change log for the Variable numeric sets question type

## Changes in 2.2

* This version is compatible with Moodle 5.0.
* Improved display of scientific notation.
* Added more validations for the question editing form, which includes:
    Better handling of numbers formatting.
    Checks for empty variables to prevent save errors.
    Added new validation for answer format verification.
* Fix Behat and PHPUnit tests.
* Defined excluded hash fields and implemented conversion of legacy backup data
  to align with new question data format (per MDL-83541).


## Changes in 2.1

* This version works with Moodle 4.0.
* Fixed a bug with how penaties were applied to marks.
* Automated test failures are fixed.
* Switch from Travis to Github actions.


## Changes in 2.0

* Improve styling of answer boxes when reviewing.


## Changes in 1.9

* Support for Moodle mobile app for questions that don't use the superscripts/subscript editor.
* Better grading when the allowed error is very small.
* Update Behat tests to pass with Moodle 3.8.


## Changes in 1.8

* Fix automated tests for Moodle 3.6.


## Changes in 1.7

* Privacy API implementation.
* Update to use the newer editor_ousupsub, instead of editor_supsub.
* Setup Travis-CI automated testing integration.
* Fix some automated tests to pass with newer versions of Moodle.
* Fix some coding style.
* Due to privacy API support, this version now only works in Moodle 3.4+
  For older Moodles, you will need to use a previous version of this plugin.


## 1.6 and before

Changes were not documented here.
