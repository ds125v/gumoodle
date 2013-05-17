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
 * Unit tests for /lib/formslib.php.
 *
 * @package   core_form
 * @category  phpunit
 * @copyright 2011 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/form/radio.php');
require_once($CFG->libdir . '/form/select.php');
require_once($CFG->libdir . '/form/text.php');


class formslib_testcase extends basic_testcase {

    public function test_require_rule() {
        global $CFG;

        $strictformsrequired = null;
        if (isset($CFG->strictformsrequired)) {
            $strictformsrequired = $CFG->strictformsrequired;
        }

        $rule = new MoodleQuickForm_Rule_Required();

        // First run the tests with strictformsrequired off
        $CFG->strictformsrequired = false;
        // Passes
        $this->assertTrue($rule->validate('Something'));
        $this->assertTrue($rule->validate("Something\nmore"));
        $this->assertTrue($rule->validate("\nmore"));
        $this->assertTrue($rule->validate(" more "));
        $this->assertTrue($rule->validate("0"));
        $this->assertTrue($rule->validate(0));
        $this->assertTrue($rule->validate(true));
        $this->assertTrue($rule->validate(' '));
        $this->assertTrue($rule->validate('      '));
        $this->assertTrue($rule->validate("\t"));
        $this->assertTrue($rule->validate("\n"));
        $this->assertTrue($rule->validate("\r"));
        $this->assertTrue($rule->validate("\r\n"));
        $this->assertTrue($rule->validate(" \t  \n  \r "));
        $this->assertTrue($rule->validate('<p></p>'));
        $this->assertTrue($rule->validate('<p> </p>'));
        $this->assertTrue($rule->validate('<p>x</p>'));
        $this->assertTrue($rule->validate('<img src="smile.jpg" alt="smile" />'));
        $this->assertTrue($rule->validate('<img src="smile.jpg" alt="smile"/>'));
        $this->assertTrue($rule->validate('<img src="smile.jpg" alt="smile"></img>'));
        $this->assertTrue($rule->validate('<hr />'));
        $this->assertTrue($rule->validate('<hr/>'));
        $this->assertTrue($rule->validate('<hr>'));
        $this->assertTrue($rule->validate('<hr></hr>'));
        $this->assertTrue($rule->validate('<br />'));
        $this->assertTrue($rule->validate('<br/>'));
        $this->assertTrue($rule->validate('<br>'));
        $this->assertTrue($rule->validate('&nbsp;'));
        // Fails
        $this->assertFalse($rule->validate(''));
        $this->assertFalse($rule->validate(false));
        $this->assertFalse($rule->validate(null));

        // Now run the same tests with it on to make sure things work as expected
        $CFG->strictformsrequired = true;
        // Passes
        $this->assertTrue($rule->validate('Something'));
        $this->assertTrue($rule->validate("Something\nmore"));
        $this->assertTrue($rule->validate("\nmore"));
        $this->assertTrue($rule->validate(" more "));
        $this->assertTrue($rule->validate("0"));
        $this->assertTrue($rule->validate(0));
        $this->assertTrue($rule->validate(true));
        $this->assertTrue($rule->validate('<p>x</p>'));
        $this->assertTrue($rule->validate('<img src="smile.jpg" alt="smile" />'));
        $this->assertTrue($rule->validate('<img src="smile.jpg" alt="smile"/>'));
        $this->assertTrue($rule->validate('<img src="smile.jpg" alt="smile"></img>'));
        $this->assertTrue($rule->validate('<hr />'));
        $this->assertTrue($rule->validate('<hr/>'));
        $this->assertTrue($rule->validate('<hr>'));
        $this->assertTrue($rule->validate('<hr></hr>'));
        // Fails
        $this->assertFalse($rule->validate(' '));
        $this->assertFalse($rule->validate('      '));
        $this->assertFalse($rule->validate("\t"));
        $this->assertFalse($rule->validate("\n"));
        $this->assertFalse($rule->validate("\r"));
        $this->assertFalse($rule->validate("\r\n"));
        $this->assertFalse($rule->validate(" \t  \n  \r "));
        $this->assertFalse($rule->validate('<p></p>'));
        $this->assertFalse($rule->validate('<p> </p>'));
        $this->assertFalse($rule->validate('<br />'));
        $this->assertFalse($rule->validate('<br/>'));
        $this->assertFalse($rule->validate('<br>'));
        $this->assertFalse($rule->validate('&nbsp;'));
        $this->assertFalse($rule->validate(''));
        $this->assertFalse($rule->validate(false));
        $this->assertFalse($rule->validate(null));

        if (isset($strictformsrequired)) {
            $CFG->strictformsrequired = $strictformsrequired;
        }
    }

