Variable Numeric Question Type
------------------------------

The question type was created by Jamie Pratt (http://jamiep.org/) for
the Open University (http://www.open.ac.uk/).

This question type is compatible with Moodle 2.1+.

This question type requires the varnumericset question type to be installed. See:

    https://github.com/moodleou/moodle-qtype_varnumericset/

To install using git for a 2.2+ Moodle installation, type this command in the root of your Moodle
install :

    git clone git://github.com/moodleou/moodle-qtype_varnumeric.git question/type/varnumeric

To install using git for a 2.1+ Moodle installation, type this command in the root of your Moodle
install :

    git clone -b MOODLE_21_STABLE git://github.com/moodleou/moodle-qtype_varnumeric.git question/type/varnumeric

Then add question/type/varnumeric to your git ignore.

Alternatively, download the zip from
    Moodle 2.1+ - https://github.com/moodleou/moodle-qtype_varnumeric/zipball/MOODLE_21_STABLE
    Moodle 2.2+ - https://github.com/moodleou/moodle-qtype_varnumeric/zipball/master
unzip it into the question/type folder, and then rename the new folder to varnumeric.

You may want to install Tim's stripped down tinymce editor that only allows the
use of superscript and subscript see
https://github.com/moodleou/moodle-editor_supsub. To install this editor using
git, type this command in the root of your Moodle install:

    git clone git://github.com/moodleou/moodle-editor_supsub.git lib/editor/supsub

Then add lib/editor/supsub to your git ignore.

If the editor is not installed the question type can still be used but if it is
installed when  you make a question that requires scientific notation then this
editor will be shown and a student can either enter an answer with the notation
1x10^5 where the ^5 is expressed with super script.
