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
 * Unit tests for (some of) ../moodlelib.php.
 *
 * @package    core
 * @category   phpunit
 * @copyright  &copy; 2006 The Open University
 * @author     T.J.Hunt@open.ac.uk
 * @author     nicolas@moodle.com
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/moodlelib.php');


class moodlelib_testcase extends advanced_testcase {

    public static $includecoverage = array('lib/moodlelib.php');

    var $user_agents = array(
        'MSIE' => array(
            '5.0' => array('Windows 98' => 'Mozilla/4.0 (compatible; MSIE 5.00; Windows 98)'),
            '5.5' => array('Windows 2000' => 'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0)'),
            '6.0' => array('Windows XP SP2' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)'),
            '7.0' => array('Windows XP SP2' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; YPC 3.0.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)'),
            '8.0' => array('Windows Vista' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET CLR 3.0.04506.30; .NET CLR 3.0.04506.648)'),
            '9.0' => array('Windows 7' => 'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))'),

        ),
        'Firefox' => array(
            '1.0.6'   => array('Windows XP' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.10) Gecko/20050716 Firefox/1.0.6'),
            '1.5'     => array('Windows XP' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; nl; rv:1.8) Gecko/20051107 Firefox/1.5'),
            '1.5.0.1' => array('Windows XP' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.8.0.1) Gecko/20060111 Firefox/1.5.0.1'),
            '2.0'     => array('Windows XP' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1',
                'Ubuntu Linux AMD64' => 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.8.1) Gecko/20060601 Firefox/2.0 (Ubuntu-edgy)'),
            '3.0.6' => array('SUSE' => 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.0.6) Gecko/2009012700 SUSE/3.0.6-1.4 Firefox/3.0.6'),
        ),
        'Safari' => array(
            '312' => array('Mac OS X' => 'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-us) AppleWebKit/312.1 (KHTML, like Gecko) Safari/312'),
            '412' => array('Mac OS X' => 'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/412 (KHTML, like Gecko) Safari/412')
        ),
        'Safari iOS' => array(
            '528' => array('iPhone' => 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_1_2 like Mac OS X; cs-cz) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7D11 Safari/528.16'),
            '533' => array('iPad' => 'Mozilla/5.0 (iPad; U; CPU OS 4_2_1 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148 Safari/6533.18.5'),
        ),
        'WebKit Android' => array(
            '525' => array('G1 Phone' => 'Mozilla/5.0 (Linux; U; Android 1.1; en-gb; dream) AppleWebKit/525.10+ (KHTML, like Gecko) Version/3.0.4 Mobile Safari/523.12.2 – G1 Phone'),
            '530' => array('Nexus' => 'Mozilla/5.0 (Linux; U; Android 2.1; en-us; Nexus One Build/ERD62) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17 –Nexus'),
        ),
        'Chrome' => array(
            '8' => array('Mac OS X' => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_5; en-US) AppleWebKit/534.10 (KHTML, like Gecko) Chrome/8.0.552.215 Safari/534.10'),
        ),
        'Opera' => array(
            '8.51' => array('Windows XP' => 'Opera/8.51 (Windows NT 5.1; U; en)'),
            '9.0'  => array('Windows XP' => 'Opera/9.0 (Windows NT 5.1; U; en)',
                'Debian Linux' => 'Opera/9.01 (X11; Linux i686; U; en)')
        )
    );

    function test_cleanremoteaddr() {
        //IPv4
        $this->assertEquals(cleanremoteaddr('1023.121.234.1'), null);
        $this->assertEquals(cleanremoteaddr('123.121.234.01 '), '123.121.234.1');

        //IPv6
        $this->assertEquals(cleanremoteaddr('0:0:0:0:0:0:0:0:0'), null);
        $this->assertEquals(cleanremoteaddr('0:0:0:0:0:0:0:abh'), null);
        $this->assertEquals(cleanremoteaddr('0:0:0:::0:0:1'), null);
        $this->assertEquals(cleanremoteaddr('0:0:0:0:0:0:0:0', true), '::');
        $this->assertEquals(cleanremoteaddr('0:0:0:0:0:0:1:1', true), '::1:1');
        $this->assertEquals(cleanremoteaddr('abcd:00ef:0:0:0:0:0:0', true), 'abcd:ef::');
        $this->assertEquals(cleanremoteaddr('1:0:0:0:0:0:0:1', true), '1::1');
        $this->assertEquals(cleanremoteaddr('::10:1', false), '0:0:0:0:0:0:10:1');
        $this->assertEquals(cleanremoteaddr('01:1::', false), '1:1:0:0:0:0:0:0');
        $this->assertEquals(cleanremoteaddr('10::10', false), '10:0:0:0:0:0:0:10');
        $this->assertEquals(cleanremoteaddr('::ffff:192.168.1.1', true), '::ffff:c0a8:11');
    }

    function test_address_in_subnet() {
        /// 1: xxx.xxx.xxx.xxx/nn or xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx/nnn          (number of bits in net mask)
        $this->assertTrue(address_in_subnet('123.121.234.1', '123.121.234.1/32'));
        $this->assertFalse(address_in_subnet('123.121.23.1', '123.121.23.0/32'));
        $this->assertTrue(address_in_subnet('10.10.10.100',  '123.121.23.45/0'));
        $this->assertTrue(address_in_subnet('123.121.234.1', '123.121.234.0/24'));
        $this->assertFalse(address_in_subnet('123.121.34.1', '123.121.234.0/24'));
        $this->assertTrue(address_in_subnet('123.121.234.1', '123.121.234.0/30'));
        $this->assertFalse(address_in_subnet('123.121.23.8', '123.121.23.0/30'));
        $this->assertTrue(address_in_subnet('baba:baba::baba', 'baba:baba::baba/128'));
        $this->assertFalse(address_in_subnet('bab:baba::baba', 'bab:baba::cece/128'));
        $this->assertTrue(address_in_subnet('baba:baba::baba', 'cece:cece::cece/0'));
        $this->assertTrue(address_in_subnet('baba:baba::baba', 'baba:baba::baba/128'));
        $this->assertTrue(address_in_subnet('baba:baba::00ba', 'baba:baba::/120'));
        $this->assertFalse(address_in_subnet('baba:baba::aba', 'baba:baba::/120'));
        $this->assertTrue(address_in_subnet('baba::baba:00ba', 'baba::baba:0/112'));
        $this->assertFalse(address_in_subnet('baba::aba:00ba', 'baba::baba:0/112'));
        $this->assertFalse(address_in_subnet('aba::baba:0000', 'baba::baba:0/112'));

        // fixed input
        $this->assertTrue(address_in_subnet('123.121.23.1   ', ' 123.121.23.0 / 24'));
        $this->assertTrue(address_in_subnet('::ffff:10.1.1.1', ' 0:0:0:000:0:ffff:a1:10 / 126'));

        // incorrect input
        $this->assertFalse(address_in_subnet('123.121.234.1', '123.121.234.1/-2'));
        $this->assertFalse(address_in_subnet('123.121.234.1', '123.121.234.1/64'));
        $this->assertFalse(address_in_subnet('123.121.234.x', '123.121.234.1/24'));
        $this->assertFalse(address_in_subnet('123.121.234.0', '123.121.234.xx/24'));
        $this->assertFalse(address_in_subnet('123.121.234.1', '123.121.234.1/xx0'));
        $this->assertFalse(address_in_subnet('::1', '::aa:0/xx0'));
        $this->assertFalse(address_in_subnet('::1', '::aa:0/-5'));
        $this->assertFalse(address_in_subnet('::1', '::aa:0/130'));
        $this->assertFalse(address_in_subnet('x:1', '::aa:0/130'));
        $this->assertFalse(address_in_subnet('::1', '::ax:0/130'));


        /// 2: xxx.xxx.xxx.xxx-yyy or  xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx::xxxx-yyyy (a range of IP addresses in the last group)
        $this->assertTrue(address_in_subnet('123.121.234.12', '123.121.234.12-14'));
        $this->assertTrue(address_in_subnet('123.121.234.13', '123.121.234.12-14'));
        $this->assertTrue(address_in_subnet('123.121.234.14', '123.121.234.12-14'));
        $this->assertFalse(address_in_subnet('123.121.234.1', '123.121.234.12-14'));
        $this->assertFalse(address_in_subnet('123.121.234.20', '123.121.234.12-14'));
        $this->assertFalse(address_in_subnet('123.121.23.12', '123.121.234.12-14'));
        $this->assertFalse(address_in_subnet('123.12.234.12', '123.121.234.12-14'));
        $this->assertTrue(address_in_subnet('baba:baba::baba', 'baba:baba::baba-babe'));
        $this->assertTrue(address_in_subnet('baba:baba::babc', 'baba:baba::baba-babe'));
        $this->assertTrue(address_in_subnet('baba:baba::babe', 'baba:baba::baba-babe'));
        $this->assertFalse(address_in_subnet('bab:baba::bab0', 'bab:baba::baba-babe'));
        $this->assertFalse(address_in_subnet('bab:baba::babf', 'bab:baba::baba-babe'));
        $this->assertFalse(address_in_subnet('bab:baba::bfbe', 'bab:baba::baba-babe'));
        $this->assertFalse(address_in_subnet('bfb:baba::babe', 'bab:baba::baba-babe'));

        // fixed input
        $this->assertTrue(address_in_subnet('123.121.234.12', '123.121.234.12 - 14 '));
        $this->assertTrue(address_in_subnet('bab:baba::babe', 'bab:baba::baba - babe  '));

        // incorrect input
        $this->assertFalse(address_in_subnet('123.121.234.12', '123.121.234.12-234.14'));
        $this->assertFalse(address_in_subnet('123.121.234.12', '123.121.234.12-256'));
        $this->assertFalse(address_in_subnet('123.121.234.12', '123.121.234.12--256'));


        /// 3: xxx.xxx or xxx.xxx. or xxx:xxx:xxxx or xxx:xxx:xxxx.                  (incomplete address, a bit non-technical ;-)
        $this->assertTrue(address_in_subnet('123.121.234.12', '123.121.234.12'));
        $this->assertFalse(address_in_subnet('123.121.23.12', '123.121.23.13'));
        $this->assertTrue(address_in_subnet('123.121.234.12', '123.121.234.'));
        $this->assertTrue(address_in_subnet('123.121.234.12', '123.121.234'));
        $this->assertTrue(address_in_subnet('123.121.234.12', '123.121'));
        $this->assertTrue(address_in_subnet('123.121.234.12', '123'));
        $this->assertFalse(address_in_subnet('123.121.234.1', '12.121.234.'));
        $this->assertFalse(address_in_subnet('123.121.234.1', '12.121.234'));
        $this->assertTrue(address_in_subnet('baba:baba::bab', 'baba:baba::bab'));
        $this->assertFalse(address_in_subnet('baba:baba::ba', 'baba:baba::bc'));
        $this->assertTrue(address_in_subnet('baba:baba::bab', 'baba:baba'));
        $this->assertTrue(address_in_subnet('baba:baba::bab', 'baba:'));
        $this->assertFalse(address_in_subnet('bab:baba::bab', 'baba:'));


        /// multiple subnets
        $this->assertTrue(address_in_subnet('123.121.234.12', '::1/64, 124., 123.121.234.10-30'));
        $this->assertTrue(address_in_subnet('124.121.234.12', '::1/64, 124., 123.121.234.10-30'));
        $this->assertTrue(address_in_subnet('::2',            '::1/64, 124., 123.121.234.10-30'));
        $this->assertFalse(address_in_subnet('12.121.234.12', '::1/64, 124., 123.121.234.10-30'));


        /// other incorrect input
        $this->assertFalse(address_in_subnet('123.123.123.123', ''));
    }