    public function test_generate_id_select() {
        $el = new MoodleQuickForm_select('choose_one', 'Choose one',
            array(1 => 'One', '2' => 'Two'));
        $el->_generateId();
        $this->assertEquals('id_choose_one', $el->getAttribute('id'));
    }

    public function test_generate_id_like_repeat() {
        $el = new MoodleQuickForm_text('text[7]', 'Type something');
        $el->_generateId();
        $this->assertEquals('id_text_7', $el->getAttribute('id'));
    }

    public function test_can_manually_set_id() {
        $el = new MoodleQuickForm_text('elementname', 'Type something',
            array('id' => 'customelementid'));
        $el->_generateId();
        $this->assertEquals('customelementid', $el->getAttribute('id'));
    }

    public function test_generate_id_radio() {
        $el = new MoodleQuickForm_radio('radio', 'Label', 'Choice label', 'choice_value');
        $el->_generateId();
        $this->assertEquals('id_radio_choice_value', $el->getAttribute('id'));
    }

    public function test_radio_can_manually_set_id() {
        $el = new MoodleQuickForm_radio('radio2', 'Label', 'Choice label', 'choice_value',
            array('id' => 'customelementid2'));
        $el->_generateId();
        $this->assertEquals('customelementid2', $el->getAttribute('id'));
    }

    public function test_generate_id_radio_like_repeat() {
        $el = new MoodleQuickForm_radio('repeatradio[2]', 'Label', 'Choice label', 'val');
        $el->_generateId();
        $this->assertEquals('id_repeatradio_2_val', $el->getAttribute('id'));
    }

    public function test_rendering() {
        $form = new formslib_test_form();
        ob_start();
        $form->display();
        $html = ob_get_clean();

        $this->assertTag(array('tag'=>'select', 'id'=>'id_choose_one',
            'attributes'=>array('name'=>'choose_one')), $html);

        $this->assertTag(array('tag'=>'input', 'id'=>'id_text_0',
            'attributes'=>array('type'=>'text', 'name'=>'text[0]')), $html);

        $this->assertTag(array('tag'=>'input', 'id'=>'id_text_1',
            'attributes'=>array('type'=>'text', 'name'=>'text[1]')), $html);

        $this->assertTag(array('tag'=>'input', 'id'=>'id_radio_choice_value',
            'attributes'=>array('type'=>'radio', 'name'=>'radio', 'value'=>'choice_value')), $html);

        $this->assertTag(array('tag'=>'input', 'id'=>'customelementid2',
            'attributes'=>array('type'=>'radio', 'name'=>'radio2')), $html);

        $this->assertTag(array('tag'=>'input', 'id'=>'id_repeatradio_0_2',
            'attributes'=>array('type'=>'radio', 'name'=>'repeatradio[0]', 'value'=>'2')), $html);

        $this->assertTag(array('tag'=>'input', 'id'=>'id_repeatradio_2_1',
            'attributes'=>array('type'=>'radio', 'name'=>'repeatradio[2]', 'value'=>'1')), $html);

        $this->assertTag(array('tag'=>'input', 'id'=>'id_repeatradio_2_2',
            'attributes'=>array('type'=>'radio', 'name'=>'repeatradio[2]', 'value'=>'2')), $html);
    }

