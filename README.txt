Variable Numeric Sets question type

Question type for Moodle - for numeric questions with variable and expression
evaluation. All values are precalculated although a question can have several
variants with different variable values for each variant.

The question type was created by Jamie Pratt (http://jamiep.org/) for
the Open University (http://www.open.ac.uk/).

This question type is compatible with Moodle 2.1+.

To install using git for a 2.2+ Moodle installation, type this command in the root of your Moodle
install :

git clone git://github.com/moodleou/moodle-qtype_varnumericset.git question/type/varnumericset

To install using git for a 2.1+ Moodle installation, type this command in the root of your Moodle
install :

git clone -b MOODLE_21_STABLE git://github.com/moodleou/moodle-qtype_varnumericset.git question/type/varnumericset

Then add question/type/varnumericset to your git ignore.

Alternatively, download the zip from
    https://github.com/moodleou/moodle-qtype_varnumericset/zipball/master
unzip it into the question/type folder, and then rename the new folder to varnumericset.

You may want to install Tim's stripped down tinymce editor that only allows the use of
superscript and subscript see (https://github.com/moodleou/moodle-editor_supsub). To install this
editor using git, type this command in the root of your Moodle install :

    git clone git://github.com/moodleou/moodle-editor_supsub.git lib/editor/supsub

Then add lib/editor/supsub to your git ignore.

If the editor is not installed the question type can still be used but if it is installed when
you make a question that requires scientific notation then this editor will be shown and a
student can either enter an answer with the notation 1x10^5 where the ^5 is expressed with super
script.