    /**
     * Modifies $_SERVER['HTTP_USER_AGENT'] manually to check if check_browser_version
     * works as expected.
     */
    function test_check_browser_version()
    {
        global $CFG;

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Safari']['412']['Mac OS X'];
        $this->assertTrue(check_browser_version('Safari'));
        $this->assertTrue(check_browser_version('WebKit'));
        $this->assertTrue(check_browser_version('Safari', '312'));
        $this->assertFalse(check_browser_version('Safari', '500'));
        $this->assertFalse(check_browser_version('Chrome'));
        $this->assertFalse(check_browser_version('Safari iOS'));

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Safari iOS']['528']['iPhone'];
        $this->assertTrue(check_browser_version('Safari iOS'));
        $this->assertTrue(check_browser_version('WebKit'));
        $this->assertTrue(check_browser_version('Safari iOS', '527'));
        $this->assertFalse(check_browser_version('Safari iOS', 590));
        $this->assertFalse(check_browser_version('Safari', '312'));
        $this->assertFalse(check_browser_version('Safari', '500'));
        $this->assertFalse(check_browser_version('Chrome'));

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['WebKit Android']['530']['Nexus'];
        $this->assertTrue(check_browser_version('WebKit'));
        $this->assertTrue(check_browser_version('WebKit Android', '527'));
        $this->assertFalse(check_browser_version('WebKit Android', 590));
        $this->assertFalse(check_browser_version('Safari'));
        $this->assertFalse(check_browser_version('Chrome'));

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Chrome']['8']['Mac OS X'];
        $this->assertTrue(check_browser_version('Chrome'));
        $this->assertTrue(check_browser_version('WebKit'));
        $this->assertTrue(check_browser_version('Chrome', 8));
        $this->assertFalse(check_browser_version('Chrome', 10));
        $this->assertFalse(check_browser_version('Safari', '1'));

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Opera']['9.0']['Windows XP'];
        $this->assertTrue(check_browser_version('Opera'));
        $this->assertTrue(check_browser_version('Opera', '8.0'));
        $this->assertFalse(check_browser_version('Opera', '10.0'));

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['MSIE']['6.0']['Windows XP SP2'];
        $this->assertTrue(check_browser_version('MSIE'));
        $this->assertTrue(check_browser_version('MSIE', '5.0'));
        $this->assertFalse(check_browser_version('MSIE', '7.0'));

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['MSIE']['5.0']['Windows 98'];
        $this->assertFalse(check_browser_version('MSIE'));
        $this->assertTrue(check_browser_version('MSIE', 0));
        $this->assertTrue(check_browser_version('MSIE', '5.0'));
        $this->assertFalse(check_browser_version('MSIE', '7.0'));

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['MSIE']['9.0']['Windows 7'];
        $this->assertTrue(check_browser_version('MSIE'));
        $this->assertTrue(check_browser_version('MSIE', 0));
        $this->assertTrue(check_browser_version('MSIE', '5.0'));
        $this->assertTrue(check_browser_version('MSIE', '9.0'));
        $this->assertFalse(check_browser_version('MSIE', '10'));

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Firefox']['2.0']['Windows XP'];
        $this->assertTrue(check_browser_version('Firefox'));
        $this->assertTrue(check_browser_version('Firefox', '1.5'));
        $this->assertFalse(check_browser_version('Firefox', '3.0'));
    }