    public function test_type_cleaning() {
        $expectedtypes = array(
            'simpleel' => PARAM_INT,
            'groupel1' => PARAM_INT,
            'groupel2' => PARAM_FLOAT,
            'groupel3' => PARAM_INT,
            'namedgroup' => array(
                'sndgroupel1' => PARAM_INT,
                'sndgroupel2' => PARAM_FLOAT,
                'sndgroupel3' => PARAM_INT
            ),
            'namedgroupinherit' => array(
                'thdgroupel1' => PARAM_INT,
                'thdgroupel2' => PARAM_INT
            ),
            'repeatedel' => array(
                0 => PARAM_INT,
                1 => PARAM_INT
            ),
            'repeatedelinherit' => array(
                0 => PARAM_INT,
                1 => PARAM_INT
            ),
            'squaretest' => array(
                0 => PARAM_INT
            ),
            'nested' => array(
                0 => array(
                    'bob' => array(
                        123 => PARAM_INT,
                        'foo' => PARAM_FLOAT
                    ),
                    'xyz' => PARAM_RAW
                ),
                1 => PARAM_INT
            )
        );
        $valuessubmitted = array(
            'simpleel' => '11.01',
            'groupel1' => '11.01',
            'groupel2' => '11.01',
            'groupel3' => '11.01',
            'namedgroup' => array(
                'sndgroupel1' => '11.01',
                'sndgroupel2' => '11.01',
                'sndgroupel3' => '11.01'
            ),
            'namedgroupinherit' => array(
                'thdgroupel1' => '11.01',
                'thdgroupel2' => '11.01'
            ),
            'repeatedel' => array(
                0 => '11.01',
                1 => '11.01'
            ),
            'repeatedelinherit' => array(
                0 => '11.01',
                1 => '11.01'
            ),
            'squaretest' => array(
                0 => '11.01'
            ),
            'nested' => array(
                0 => array(
                    'bob' => array(
                        123 => '11.01',
                        'foo' => '11.01'
                    ),
                    'xyz' => '11.01'
                ),
                1 => '11.01'
            )
        );
        $expectedvalues = array(
            'simpleel' => 11,
            'groupel1' => 11,
            'groupel2' => 11.01,
            'groupel3' => 11,
            'namedgroup' => array(
                'sndgroupel1' => 11,
                'sndgroupel2' => 11.01,
                'sndgroupel3' => 11
            ),
            'namedgroupinherit' => array(
                'thdgroupel1' => 11,
                'thdgroupel2' => 11
            ),
            'repeatable' => 2,
            'repeatedel' => array(
                0 => 11,
                1 => 11
            ),
            'repeatableinherit' => 2,
            'repeatedelinherit' => array(
                0 => 11,
                1 => 11
            ),
            'squaretest' => array(
                0 => 11
            ),
            'nested' => array(
                0 => array(
                    'bob' => array(
                        123 => 11,
                        'foo' => 11.01
                    ),
                    'xyz' => '11.01'
                ),
                1 => 11
            )
        );

        $mform = new formslib_clean_value();
        $mform->get_form()->updateSubmission($valuessubmitted, null);
        foreach ($expectedtypes as $elementname => $expected) {
            $actual = $mform->get_form()->getCleanType($elementname, $valuessubmitted[$elementname]);
            $this->assertSame($expected, $actual, "Failed validating clean type of '$elementname'");
        }

        $data = $mform->get_data();
        $this->assertSame($expectedvalues, (array) $data);
    }
}


/**
 * Test form to be used by {@link formslib_test::test_rendering()}.
 */
