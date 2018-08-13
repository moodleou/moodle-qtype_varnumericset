Variable Numeric Sets question type
https://moodle.org/plugins/qtype_varnumericset

Question type for Moodle - for numeric questions with variable and expression
evaluation. All values are precalculated although a question can have several
variants with different variable values for each variant.

The question type was created by Jamie Pratt (http://jamiep.org/) for
the Open University (http://www.open.ac.uk/).

This version of this question type is compatible with Moodle 3.4+. There are
other versions available for Moodle 2.3+.

Either install from the Moodle plugins database, using the link above, or to
install using git, type this command in the root of your Moodle install:

    git clone git://github.com/moodleou/moodle-qtype_varnumericset.git question/type/varnumericset
    echo /question/type/varnumericset/ >> .git/info/exclude

You probably also want to install the optional superscript/subscript editor, either from
https://moodle.org/plugins/editor_ousupsub, or using git:

    git clone git://github.com/moodleou/moodle-editor_ousupsub.git lib/editor/ousupsub
    echo /lib/editor/ousupsub/ >> .git/info/exclude

If the editor is not installed the question type can still be used but if it is
installed when you make a question that requires scientific notation then this
editor will be shown and a student can either enter an answer with the notation
1x10^5 where the ^5 is expressed with super script.