    function test_get_browser_version_classes() {
        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Safari']['412']['Mac OS X'];
        $this->assertEquals(array('safari'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Chrome']['8']['Mac OS X'];
        $this->assertEquals(array('safari'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Safari iOS']['528']['iPhone'];
        $this->assertEquals(array('safari', 'ios'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['WebKit Android']['530']['Nexus'];
        $this->assertEquals(array('safari', 'android'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Chrome']['8']['Mac OS X'];
        $this->assertEquals(array('safari'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Opera']['9.0']['Windows XP'];
        $this->assertEquals(array('opera'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['MSIE']['6.0']['Windows XP SP2'];
        $this->assertEquals(array('ie', 'ie6'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['MSIE']['7.0']['Windows XP SP2'];
        $this->assertEquals(array('ie', 'ie7'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['MSIE']['8.0']['Windows Vista'];
        $this->assertEquals(array('ie', 'ie8'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Firefox']['2.0']['Windows XP'];
        $this->assertEquals(array('gecko', 'gecko18'), get_browser_version_classes());

        $_SERVER['HTTP_USER_AGENT'] = $this->user_agents['Firefox']['3.0.6']['SUSE'];
        $this->assertEquals(array('gecko', 'gecko19'), get_browser_version_classes());
    }

    function test_get_device_type() {
        // IE8 (common pattern ~1.5% of IE7/8 users have embedded IE6 agent))
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; BT Openworld BB; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; Hotbar 10.2.197.0; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 2.0.50727)';
        $this->assertEquals('default', get_device_type());
        // Genuine IE6
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 6.0; AOL 9.0; Windows NT 5.1; SV1; FunWebProducts; .NET CLR 1.0.3705; Media Center PC 2.8)';
        $this->assertEquals('legacy', get_device_type());
    }

    function test_fix_utf8() {
        // make sure valid data including other types is not changed
        $this->assertSame(null, fix_utf8(null));
        $this->assertSame(1, fix_utf8(1));
        $this->assertSame(1.1, fix_utf8(1.1));
        $this->assertSame(true, fix_utf8(true));
        $this->assertSame('', fix_utf8(''));
        $this->assertSame('abc', fix_utf8('abc'));
        $array = array('do', 're', 'mi');
        $this->assertSame($array, fix_utf8($array));
        $object = new stdClass();
        $object->a = 'aa';
        $object->b = 'bb';
        $this->assertEquals($object, fix_utf8($object));

        // valid utf8 string
        $this->assertSame("žlutý koníček přeskočil potůček \n\t\r\0", fix_utf8("žlutý koníček přeskočil potůček \n\t\r\0"));

        // invalid utf8 string
        $this->assertSame('aš', fix_utf8('a'.chr(130).'š'), 'This fails with buggy iconv() when mbstring extenstion is not available as fallback.');
    }

    function test_optional_param() {
        global $CFG;

        $_POST['username'] = 'post_user';
        $_GET['username'] = 'get_user';
        $this->assertSame(optional_param('username', 'default_user', PARAM_RAW), $_POST['username']);

        unset($_POST['username']);
        $this->assertSame(optional_param('username', 'default_user', PARAM_RAW), $_GET['username']);

        unset($_GET['username']);
        $this->assertSame(optional_param('username', 'default_user', PARAM_RAW), 'default_user');

        // make sure exception is triggered when some params are missing, hide error notices here - new in 2.2
        $_POST['username'] = 'post_user';
        try {
            optional_param('username', 'default_user', null);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            @optional_param('username', 'default_user');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            @optional_param('username');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            optional_param('', 'default_user', PARAM_RAW);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }

        // make sure warning is displayed if array submitted - TODO: throw exception in Moodle 2.3
        $debugging = isset($CFG->debug) ? $CFG->debug : null;
        $debugdisplay = isset($CFG->debugdisplay) ? $CFG->debugdisplay : null;
        $CFG->debug = DEBUG_DEVELOPER;
        $CFG->debugdisplay = true;

        ob_start();
        $this->assertSame(optional_param('username', 'default_user', PARAM_RAW), $_POST['username']);
        $d = ob_end_clean();
        $this->assertTrue($d !== '');

        if ($debugging !== null) {
            $CFG->debug = $debugging;
        } else {
            unset($CFG->debug);
        }
        if ($debugdisplay !== null) {
            $CFG->debugdisplay = $debugdisplay;
        } else {
            unset($CFG->debugdisplay);
        }
    }

    function test_optional_param_array() {
        global $CFG;

        $_POST['username'] = array('a'=>'post_user');
        $_GET['username'] = array('a'=>'get_user');
        $this->assertSame(optional_param_array('username', array('a'=>'default_user'), PARAM_RAW), $_POST['username']);

        unset($_POST['username']);
        $this->assertSame(optional_param_array('username', array('a'=>'default_user'), PARAM_RAW), $_GET['username']);

        unset($_GET['username']);
        $this->assertSame(optional_param_array('username', array('a'=>'default_user'), PARAM_RAW), array('a'=>'default_user'));

        // make sure exception is triggered when some params are missing, hide error notices here - new in 2.2
        $_POST['username'] = array('a'=>'post_user');
        try {
            optional_param_array('username', array('a'=>'default_user'), null);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            @optional_param_array('username', array('a'=>'default_user'));
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            @optional_param_array('username');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            optional_param_array('', array('a'=>'default_user'), PARAM_RAW);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }

        // do not allow nested arrays
        try {
            $_POST['username'] = array('a'=>array('b'=>'post_user'));
            optional_param_array('username', array('a'=>'default_user'), PARAM_RAW);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }

        // do not allow non-arrays
        $debugging = isset($CFG->debug) ? $CFG->debug : null;
        $debugdisplay = isset($CFG->debugdisplay) ? $CFG->debugdisplay : null;
        $CFG->debug = DEBUG_DEVELOPER;
        $CFG->debugdisplay = true;

        ob_start();
        $_POST['username'] = 'post_user';
        $this->assertSame(optional_param_array('username', array('a'=>'default_user'), PARAM_RAW), array('a'=>'default_user'));
        $d = ob_end_clean();
        $this->assertTrue($d !== '');

        // make sure array keys are sanitised
        ob_start();
        $_POST['username'] = array('abc123_;-/*-+ '=>'arrggh', 'a1_-'=>'post_user');
        $this->assertSame(optional_param_array('username', array(), PARAM_RAW), array('a1_-'=>'post_user'));
        $d = ob_end_clean();
        $this->assertTrue($d !== '');

        if ($debugging !== null) {
            $CFG->debug = $debugging;
        } else {
            unset($CFG->debug);
        }
        if ($debugdisplay !== null) {
            $CFG->debugdisplay = $debugdisplay;
        } else {
            unset($CFG->debugdisplay);
        }
    }

    function test_required_param() {
        global $CFG;

        $_POST['username'] = 'post_user';
        $_GET['username'] = 'get_user';
        $this->assertSame(required_param('username', PARAM_RAW), 'post_user');

        unset($_POST['username']);
        $this->assertSame(required_param('username', PARAM_RAW), 'get_user');

        unset($_GET['username']);
        try {
            $this->assertSame(required_param('username', PARAM_RAW), 'default_user');
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertTrue(true);
        }

        // make sure exception is triggered when some params are missing, hide error notices here - new in 2.2
        $_POST['username'] = 'post_user';
        try {
            @required_param('username');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            required_param('username', '');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            required_param('', PARAM_RAW);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }

        // make sure warning is displayed if array submitted - TODO: throw exception in Moodle 2.3
        $debugging = isset($CFG->debug) ? $CFG->debug : null;
        $debugdisplay = isset($CFG->debugdisplay) ? $CFG->debugdisplay : null;
        $CFG->debug = DEBUG_DEVELOPER;
        $CFG->debugdisplay = true;

        ob_start();
        $this->assertSame(required_param('username', PARAM_RAW), $_POST['username']);
        $d = ob_end_clean();
        $this->assertTrue($d !== '');

        if ($debugging !== null) {
            $CFG->debug = $debugging;
        } else {
            unset($CFG->debug);
        }
        if ($debugdisplay !== null) {
            $CFG->debugdisplay = $debugdisplay;
        } else {
            unset($CFG->debugdisplay);
        }
    }

    function test_required_param_array() {
        global $CFG;

        $_POST['username'] = array('a'=>'post_user');
        $_GET['username'] = array('a'=>'get_user');
        $this->assertSame(required_param_array('username', PARAM_RAW), $_POST['username']);

        unset($_POST['username']);
        $this->assertSame(required_param_array('username', PARAM_RAW), $_GET['username']);

        // make sure exception is triggered when some params are missing, hide error notices here - new in 2.2
        $_POST['username'] = array('a'=>'post_user');
        try {
            required_param_array('username', null);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            @required_param_array('username');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            required_param_array('', PARAM_RAW);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }

        // do not allow nested arrays
        try {
            $_POST['username'] = array('a'=>array('b'=>'post_user'));
            required_param_array('username', PARAM_RAW);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }

        // do not allow non-arrays
        try {
            $_POST['username'] = 'post_user';
            required_param_array('username', PARAM_RAW);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertTrue(true);
        }

        // do not allow non-arrays
        $debugging = isset($CFG->debug) ? $CFG->debug : null;
        $debugdisplay = isset($CFG->debugdisplay) ? $CFG->debugdisplay : null;
        $CFG->debug = DEBUG_DEVELOPER;
        $CFG->debugdisplay = true;

        // make sure array keys are sanitised
        ob_start();
        $_POST['username'] = array('abc123_;-/*-+ '=>'arrggh', 'a1_-'=>'post_user');
        $this->assertSame(required_param_array('username', PARAM_RAW), array('a1_-'=>'post_user'));
        $d = ob_end_clean();
        $this->assertTrue($d !== '');

        if ($debugging !== null) {
            $CFG->debug = $debugging;
        } else {
            unset($CFG->debug);
        }
        if ($debugdisplay !== null) {
            $CFG->debugdisplay = $debugdisplay;
        } else {
            unset($CFG->debugdisplay);
        }
    }

    function test_clean_param() {
        // forbid objects and arrays
        try {
            clean_param(array('x', 'y'), PARAM_RAW);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            $param = new stdClass();
            $param->id = 1;
            clean_param($param, PARAM_RAW);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }

        // require correct type
        try {
            clean_param('x', 'xxxxxx');
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            @clean_param('x');
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertTrue(true);
        }

    }

    function test_clean_param_array() {
        $this->assertSame(clean_param_array(null, PARAM_RAW), array());
        $this->assertSame(clean_param_array(array('a', 'b'), PARAM_RAW), array('a', 'b'));
        $this->assertSame(clean_param_array(array('a', array('b')), PARAM_RAW, true), array('a', array('b')));

        // require correct type
        try {
            clean_param_array(array('x'), 'xxxxxx');
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            @clean_param_array(array('x'));
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertTrue(true);
        }

        try {
            clean_param_array(array('x', array('y')), PARAM_RAW);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertTrue(true);
        }

        // test recursive
    }

    function test_clean_param_raw() {
        $this->assertEquals(clean_param('#()*#,9789\'".,<42897></?$(*DSFMO#$*)(SDJ)($*)', PARAM_RAW),
            '#()*#,9789\'".,<42897></?$(*DSFMO#$*)(SDJ)($*)');
    }

    function test_clean_param_trim() {
        $this->assertEquals(clean_param("   Frog toad   \r\n  ", PARAM_RAW_TRIMMED), 'Frog toad');
    }

    function test_clean_param_clean() {
        // PARAM_CLEAN is an ugly hack, do not use in new code (skodak)
        // instead use more specific type, or submit sothing that can be verified properly
        $this->assertEquals(clean_param('xx<script>', PARAM_CLEAN), 'xx');
    }

    function test_clean_param_alpha() {
        $this->assertEquals(clean_param('#()*#,9789\'".,<42897></?$(*DSFMO#$*)(SDJ)($*)', PARAM_ALPHA),
            'DSFMOSDJ');
    }

    function test_clean_param_alphanum() {
        $this->assertEquals(clean_param('#()*#,9789\'".,<42897></?$(*DSFMO#$*)(SDJ)($*)', PARAM_ALPHANUM),
            '978942897DSFMOSDJ');
    }

    function test_clean_param_alphaext() {
        $this->assertEquals(clean_param('#()*#,9789\'".,<42897></?$(*DSFMO#$*)(SDJ)($*)', PARAM_ALPHAEXT),
            'DSFMOSDJ');
    }

    function test_clean_param_sequence() {
        $this->assertEquals(clean_param('#()*#,9789\'".,<42897></?$(*DSFMO#$*)(SDJ)($*)', PARAM_SEQUENCE),
            ',9789,42897');
    }

    function test_clean_param_component() {
        // please note the cleaning of component names is very strict, no guessing here
        $this->assertSame(clean_param('mod_forum', PARAM_COMPONENT), 'mod_forum');
        $this->assertSame(clean_param('block_online_users', PARAM_COMPONENT), 'block_online_users');
        $this->assertSame(clean_param('block_blond_online_users', PARAM_COMPONENT), 'block_blond_online_users');
        $this->assertSame(clean_param('mod_something2', PARAM_COMPONENT), 'mod_something2');
        $this->assertSame(clean_param('forum', PARAM_COMPONENT), 'forum');
        $this->assertSame(clean_param('user', PARAM_COMPONENT), 'user');
        $this->assertSame(clean_param('rating', PARAM_COMPONENT), 'rating');
        $this->assertSame(clean_param('mod_2something', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('2mod_something', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('mod_something_xx', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('auth_something__xx', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('mod_Something', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('mod_somethíng', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('auth_xx-yy', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('_auth_xx', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('a2uth_xx', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('auth_xx_', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('auth_xx.old', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('_user', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('2rating', PARAM_COMPONENT), '');
        $this->assertSame(clean_param('user_', PARAM_COMPONENT), '');
    }

    function test_clean_param_plugin() {
        // please note the cleaning of plugin names is very strict, no guessing here
        $this->assertSame(clean_param('forum', PARAM_PLUGIN), 'forum');
        $this->assertSame(clean_param('forum2', PARAM_PLUGIN), 'forum2');
        $this->assertSame(clean_param('online_users', PARAM_PLUGIN), 'online_users');
        $this->assertSame(clean_param('blond_online_users', PARAM_PLUGIN), 'blond_online_users');
        $this->assertSame(clean_param('online__users', PARAM_PLUGIN), '');
        $this->assertSame(clean_param('forum ', PARAM_PLUGIN), '');
        $this->assertSame(clean_param('forum.old', PARAM_PLUGIN), '');
        $this->assertSame(clean_param('xx-yy', PARAM_PLUGIN), '');
        $this->assertSame(clean_param('2xx', PARAM_PLUGIN), '');
        $this->assertSame(clean_param('Xx', PARAM_PLUGIN), '');
        $this->assertSame(clean_param('_xx', PARAM_PLUGIN), '');
        $this->assertSame(clean_param('xx_', PARAM_PLUGIN), '');
    }

    function test_clean_param_area() {
        // please note the cleaning of area names is very strict, no guessing here
        $this->assertSame(clean_param('something', PARAM_AREA), 'something');
        $this->assertSame(clean_param('something2', PARAM_AREA), 'something2');
        $this->assertSame(clean_param('some_thing', PARAM_AREA), 'some_thing');
        $this->assertSame(clean_param('some_thing_xx', PARAM_AREA), 'some_thing_xx');
        $this->assertSame(clean_param('_something', PARAM_AREA), '');
        $this->assertSame(clean_param('something_', PARAM_AREA), '');
        $this->assertSame(clean_param('2something', PARAM_AREA), '');
        $this->assertSame(clean_param('Something', PARAM_AREA), '');
        $this->assertSame(clean_param('some-thing', PARAM_AREA), '');
        $this->assertSame(clean_param('somethííng', PARAM_AREA), '');
        $this->assertSame(clean_param('something.x', PARAM_AREA), '');
    }

    function test_clean_param_text() {
        $this->assertEquals(PARAM_TEXT, PARAM_MULTILANG);
        //standard
        $this->assertEquals(clean_param('xx<lang lang="en">aa</lang><lang lang="yy">pp</lang>', PARAM_TEXT), 'xx<lang lang="en">aa</lang><lang lang="yy">pp</lang>');
        $this->assertEquals(clean_param('<span lang="en" class="multilang">aa</span><span lang="xy" class="multilang">bb</span>', PARAM_TEXT), '<span lang="en" class="multilang">aa</span><span lang="xy" class="multilang">bb</span>');
        $this->assertEquals(clean_param('xx<lang lang="en">aa'."\n".'</lang><lang lang="yy">pp</lang>', PARAM_TEXT), 'xx<lang lang="en">aa'."\n".'</lang><lang lang="yy">pp</lang>');
        //malformed
        $this->assertEquals(clean_param('<span lang="en" class="multilang">aa</span>', PARAM_TEXT), '<span lang="en" class="multilang">aa</span>');
        $this->assertEquals(clean_param('<span lang="en" class="nothing" class="multilang">aa</span>', PARAM_TEXT), 'aa');
        $this->assertEquals(clean_param('<lang lang="en" class="multilang">aa</lang>', PARAM_TEXT), 'aa');
        $this->assertEquals(clean_param('<lang lang="en!!">aa</lang>', PARAM_TEXT), 'aa');
        $this->assertEquals(clean_param('<span lang="en==" class="multilang">aa</span>', PARAM_TEXT), 'aa');
        $this->assertEquals(clean_param('a<em>b</em>c', PARAM_TEXT), 'abc');
        $this->assertEquals(clean_param('a><xx >c>', PARAM_TEXT), 'a>c>'); // standard strip_tags() behaviour
        $this->assertEquals(clean_param('a<b', PARAM_TEXT), 'a');
        $this->assertEquals(clean_param('a>b', PARAM_TEXT), 'a>b');
        $this->assertEquals(clean_param('<lang lang="en">a>a</lang>', PARAM_TEXT), '<lang lang="en">a>a</lang>'); // standard strip_tags() behaviour
        $this->assertEquals(clean_param('<lang lang="en">a<a</lang>', PARAM_TEXT), 'a');
        $this->assertEquals(clean_param('<lang lang="en">a<br>a</lang>', PARAM_TEXT), '<lang lang="en">aa</lang>');
    }

    function test_clean_param_url() {
        // Test PARAM_URL and PARAM_LOCALURL a bit
        $this->assertEquals(clean_param('http://google.com/', PARAM_URL), 'http://google.com/');
        $this->assertEquals(clean_param('http://some.very.long.and.silly.domain/with/a/path/', PARAM_URL), 'http://some.very.long.and.silly.domain/with/a/path/');
        $this->assertEquals(clean_param('http://localhost/', PARAM_URL), 'http://localhost/');
        $this->assertEquals(clean_param('http://0.255.1.1/numericip.php', PARAM_URL), 'http://0.255.1.1/numericip.php');
        $this->assertEquals(clean_param('/just/a/path', PARAM_URL), '/just/a/path');
        $this->assertEquals(clean_param('funny:thing', PARAM_URL), '');
    }

    function test_clean_param_localurl() {
        global $CFG;
        $this->assertEquals(clean_param('http://google.com/', PARAM_LOCALURL), '');
        $this->assertEquals(clean_param('http://some.very.long.and.silly.domain/with/a/path/', PARAM_LOCALURL), '');
        $this->assertEquals(clean_param($CFG->wwwroot, PARAM_LOCALURL), $CFG->wwwroot);
        $this->assertEquals(clean_param('/just/a/path', PARAM_LOCALURL), '/just/a/path');
        $this->assertEquals(clean_param('funny:thing', PARAM_LOCALURL), '');
        $this->assertEquals(clean_param('course/view.php?id=3', PARAM_LOCALURL), 'course/view.php?id=3');
    }

    function test_clean_param_file() {
        $this->assertEquals(clean_param('correctfile.txt', PARAM_FILE), 'correctfile.txt');
        $this->assertEquals(clean_param('b\'a<d`\\/fi:l>e.t"x|t', PARAM_FILE), 'badfile.txt');
        $this->assertEquals(clean_param('../parentdirfile.txt', PARAM_FILE), 'parentdirfile.txt');
        //The following behaviours have been maintained although they seem a little odd
        $this->assertEquals(clean_param('funny:thing', PARAM_FILE), 'funnything');
        $this->assertEquals(clean_param('./currentdirfile.txt', PARAM_FILE), '.currentdirfile.txt');
        $this->assertEquals(clean_param('c:\temp\windowsfile.txt', PARAM_FILE), 'ctempwindowsfile.txt');
        $this->assertEquals(clean_param('/home/user/linuxfile.txt', PARAM_FILE), 'homeuserlinuxfile.txt');
        $this->assertEquals(clean_param('~/myfile.txt', PARAM_FILE), '~myfile.txt');
    }

    function test_clean_param_username() {
        global $CFG;
        $currentstatus =  $CFG->extendedusernamechars;

        // Run tests with extended character == FALSE;
        $CFG->extendedusernamechars = FALSE;
        $this->assertEquals(clean_param('johndoe123', PARAM_USERNAME), 'johndoe123' );
        $this->assertEquals(clean_param('john.doe', PARAM_USERNAME), 'john.doe');
        $this->assertEquals(clean_param('john-doe', PARAM_USERNAME), 'john-doe');
        $this->assertEquals(clean_param('john- doe', PARAM_USERNAME), 'john-doe');
        $this->assertEquals(clean_param('john_doe', PARAM_USERNAME), 'john_doe');
        $this->assertEquals(clean_param('john@doe', PARAM_USERNAME), 'john@doe');
        $this->assertEquals(clean_param('john~doe', PARAM_USERNAME), 'johndoe');
        $this->assertEquals(clean_param('john´doe', PARAM_USERNAME), 'johndoe');
        $this->assertEquals(clean_param('john#$%&() ', PARAM_USERNAME), 'john');
        $this->assertEquals(clean_param('JOHNdóé ', PARAM_USERNAME), 'johnd');
        $this->assertEquals(clean_param('john.,:;-_/|\ñÑ[]A_X-,D {} ~!@#$%^&*()_+ ?><[] ščřžžý ?ýá?ý??doe ', PARAM_USERNAME), 'john.-_a_x-d@_doe');


        // Test success condition, if extendedusernamechars == ENABLE;
        $CFG->extendedusernamechars = TRUE;
        $this->assertEquals(clean_param('john_doe', PARAM_USERNAME), 'john_doe');
        $this->assertEquals(clean_param('john@doe', PARAM_USERNAME), 'john@doe');
        $this->assertEquals(clean_param('john# $%&()+_^', PARAM_USERNAME), 'john#$%&()+_^');
        $this->assertEquals(clean_param('john~doe', PARAM_USERNAME), 'john~doe');
        $this->assertEquals(clean_param('joHN´doe', PARAM_USERNAME), 'john´doe');
        $this->assertEquals(clean_param('johnDOE', PARAM_USERNAME), 'johndoe');
        $this->assertEquals(clean_param('johndóé ', PARAM_USERNAME), 'johndóé');

        $CFG->extendedusernamechars = $currentstatus;
    }

    function test_clean_param_stringid() {
        // Test string identifiers validation
        // valid strings:
        $this->assertEquals(clean_param('validstring', PARAM_STRINGID), 'validstring');
        $this->assertEquals(clean_param('mod/foobar:valid_capability', PARAM_STRINGID), 'mod/foobar:valid_capability');
        $this->assertEquals(clean_param('CZ', PARAM_STRINGID), 'CZ');
        $this->assertEquals(clean_param('application/vnd.ms-powerpoint', PARAM_STRINGID), 'application/vnd.ms-powerpoint');
        $this->assertEquals(clean_param('grade2', PARAM_STRINGID), 'grade2');
        // invalid strings:
        $this->assertEquals(clean_param('trailing ', PARAM_STRINGID), '');
        $this->assertEquals(clean_param('space bar', PARAM_STRINGID), '');
        $this->assertEquals(clean_param('0numeric', PARAM_STRINGID), '');
        $this->assertEquals(clean_param('*', PARAM_STRINGID), '');
        $this->assertEquals(clean_param(' ', PARAM_STRINGID), '');
    }

    function test_clean_param_timezone() {
        // Test timezone validation
        $testvalues = array (
            'America/Jamaica'                => 'America/Jamaica',
            'America/Argentina/Cordoba'      => 'America/Argentina/Cordoba',
            'America/Port-au-Prince'         => 'America/Port-au-Prince',
            'America/Argentina/Buenos_Aires' => 'America/Argentina/Buenos_Aires',
            'PST8PDT'                        => 'PST8PDT',
            'Wrong.Value'                    => '',
            'Wrong/.Value'                   => '',
            'Wrong(Value)'                   => '',
            '0'                              => '0',
            '0.0'                            => '0.0',
            '0.5'                            => '0.5',
            '-12.5'                          => '-12.5',
            '+12.5'                          => '+12.5',
            '13.5'                           => '',
            '-13.5'                          => '',
            '0.2'                            => '');

        foreach ($testvalues as $testvalue => $expectedvalue) {
            $actualvalue = clean_param($testvalue, PARAM_TIMEZONE);
            $this->assertEquals($actualvalue, $expectedvalue);
        }
    }

    function test_validate_param() {
        try {
            $param = validate_param('11a', PARAM_INT);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            $param = validate_param('11', PARAM_INT);
            $this->assertEquals($param, 11);
        } catch (invalid_parameter_exception $ex) {
            $this->fail('invalid_parameter_exception not expected');
        }
        try {
            $param = validate_param(null, PARAM_INT, false);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            $param = validate_param(null, PARAM_INT, true);
            $this->assertTrue($param===null);
        } catch (invalid_parameter_exception $ex) {
            $this->fail('invalid_parameter_exception expected');
        }
        try {
            $param = validate_param(array(), PARAM_INT);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            $param = validate_param(new stdClass, PARAM_INT);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            $param = validate_param('1.0', PARAM_FLOAT);
            $this->assertSame(1.0, $param);

            // Make sure valid floats do not cause exception.
            validate_param(1.0, PARAM_FLOAT);
            validate_param(10, PARAM_FLOAT);
            validate_param('0', PARAM_FLOAT);
            validate_param('119813454.545464564564546564545646556564465465456465465465645645465645645645', PARAM_FLOAT);
            validate_param('011.1', PARAM_FLOAT);
            validate_param('11', PARAM_FLOAT);
            validate_param('+.1', PARAM_FLOAT);
            validate_param('-.1', PARAM_FLOAT);
            validate_param('1e10', PARAM_FLOAT);
            validate_param('.1e+10', PARAM_FLOAT);
            validate_param('1E-1', PARAM_FLOAT);
            $this->assertTrue(true);
        } catch (invalid_parameter_exception $ex) {
            $this->fail('Valid float notation not accepted');
        }
        try {
            $param = validate_param('1,2', PARAM_FLOAT);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            $param = validate_param('', PARAM_FLOAT);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            $param = validate_param('.', PARAM_FLOAT);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            $param = validate_param('e10', PARAM_FLOAT);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertTrue(true);
        }
        try {
            $param = validate_param('abc', PARAM_FLOAT);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertTrue(true);
        }
    }

    function test_shorten_text() {
        $text = "short text already no tags";
        $this->assertEquals($text, shorten_text($text));

        $text = "<p>short <b>text</b> already</p><p>with tags</p>";
        $this->assertEquals($text, shorten_text($text));

        $text = "long text without any tags blah de blah blah blah what";
        $this->assertEquals('long text without any tags ...', shorten_text($text));

        $text = "<div class='frog'><p><blockquote>Long text with tags that will ".
            "be chopped off but <b>should be added back again</b></blockquote></p></div>";
        $this->assertEquals("<div class='frog'><p><blockquote>Long text with " .
            "tags that ...</blockquote></p></div>", shorten_text($text));

        $text = "some text which shouldn't &nbsp; break there";
        $this->assertEquals("some text which shouldn't &nbsp; ...",
            shorten_text($text, 31));
        $this->assertEquals("some text which shouldn't ...",
            shorten_text($text, 30));

        // This case caused a bug up to 1.9.5
        $text = "<h3>standard 'break-out' sub groups in TGs?</h3>&nbsp;&lt;&lt;There are several";
        $this->assertEquals("<h3>standard 'break-out' sub groups in ...</h3>",
            shorten_text($text, 43));

        $text = "<h1>123456789</h1>";//a string with no convenient breaks
        $this->assertEquals("<h1>12345...</h1>",
            shorten_text($text, 8));

        // ==== this must work with UTF-8 too! ======

        // text without tags
        $text = "Žluťoučký koníček přeskočil";
        $this->assertEquals($text, shorten_text($text)); // 30 chars by default
        $this->assertEquals("Žluťoučký koníče...", shorten_text($text, 19, true));
        $this->assertEquals("Žluťoučký ...", shorten_text($text, 19, false));
        // And try it with 2-less (that are, in bytes, the middle of a sequence)
        $this->assertEquals("Žluťoučký koní...", shorten_text($text, 17, true));
        $this->assertEquals("Žluťoučký ...", shorten_text($text, 17, false));

        $text = "<p>Žluťoučký koníček <b>přeskočil</b> potůček</p>";
        $this->assertEquals($text, shorten_text($text, 60));
        $this->assertEquals("<p>Žluťoučký koníček ...</p>", shorten_text($text, 21));
        $this->assertEquals("<p>Žluťoučký koníče...</p>", shorten_text($text, 19, true));
        $this->assertEquals("<p>Žluťoučký ...</p>", shorten_text($text, 19, false));
        // And try it with 2-less (that are, in bytes, the middle of a sequence)
        $this->assertEquals("<p>Žluťoučký koní...</p>", shorten_text($text, 17, true));
        $this->assertEquals("<p>Žluťoučký ...</p>", shorten_text($text, 17, false));
        // And try over one tag (start/end), it does proper text len
        $this->assertEquals("<p>Žluťoučký koníček <b>př...</b></p>", shorten_text($text, 23, true));
        $this->assertEquals("<p>Žluťoučký koníček <b>přeskočil</b> pot...</p>", shorten_text($text, 34, true));
        // And in the middle of one tag
        $this->assertEquals("<p>Žluťoučký koníček <b>přeskočil...</b></p>", shorten_text($text, 30, true));

        // Japanese
        $text = '言語設定言語設定abcdefghijkl';
        $this->assertEquals($text, shorten_text($text)); // 30 chars by default
        $this->assertEquals("言語設定言語...", shorten_text($text, 9, true));
        $this->assertEquals("言語設定言語...", shorten_text($text, 9, false));
        $this->assertEquals("言語設定言語設定ab...", shorten_text($text, 13, true));
        $this->assertEquals("言語設定言語設定...", shorten_text($text, 13, false));

        // Chinese
        $text = '简体中文简体中文abcdefghijkl';
        $this->assertEquals($text, shorten_text($text)); // 30 chars by default
        $this->assertEquals("简体中文简体...", shorten_text($text, 9, true));
        $this->assertEquals("简体中文简体...", shorten_text($text, 9, false));
        $this->assertEquals("简体中文简体中文ab...", shorten_text($text, 13, true));
        $this->assertEquals("简体中文简体中文...", shorten_text($text, 13, false));

    }

    function test_usergetdate() {
        global $USER, $CFG, $DB;

        //Check if forcetimezone is set then save it and set it to use user timezone
        $cfgforcetimezone = null;
        if (isset($CFG->forcetimezone)) {
            $cfgforcetimezone = $CFG->forcetimezone;
            $CFG->forcetimezone = 99; //get user default timezone.
        }

        $olduser = $USER;
        $USER = $DB->get_record('user', array('id'=>2)); //admin

        $userstimezone = $USER->timezone;
        $USER->timezone = 2;//set the timezone to a known state

        // The string version of date comes from server locale setting and does
        // not respect user language, so it is necessary to reset that.
        $oldlocale = setlocale(LC_TIME, '0');
        setlocale(LC_TIME, 'en_AU.UTF-8');

        $ts = 1261540267; //the time this function was created

        $arr = usergetdate($ts,1);//specify the timezone as an argument
        $arr = array_values($arr);

        list($seconds,$minutes,$hours,$mday,$wday,$mon,$year,$yday,$weekday,$month) = $arr;
        $this->assertSame($seconds, 7);
        $this->assertSame($minutes, 51);
        $this->assertSame($hours, 4);
        $this->assertSame($mday, 23);
        $this->assertSame($wday, 3);
        $this->assertSame($mon, 12);
        $this->assertSame($year, 2009);
        $this->assertSame($yday, 356);
        $this->assertSame($weekday, 'Wednesday');
        $this->assertSame($month, 'December');
        $arr = usergetdate($ts);//gets the timezone from the $USER object
        $arr = array_values($arr);

        list($seconds,$minutes,$hours,$mday,$wday,$mon,$year,$yday,$weekday,$month) = $arr;
        $this->assertSame($seconds, 7);
        $this->assertSame($minutes, 51);
        $this->assertSame($hours, 5);
        $this->assertSame($mday, 23);
        $this->assertSame($wday, 3);
        $this->assertSame($mon, 12);
        $this->assertSame($year, 2009);
        $this->assertSame($yday, 356);
        $this->assertSame($weekday, 'Wednesday');
        $this->assertSame($month, 'December');
        //set the timezone back to what it was
        $USER->timezone = $userstimezone;

        //restore forcetimezone if changed.
        if (!is_null($cfgforcetimezone)) {
            $CFG->forcetimezone = $cfgforcetimezone;
        }

        setlocale(LC_TIME, $oldlocale);

        $USER = $olduser;
    }

    public function test_normalize_component() {

        // moodle core
        $this->assertEquals(normalize_component('moodle'), array('core', null));
        $this->assertEquals(normalize_component('core'), array('core', null));

        // moodle core subsystems
        $this->assertEquals(normalize_component('admin'), array('core', 'admin'));
        $this->assertEquals(normalize_component('core_admin'), array('core', 'admin'));

        // activity modules and their subplugins
        $this->assertEquals(normalize_component('workshop'), array('mod', 'workshop'));
        $this->assertEquals(normalize_component('mod_workshop'), array('mod', 'workshop'));
        $this->assertEquals(normalize_component('workshopform_accumulative'), array('workshopform', 'accumulative'));
        $this->assertEquals(normalize_component('quiz'), array('mod', 'quiz'));
        $this->assertEquals(normalize_component('quiz_grading'), array('quiz', 'grading'));
        $this->assertEquals(normalize_component('data'), array('mod', 'data'));
        $this->assertEquals(normalize_component('datafield_checkbox'), array('datafield', 'checkbox'));

        // other plugin types
        $this->assertEquals(normalize_component('auth_mnet'), array('auth', 'mnet'));
        $this->assertEquals(normalize_component('enrol_self'), array('enrol', 'self'));
        $this->assertEquals(normalize_component('block_html'), array('block', 'html'));
        $this->assertEquals(normalize_component('block_mnet_hosts'), array('block', 'mnet_hosts'));
        $this->assertEquals(normalize_component('local_amos'), array('local', 'amos'));

        // unknown components are supposed to be activity modules
        $this->assertEquals(normalize_component('whothefuckwouldcomewithsuchastupidnameofcomponent'),
            array('mod', 'whothefuckwouldcomewithsuchastupidnameofcomponent'));
        $this->assertEquals(normalize_component('whothefuck_wouldcomewithsuchastupidnameofcomponent'),
            array('mod', 'whothefuck_wouldcomewithsuchastupidnameofcomponent'));
        $this->assertEquals(normalize_component('whothefuck_would_come_withsuchastupidnameofcomponent'),
            array('mod', 'whothefuck_would_come_withsuchastupidnameofcomponent'));
    }

    protected function get_fake_preference_test_userid() {
        global $DB;

        // we need some nonexistent user id
        $id = 2147483647 - 666;
        if ($DB->get_records('user', array('id'=>$id))) {
            //weird!
            return false;
        }
        return $id;
    }

    public function test_mark_user_preferences_changed() {
        $this->resetAfterTest(true);
        if (!$otheruserid = $this->get_fake_preference_test_userid()) {
            $this->fail('Can not find unused user id for the preferences test');
            return;
        }

        set_cache_flag('userpreferenceschanged', $otheruserid, NULL);
        mark_user_preferences_changed($otheruserid);

        $this->assertEquals(get_cache_flag('userpreferenceschanged', $otheruserid, time()-10), 1);
        set_cache_flag('userpreferenceschanged', $otheruserid, NULL);
    }

    public function test_check_user_preferences_loaded() {
        global $DB;
        $this->resetAfterTest(true);

        if (!$otheruserid = $this->get_fake_preference_test_userid()) {
            $this->fail('Can not find unused user id for the preferences test');
            return;
        }

        $DB->delete_records('user_preferences', array('userid'=>$otheruserid));
        set_cache_flag('userpreferenceschanged', $otheruserid, NULL);

        $user = new stdClass();
        $user->id = $otheruserid;

        // load
        check_user_preferences_loaded($user);
        $this->assertTrue(isset($user->preference));
        $this->assertTrue(is_array($user->preference));
        $this->assertTrue(isset($user->preference['_lastloaded']));
        $this->assertEquals(count($user->preference), 1);

        // add preference via direct call
        $DB->insert_record('user_preferences', array('name'=>'xxx', 'value'=>'yyy', 'userid'=>$user->id));

        // no cache reload yet
        check_user_preferences_loaded($user);
        $this->assertEquals(count($user->preference), 1);

        // forced reloading of cache
        unset($user->preference);
        check_user_preferences_loaded($user);
        $this->assertEquals(count($user->preference), 2);
        $this->assertEquals($user->preference['xxx'], 'yyy');

        // add preference via direct call
        $DB->insert_record('user_preferences', array('name'=>'aaa', 'value'=>'bbb', 'userid'=>$user->id));

        // test timeouts and modifications from different session
        set_cache_flag('userpreferenceschanged', $user->id, 1, time() + 1000);
        $user->preference['_lastloaded'] = $user->preference['_lastloaded'] - 20;
        check_user_preferences_loaded($user);
        $this->assertEquals(count($user->preference), 2);
        check_user_preferences_loaded($user, 10);
        $this->assertEquals(count($user->preference), 3);
        $this->assertEquals($user->preference['aaa'], 'bbb');
        set_cache_flag('userpreferenceschanged', $user->id, null);
    }

    public function test_set_user_preference() {
        global $DB, $USER;
        $this->resetAfterTest(true);

        $olduser = $USER;
        $USER = $DB->get_record('user', array('id'=>2)); //admin

        if (!$otheruserid = $this->get_fake_preference_test_userid()) {
            $this->fail('Can not find unused user id for the preferences test');
            return;
        }

        $DB->delete_records('user_preferences', array('userid'=>$otheruserid));
        set_cache_flag('userpreferenceschanged', $otheruserid, null);

        $user = new stdClass();
        $user->id = $otheruserid;

        set_user_preference('aaa', 'bbb', $otheruserid);
        $this->assertEquals('bbb', $DB->get_field('user_preferences', 'value', array('userid'=>$otheruserid, 'name'=>'aaa')));
        $this->assertEquals('bbb', get_user_preferences('aaa', null, $otheruserid));

        set_user_preference('xxx', 'yyy', $user);
        $this->assertEquals('yyy', $DB->get_field('user_preferences', 'value', array('userid'=>$otheruserid, 'name'=>'xxx')));
        $this->assertEquals('yyy', get_user_preferences('xxx', null, $otheruserid));
        $this->assertTrue(is_array($user->preference));
        $this->assertEquals($user->preference['aaa'], 'bbb');
        $this->assertEquals($user->preference['xxx'], 'yyy');

        set_user_preference('xxx', NULL, $user);
        $this->assertSame(false, $DB->get_field('user_preferences', 'value', array('userid'=>$otheruserid, 'name'=>'xxx')));
        $this->assertSame(null, get_user_preferences('xxx', null, $otheruserid));

        set_user_preference('ooo', true, $user);
        $prefs = get_user_preferences(null, null, $otheruserid);
        $this->assertSame($prefs['aaa'], $user->preference['aaa']);
        $this->assertSame($prefs['ooo'], $user->preference['ooo']);
        $this->assertSame($prefs['ooo'], '1');

        set_user_preference('null', 0, $user);
        $this->assertSame('0', get_user_preferences('null', null, $otheruserid));

        $this->assertSame('lala', get_user_preferences('undefined', 'lala', $otheruserid));

        $DB->delete_records('user_preferences', array('userid'=>$otheruserid));
        set_cache_flag('userpreferenceschanged', $otheruserid, null);

        // test $USER default
        set_user_preference('_test_user_preferences_pref', 'ok');
        $this->assertSame('ok', $USER->preference['_test_user_preferences_pref']);
        unset_user_preference('_test_user_preferences_pref');
        $this->assertTrue(!isset($USER->preference['_test_user_preferences_pref']));

        // Test 1333 char values (no need for unicode, there are already tests for that in DB tests)
        $longvalue = str_repeat('a', 1333);
        set_user_preference('_test_long_user_preference', $longvalue);
        $this->assertEquals($longvalue, get_user_preferences('_test_long_user_preference'));
        $this->assertEquals($longvalue,
            $DB->get_field('user_preferences', 'value', array('userid' => $USER->id, 'name' => '_test_long_user_preference')));

        // Test > 1333 char values, coding_exception expected
        $longvalue = str_repeat('a', 1334);
        try {
            set_user_preference('_test_long_user_preference', $longvalue);
            $this->assertFail('Exception expected - longer than 1333 chars not allowed as preference value');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof coding_exception);
        }

        //test invalid params
        try {
            set_user_preference('_test_user_preferences_pref', array());
            $this->assertFail('Exception expected - array not valid preference value');
        } catch (Exception $ex) {
            $this->assertTrue(true);
        }
        try {
            set_user_preference('_test_user_preferences_pref', new stdClass);
            $this->assertFail('Exception expected - class not valid preference value');
        } catch (Exception $ex) {
            $this->assertTrue(true);
        }
        try {
            set_user_preference('_test_user_preferences_pref', 1, array('xx'=>1));
            $this->assertFail('Exception expected - user instance expected');
        } catch (Exception $ex) {
            $this->assertTrue(true);
        }
        try {
            set_user_preference('_test_user_preferences_pref', 1, 'abc');
            $this->assertFail('Exception expected - user instance expected');
        } catch (Exception $ex) {
            $this->assertTrue(true);
        }
        try {
            set_user_preference('', 1);
            $this->assertFail('Exception expected - invalid name accepted');
        } catch (Exception $ex) {
            $this->assertTrue(true);
        }
        try {
            set_user_preference('1', 1);
            $this->assertFail('Exception expected - invalid name accepted');
        } catch (Exception $ex) {
            $this->assertTrue(true);
        }

        $USER = $olduser;
    }

    public function test_get_extra_user_fields() {
        global $CFG, $USER, $DB;
        $oldshowuseridentity = $CFG->showuseridentity;

        $olduser = $USER;
        $USER = $DB->get_record('user', array('id'=>2)); //admin

        // It would be really nice if there were a way to 'mock' has_capability
        // checks (either to return true or false) but as there is not, this
        // test doesn't test the capability check. Presumably, anyone running
        // unit tests will have the capability.
        $context = context_system::instance();

        // No fields
        $CFG->showuseridentity = '';
        $this->assertEquals(array(), get_extra_user_fields($context));

        // One field
        $CFG->showuseridentity = 'frog';
        $this->assertEquals(array('frog'), get_extra_user_fields($context));

        // Two fields
        $CFG->showuseridentity = 'frog,zombie';
        $this->assertEquals(array('frog', 'zombie'), get_extra_user_fields($context));

        // No fields, except
        $CFG->showuseridentity = '';
        $this->assertEquals(array(), get_extra_user_fields($context, array('frog')));

        // One field
        $CFG->showuseridentity = 'frog';
        $this->assertEquals(array(), get_extra_user_fields($context, array('frog')));

        // Two fields
        $CFG->showuseridentity = 'frog,zombie';
        $this->assertEquals(array('zombie'), get_extra_user_fields($context, array('frog')));

        // As long as this test passes, the value will be set back. This is only
        // in-memory anyhow
        $CFG->showuseridentity = $oldshowuseridentity;

        $USER = $olduser;
    }

    public function test_get_extra_user_fields_sql() {
        global $CFG, $USER, $DB;

        $olduser = $USER;
        $USER = $DB->get_record('user', array('id'=>2)); //admin

        $oldshowuseridentity = $CFG->showuseridentity;
        $context = context_system::instance();

        // No fields
        $CFG->showuseridentity = '';
        $this->assertEquals('', get_extra_user_fields_sql($context));

        // One field
        $CFG->showuseridentity = 'frog';
        $this->assertEquals(', frog', get_extra_user_fields_sql($context));

        // Two fields with table prefix
        $CFG->showuseridentity = 'frog,zombie';
        $this->assertEquals(', u1.frog, u1.zombie', get_extra_user_fields_sql($context, 'u1'));

        // Two fields with field prefix
        $CFG->showuseridentity = 'frog,zombie';
        $this->assertEquals(', frog AS u_frog, zombie AS u_zombie',
            get_extra_user_fields_sql($context, '', 'u_'));

        // One field excluded
        $CFG->showuseridentity = 'frog';
        $this->assertEquals('', get_extra_user_fields_sql($context, '', '', array('frog')));

        // Two fields, one excluded, table+field prefix
        $CFG->showuseridentity = 'frog,zombie';
        $this->assertEquals(', u1.zombie AS u_zombie',
            get_extra_user_fields_sql($context, 'u1', 'u_', array('frog')));

        // As long as this test passes, the value will be set back. This is only
        // in-memory anyhow
        $CFG->showuseridentity = $oldshowuseridentity;
        $USER = $olduser;
    }

    public function test_userdate() {
        global $USER, $CFG, $DB;

        $olduser = $USER;
        $USER = $DB->get_record('user', array('id'=>2)); //admin

        $testvalues = array(
            array(
                'time' => '1309514400',
                'usertimezone' => 'America/Moncton',
                'timezone' => '0.0', //no dst offset
                'expectedoutput' => 'Friday, 1 July 2011, 10:00 AM'
            ),
            array(
                'time' => '1309514400',
                'usertimezone' => 'America/Moncton',
                'timezone' => '99', //dst offset and timezone offset.
                'expectedoutput' => 'Friday, 1 July 2011, 7:00 AM'
            ),
            array(
                'time' => '1309514400',
                'usertimezone' => 'America/Moncton',
                'timezone' => 'America/Moncton', //dst offset and timezone offset.
                'expectedoutput' => 'Friday, 1 July 2011, 7:00 AM'
            ),
            array(
                'time' => '1293876000 ',
                'usertimezone' => 'America/Moncton',
                'timezone' => '0.0', //no dst offset
                'expectedoutput' => 'Saturday, 1 January 2011, 10:00 AM'
            ),
            array(
                'time' => '1293876000 ',
                'usertimezone' => 'America/Moncton',
                'timezone' => '99', //no dst offset in jan, so just timezone offset.
                'expectedoutput' => 'Saturday, 1 January 2011, 6:00 AM'
            ),
            array(
                'time' => '1293876000 ',
                'usertimezone' => 'America/Moncton',
                'timezone' => 'America/Moncton', //no dst offset in jan
                'expectedoutput' => 'Saturday, 1 January 2011, 6:00 AM'
            ),
            array(
                'time' => '1293876000 ',
                'usertimezone' => '2',
                'timezone' => '99', //take user timezone
                'expectedoutput' => 'Saturday, 1 January 2011, 12:00 PM'
            ),
            array(
                'time' => '1293876000 ',
                'usertimezone' => '-2',
                'timezone' => '99', //take user timezone
                'expectedoutput' => 'Saturday, 1 January 2011, 8:00 AM'
            ),
            array(
                'time' => '1293876000 ',
                'usertimezone' => '-10',
                'timezone' => '2', //take this timezone
                'expectedoutput' => 'Saturday, 1 January 2011, 12:00 PM'
            ),
            array(
                'time' => '1293876000 ',
                'usertimezone' => '-10',
                'timezone' => '-2', //take this timezone
                'expectedoutput' => 'Saturday, 1 January 2011, 8:00 AM'
            ),
            array(
                'time' => '1293876000 ',
                'usertimezone' => '-10',
                'timezone' => 'random/time', //this should show server time
                'expectedoutput' => 'Saturday, 1 January 2011, 6:00 PM'
            ),
            array(
                'time' => '1293876000 ',
                'usertimezone' => '14', //server time zone
                'timezone' => '99', //this should show user time
                'expectedoutput' => 'Saturday, 1 January 2011, 6:00 PM'
            ),
        );

        //Check if forcetimezone is set then save it and set it to use user timezone
        $cfgforcetimezone = null;
        if (isset($CFG->forcetimezone)) {
            $cfgforcetimezone = $CFG->forcetimezone;
            $CFG->forcetimezone = 99; //get user default timezone.
        }
        //store user default timezone to restore later
        $userstimezone = $USER->timezone;

        // The string version of date comes from server locale setting and does
        // not respect user language, so it is necessary to reset that.
        $oldlocale = setlocale(LC_TIME, '0');
        setlocale(LC_TIME, 'en_AU.UTF-8');

        //set default timezone to Australia/Perth, else time calculated
        //will not match expected values. Before that save system defaults.
        $systemdefaulttimezone = date_default_timezone_get();
        date_default_timezone_set('Australia/Perth');

        foreach ($testvalues as $vals) {
            $USER->timezone = $vals['usertimezone'];
            $actualoutput = userdate($vals['time'], '%A, %d %B %Y, %I:%M %p', $vals['timezone']);

            //On different systems case of AM PM changes so compare case insensitive
            $vals['expectedoutput'] = textlib::strtolower($vals['expectedoutput']);
            $actualoutput = textlib::strtolower($actualoutput);

            $this->assertEquals($vals['expectedoutput'], $actualoutput,
                "Expected: {$vals['expectedoutput']} => Actual: {$actualoutput},
                Please check if timezones are updated (Site adminstration -> location -> update timezone)");
        }

        //restore user timezone back to what it was
        $USER->timezone = $userstimezone;

        //restore forcetimezone
        if (!is_null($cfgforcetimezone)) {
            $CFG->forcetimezone = $cfgforcetimezone;
        }

        //restore system default values.
        date_default_timezone_set($systemdefaulttimezone);
        setlocale(LC_TIME, $oldlocale);

        $USER = $olduser;
    }

    public function test_make_timestamp() {
        global $USER, $CFG, $DB;

        $olduser = $USER;
        $USER = $DB->get_record('user', array('id'=>2)); //admin

        $testvalues = array(
            array(
                'usertimezone' => 'America/Moncton',
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => '0.0', //no dst offset
                'applydst' => false,
                'expectedoutput' => '1309528800'
            ),
            array(
                'usertimezone' => 'America/Moncton',
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => '99', //user default timezone
                'applydst' => false, //don't apply dst
                'expectedoutput' => '1309528800'
            ),
            array(
                'usertimezone' => 'America/Moncton',
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => '99', //user default timezone
                'applydst' => true, //apply dst
                'expectedoutput' => '1309525200'
            ),
            array(
                'usertimezone' => 'America/Moncton',
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => 'America/Moncton', //string timezone
                'applydst' => true, //apply dst
                'expectedoutput' => '1309525200'
            ),
            array(
                'usertimezone' => '2',//no dst applyed
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => '99', //take user timezone
                'applydst' => true, //apply dst
                'expectedoutput' => '1309507200'
            ),
            array(
                'usertimezone' => '-2',//no dst applyed
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => '99', //take usertimezone
                'applydst' => true, //apply dst
                'expectedoutput' => '1309521600'
            ),
            array(
                'usertimezone' => '-10',//no dst applyed
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => '2', //take this timezone
                'applydst' => true, //apply dst
                'expectedoutput' => '1309507200'
            ),
            array(
                'usertimezone' => '-10',//no dst applyed
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => '-2', //take this timezone
                'applydst' => true, //apply dst,
                'expectedoutput' => '1309521600'
            ),
            array(
                'usertimezone' => '-10',//no dst applyed
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => 'random/time', //This should show server time
                'applydst' => true, //apply dst,
                'expectedoutput' => '1309485600'
            ),
            array(
                'usertimezone' => '14',//server time
                'year' => '2011',
                'month' => '7',
                'day' => '1',
                'hour' => '10',
                'minutes' => '00',
                'seconds' => '00',
                'timezone' => '99', //get user time
                'applydst' => true, //apply dst,
                'expectedoutput' => '1309485600'
            )
        );

        //Check if forcetimezone is set then save it and set it to use user timezone
        $cfgforcetimezone = null;
        if (isset($CFG->forcetimezone)) {
            $cfgforcetimezone = $CFG->forcetimezone;
            $CFG->forcetimezone = 99; //get user default timezone.
        }

        //store user default timezone to restore later
        $userstimezone = $USER->timezone;

        // The string version of date comes from server locale setting and does
        // not respect user language, so it is necessary to reset that.
        $oldlocale = setlocale(LC_TIME, '0');
        setlocale(LC_TIME, 'en_AU.UTF-8');

        //set default timezone to Australia/Perth, else time calulated
        //will not match expected values. Before that save system defaults.
        $systemdefaulttimezone = date_default_timezone_get();
        date_default_timezone_set('Australia/Perth');

        //Test make_timestamp with all testvals and assert if anything wrong.
        foreach ($testvalues as $vals) {
            $USER->timezone = $vals['usertimezone'];
            $actualoutput = make_timestamp(
                $vals['year'],
                $vals['month'],
                $vals['day'],
                $vals['hour'],
                $vals['minutes'],
                $vals['seconds'],
                $vals['timezone'],
                $vals['applydst']
            );

            //On different systems case of AM PM changes so compare case insenitive
            $vals['expectedoutput'] = textlib::strtolower($vals['expectedoutput']);
            $actualoutput = textlib::strtolower($actualoutput);

            $this->assertEquals($vals['expectedoutput'], $actualoutput,
                "Expected: {$vals['expectedoutput']} => Actual: {$actualoutput},
                Please check if timezones are updated (Site adminstration -> location -> update timezone)");
        }

        //restore user timezone back to what it was
        $USER->timezone = $userstimezone;

        //restore forcetimezone
        if (!is_null($cfgforcetimezone)) {
            $CFG->forcetimezone = $cfgforcetimezone;
        }

        //restore system default values.
        date_default_timezone_set($systemdefaulttimezone);
        setlocale(LC_TIME, $oldlocale);

        $USER = $olduser;
    }

    /**
     * Test get_string and most importantly the implementation of the lang_string
     * object.
     */
    public function test_get_string() {
        global $COURSE;

        // Make sure we are using English
        $originallang = $COURSE->lang;
        $COURSE->lang = 'en';

        $yes = get_string('yes');
        $yesexpected = 'Yes';
        $this->assertEquals(getType($yes), 'string');
        $this->assertEquals($yes, $yesexpected);

        $yes = get_string('yes', 'moodle');
        $this->assertEquals(getType($yes), 'string');
        $this->assertEquals($yes, $yesexpected);

        $yes = get_string('yes', 'core');
        $this->assertEquals(getType($yes), 'string');
        $this->assertEquals($yes, $yesexpected);

        $yes = get_string('yes', '');
        $this->assertEquals(getType($yes), 'string');
        $this->assertEquals($yes, $yesexpected);

        $yes = get_string('yes', null);
        $this->assertEquals(getType($yes), 'string');
        $this->assertEquals($yes, $yesexpected);

        $yes = get_string('yes', null, 1);
        $this->assertEquals(getType($yes), 'string');
        $this->assertEquals($yes, $yesexpected);

        $days = 1;
        $numdays = get_string('numdays', 'core', '1');
        $numdaysexpected = $days.' days';
        $this->assertEquals(getType($numdays), 'string');
        $this->assertEquals($numdays, $numdaysexpected);

        $yes = get_string('yes', null, null, true);
        $this->assertEquals(get_class($yes), 'lang_string');
        $this->assertEquals((string)$yes, $yesexpected);

        // Test using a lang_string object as the $a argument for a normal
        // get_string call (returning string)
        $test = new lang_string('yes', null, null, true);
        $testexpected = get_string('numdays', 'core', get_string('yes'));
        $testresult = get_string('numdays', null, $test);
        $this->assertEquals(getType($testresult), 'string');
        $this->assertEquals($testresult, $testexpected);

        // Test using a lang_string object as the $a argument for an object
        // get_string call (returning lang_string)
        $test = new lang_string('yes', null, null, true);
        $testexpected = get_string('numdays', 'core', get_string('yes'));
        $testresult = get_string('numdays', null, $test, true);
        $this->assertEquals(get_class($testresult), 'lang_string');
        $this->assertEquals("$testresult", $testexpected);

        // Make sure that object properties that can't be converted don't cause
        // errors
        // Level one: This is as deep as current language processing goes
        $test = new stdClass;
        $test->one = 'here';
        $string = get_string('yes', null, $test, true);
        $this->assertEquals($string, $yesexpected);

        // Make sure that object properties that can't be converted don't cause
        // errors.
        // Level two: Language processing doesn't currently reach this deep.
        // only immediate scalar properties are worked with.
        $test = new stdClass;
        $test->one = new stdClass;
        $test->one->two = 'here';
        $string = get_string('yes', null, $test, true);
        $this->assertEquals($string, $yesexpected);

        // Make sure that object properties that can't be converted don't cause
        // errors.
        // Level three: It should never ever go this deep, but we're making sure
        // it doesn't cause any probs anyway.
        $test = new stdClass;
        $test->one = new stdClass;
        $test->one->two = new stdClass;
        $test->one->two->three = 'here';
        $string = get_string('yes', null, $test, true);
        $this->assertEquals($string, $yesexpected);

        // Make sure that object properties that can't be converted don't cause
        // errors and check lang_string properties.
        // Level one: This is as deep as current language processing goes
        $test = new stdClass;
        $test->one = new lang_string('yes');
        $string = get_string('yes', null, $test, true);
        $this->assertEquals($string, $yesexpected);

        // Make sure that object properties that can't be converted don't cause
        // errors and check lang_string properties.
        // Level two: Language processing doesn't currently reach this deep.
        // only immediate scalar properties are worked with.
        $test = new stdClass;
        $test->one = new stdClass;
        $test->one->two = new lang_string('yes');
        $string = get_string('yes', null, $test, true);
        $this->assertEquals($string, $yesexpected);

        // Make sure that object properties that can't be converted don't cause
        // errors and check lang_string properties.
        // Level three: It should never ever go this deep, but we're making sure
        // it doesn't cause any probs anyway.
        $test = new stdClass;
        $test->one = new stdClass;
        $test->one->two = new stdClass;
        $test->one->two->three = new lang_string('yes');
        $string = get_string('yes', null, $test, true);
        $this->assertEquals($string, $yesexpected);

        // Make sure that array properties that can't be converted don't cause
        // errors
        $test = array();
        $test['one'] = new stdClass;
        $test['one']->two = 'here';
        $string = get_string('yes', null, $test, true);
        $this->assertEquals($string, $yesexpected);

        // Same thing but as above except using an object... this is allowed :P
        $string = get_string('yes', null, null, true);
        $object = new stdClass;
        $object->$string = 'Yes';
        $this->assertEquals($string, $yesexpected);
        $this->assertEquals($object->$string, $yesexpected);

        // Reset the language
        $COURSE->lang = $originallang;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @return void
     */
    public function test_get_string_limitation() {
        // This is one of the limitations to the lang_string class. It can't be
        // used as a key
        $array = array(get_string('yes', null, null, true) => 'yes');
    }

    /**
     * Test localised float formatting.
     */
    public function test_format_float() {
        global $SESSION, $CFG;

        // Special case for null
        $this->assertEquals('', format_float(null));

        // Default 1 decimal place
        $this->assertEquals('5.4', format_float(5.43));
        $this->assertEquals('5.0', format_float(5.001));

        // Custom number of decimal places
        $this->assertEquals('5.43000', format_float(5.43, 5));

        // Option to strip ending zeros after rounding
        $this->assertEquals('5.43', format_float(5.43, 5, true, true));
        $this->assertEquals('5', format_float(5.0001, 3, true, true));

        // It is not possible to directly change the result of get_string in
        // a unit test. Instead, we create a language pack for language 'xx' in
        // dataroot and make langconfig.php with the string we need to change.
        // The example separator used here is 'X'; on PHP 5.3 and before this
        // must be a single byte character due to PHP bug/limitation in
        // number_format, so you can't use UTF-8 characters.
        $SESSION->lang = 'xx';
        $langconfig = "<?php\n\$string['decsep'] = 'X';";
        $langfolder = $CFG->dataroot . '/lang/xx';
        check_dir_exists($langfolder);
        file_put_contents($langfolder . '/langconfig.php', $langconfig);

        // Localisation on (default)
        $this->assertEquals('5X43000', format_float(5.43, 5));
        $this->assertEquals('5X43', format_float(5.43, 5, true, true));

        // Localisation off
        $this->assertEquals('5.43000', format_float(5.43, 5, false));
        $this->assertEquals('5.43', format_float(5.43, 5, false, true));
    }
}