class formslib_test_form extends moodleform {
    public function definition() {
        $this->_form->addElement('select', 'choose_one', 'Choose one',
            array(1 => 'One', '2' => 'Two'));

        $repeatels = array(
            $this->_form->createElement('text', 'text', 'Type something')
        );
        $this->repeat_elements($repeatels, 2, array(), 'numtexts', 'addtexts');

        $this->_form->addElement('radio', 'radio', 'Label', 'Choice label', 'choice_value');

        $this->_form->addElement('radio', 'radio2', 'Label', 'Choice label', 'choice_value',
            array('id' => 'customelementid2'));

        $repeatels = array(
            $this->_form->createElement('radio', 'repeatradio', 'Choose {no}', 'One', 1),
            $this->_form->createElement('radio', 'repeatradio', 'Choose {no}', 'Two', 2),
        );
        $this->repeat_elements($repeatels, 3, array(), 'numradios', 'addradios');
    }
}

class formslib_clean_value extends moodleform {
    public function get_form() {
        return $this->_form;
    }
    public function definition() {
        $mform = $this->_form;

        // Add a simple int.
        $mform->addElement('text', 'simpleel', 'simpleel');
        $mform->setType('simpleel', PARAM_INT);

        // Add a non-named group.
        $group = array(
            $mform->createElement('text', 'groupel1', 'groupel1'),
            $mform->createElement('text', 'groupel2', 'groupel2'),
            $mform->createElement('text', 'groupel3', 'groupel3')
        );
        $mform->setType('groupel1', PARAM_INT);
        $mform->setType('groupel2', PARAM_FLOAT);
        $mform->setType('groupel3', PARAM_INT);
        $mform->addGroup($group);

        // Add a named group.
        $group = array(
            $mform->createElement('text', 'sndgroupel1', 'sndgroupel1'),
            $mform->createElement('text', 'sndgroupel2', 'sndgroupel2'),
            $mform->createElement('text', 'sndgroupel3', 'sndgroupel3')
        );
        $mform->addGroup($group, 'namedgroup');
        $mform->setType('namedgroup[sndgroupel1]', PARAM_INT);
        $mform->setType('namedgroup[sndgroupel2]', PARAM_FLOAT);
        $mform->setType('namedgroup[sndgroupel3]', PARAM_INT);

        // Add a named group, with inheritance.
        $group = array(
            $mform->createElement('text', 'thdgroupel1', 'thdgroupel1'),
            $mform->createElement('text', 'thdgroupel2', 'thdgroupel2')
        );
        $mform->addGroup($group, 'namedgroupinherit');
        $mform->setType('namedgroupinherit', PARAM_INT);

        // Add a repetition.
        $repeat = $mform->createElement('text', 'repeatedel', 'repeatedel');
        $this->repeat_elements(array($repeat), 2, array('repeatedel' => array('type' => PARAM_INT)), 'repeatable', 'add', 0);

        // Add a repetition, with inheritance.
        $repeat = $mform->createElement('text', 'repeatedelinherit', 'repeatedelinherit');
        $this->repeat_elements(array($repeat), 2, array(), 'repeatableinherit', 'add', 0);
        $mform->setType('repeatedelinherit', PARAM_INT);

        // Add an arbitrary named element.
        $mform->addElement('text', 'squaretest[0]', 'squaretest[0]');
        $mform->setType('squaretest[0]', PARAM_INT);

        // Add an arbitrary nested array named element.
        $mform->addElement('text', 'nested[0][bob][123]', 'nested[0][bob][123]');
        $mform->setType('nested[0][bob][123]', PARAM_INT);

        // Add inheritance test cases.
        $mform->setType('nested', PARAM_INT);
        $mform->setType('nested[0]', PARAM_RAW);
        $mform->setType('nested[0][bob]', PARAM_FLOAT);
        $mform->addElement('text', 'nested[1]', 'nested[1]');
        $mform->addElement('text', 'nested[0][xyz]', 'nested[0][xyz]');
        $mform->addElement('text', 'nested[0][bob][foo]', 'nested[0][bob][foo]');
    }
}
