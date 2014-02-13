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
 * Unit tests for /lib/filelib.php.
 *
 * @package   core_files
 * @category  phpunit
 * @copyright 2009 Jerome Mouneyrac
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/repository/lib.php');

class core_filelib_testcase extends advanced_testcase {
    public function test_format_postdata_for_curlcall() {

        // POST params with just simple types.
        $postdatatoconvert = array( 'userid' => 1, 'roleid' => 22, 'name' => 'john');
        $expectedresult = "userid=1&roleid=22&name=john";
        $postdata = format_postdata_for_curlcall($postdatatoconvert);
        $this->assertEquals($expectedresult, $postdata);

        // POST params with a string containing & character.
        $postdatatoconvert = array( 'name' => 'john&emilie', 'roleid' => 22);
        $expectedresult = "name=john%26emilie&roleid=22"; // Urlencode: '%26' => '&'.
        $postdata = format_postdata_for_curlcall($postdatatoconvert);
        $this->assertEquals($expectedresult, $postdata);

        // POST params with an empty value.
        $postdatatoconvert = array( 'name' => null, 'roleid' => 22);
        $expectedresult = "name=&roleid=22";
        $postdata = format_postdata_for_curlcall($postdatatoconvert);
        $this->assertEquals($expectedresult, $postdata);

        // POST params with complex types.
        $postdatatoconvert = array( 'users' => array(
            array(
                'id' => 2,
                'customfields' => array(
                    array
                    (
                        'type' => 'Color',
                        'value' => 'violet'
                    )
                )
            )
        )
        );
        $expectedresult = "users[0][id]=2&users[0][customfields][0][type]=Color&users[0][customfields][0][value]=violet";
        $postdata = format_postdata_for_curlcall($postdatatoconvert);
        $this->assertEquals($expectedresult, $postdata);

        // POST params with other complex types.
        $postdatatoconvert = array ('members' =>
        array(
            array('groupid' => 1, 'userid' => 1)
        , array('groupid' => 1, 'userid' => 2)
        )
        );
        $expectedresult = "members[0][groupid]=1&members[0][userid]=1&members[1][groupid]=1&members[1][userid]=2";
        $postdata = format_postdata_for_curlcall($postdatatoconvert);
        $this->assertEquals($expectedresult, $postdata);
    }

    public function test_download_file_content() {
        global $CFG;

        // Test http success first.
        $testhtml = $this->getExternalTestFileUrl('/test.html');

        $contents = download_file_content($testhtml);
        $this->assertSame('47250a973d1b88d9445f94db4ef2c97a', md5($contents));

        $tofile = "$CFG->tempdir/test.html";
        @unlink($tofile);
        $result = download_file_content($testhtml, null, null, false, 300, 20, false, $tofile);
        $this->assertTrue($result);
        $this->assertFileExists($tofile);
        $this->assertSame(file_get_contents($tofile), $contents);
        @unlink($tofile);

        $result = download_file_content($testhtml, null, null, false, 300, 20, false, null, true);
        $this->assertSame($contents, $result);

        $response = download_file_content($testhtml, null, null, true);
        $this->assertInstanceOf('stdClass', $response);
        $this->assertSame('200', $response->status);
        $this->assertTrue(is_array($response->headers));
        $this->assertRegExp('|^HTTP/1\.[01] 200 OK$|', rtrim($response->response_code));
        $this->assertSame($contents, $response->results);
        $this->assertSame('', $response->error);

        // Test https success.
        $testhtml = $this->getExternalTestFileUrl('/test.html', true);

        $contents = download_file_content($testhtml, null, null, false, 300, 20, true);
        $this->assertSame('47250a973d1b88d9445f94db4ef2c97a', md5($contents));

        $contents = download_file_content($testhtml);
        $this->assertSame('47250a973d1b88d9445f94db4ef2c97a', md5($contents));

        // Now 404.
        $testhtml = $this->getExternalTestFileUrl('/test.html_nonexistent');

        $contents = download_file_content($testhtml);
        $this->assertFalse($contents);
        $this->assertDebuggingCalled();

        $response = download_file_content($testhtml, null, null, true);
        $this->assertInstanceOf('stdClass', $response);
        $this->assertSame('404', $response->status);
        $this->assertTrue(is_array($response->headers));
        $this->assertRegExp('|^HTTP/1\.[01] 404 Not Found$|', rtrim($response->response_code));
        // Do not test the response starts with DOCTYPE here because some servers may return different headers.
        $this->assertSame('', $response->error);

        // Invalid url.
        $testhtml = $this->getExternalTestFileUrl('/test.html');
        $testhtml = str_replace('http://', 'ftp://', $testhtml);

        $contents = download_file_content($testhtml);
        $this->assertFalse($contents);

        // Test standard redirects.
        $testurl = $this->getExternalTestFileUrl('/test_redir.php');

        $contents = download_file_content("$testurl?redir=2");
        $this->assertSame('done', $contents);

        $response = download_file_content("$testurl?redir=2", null, null, true);
        $this->assertInstanceOf('stdClass', $response);
        $this->assertSame('200', $response->status);
        $this->assertTrue(is_array($response->headers));
        $this->assertRegExp('|^HTTP/1\.[01] 200 OK$|', rtrim($response->response_code));
        $this->assertSame('done', $response->results);
        $this->assertSame('', $response->error);

        // Commented out this block if there are performance problems.
        /*
        $contents = download_file_content("$testurl?redir=6");
        $this->assertFalse(false, $contents);
        $this->assertDebuggingCalled();
        $response = download_file_content("$testurl?redir=6", null, null, true);
        $this->assertInstanceOf('stdClass', $response);
        $this->assertSame('0', $response->status);
        $this->assertTrue(is_array($response->headers));
        $this->assertFalse($response->results);
        $this->assertNotEmpty($response->error);
        */

        // Test relative redirects.
        $testurl = $this->getExternalTestFileUrl('/test_relative_redir.php');

        $contents = download_file_content("$testurl");
        $this->assertSame('done', $contents);

        $contents = download_file_content("$testurl?unused=xxx");
        $this->assertSame('done', $contents);
    }

    /**
     * Test curl basics.
     */
    public function test_curl_basics() {
        global $CFG;

        // Test HTTP success.
        $testhtml = $this->getExternalTestFileUrl('/test.html');

        $curl = new curl();
        $contents = $curl->get($testhtml);
        $this->assertSame('47250a973d1b88d9445f94db4ef2c97a', md5($contents));
        $this->assertSame(0, $curl->get_errno());

        $curl = new curl();
        $tofile = "$CFG->tempdir/test.html";
        @unlink($tofile);
        $fp = fopen($tofile, 'w');
        $result = $curl->get($testhtml, array(), array('CURLOPT_FILE'=>$fp));
        $this->assertTrue($result);
        fclose($fp);
        $this->assertFileExists($tofile);
        $this->assertSame($contents, file_get_contents($tofile));
        @unlink($tofile);

        $curl = new curl();
        $tofile = "$CFG->tempdir/test.html";
        @unlink($tofile);
        $result = $curl->download_one($testhtml, array(), array('filepath'=>$tofile));
        $this->assertTrue($result);
        $this->assertFileExists($tofile);
        $this->assertSame($contents, file_get_contents($tofile));
        @unlink($tofile);

        // Test 404 request.
        $curl = new curl();
        $contents = $curl->get($this->getExternalTestFileUrl('/i.do.not.exist'));
        $response = $curl->getResponse();
        $this->assertSame('404 Not Found', reset($response));
        $this->assertSame(0, $curl->get_errno());
    }

    public function test_curl_redirects() {
        global $CFG;

        // Test full URL redirects.
        $testurl = $this->getExternalTestFileUrl('/test_redir.php');

        $curl = new curl();
        $contents = $curl->get("$testurl?redir=2", array(), array('CURLOPT_MAXREDIRS'=>2));
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(2, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $curl->emulateredirects = true;
        $contents = $curl->get("$testurl?redir=2", array(), array('CURLOPT_MAXREDIRS'=>2));
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(2, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $contents = $curl->get("$testurl?redir=3", array(), array('CURLOPT_FOLLOWLOCATION'=>0));
        $response = $curl->getResponse();
        $this->assertSame('302 Found', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(302, $curl->info['http_code']);
        $this->assertSame('', $contents);

        $curl = new curl();
        $curl->emulateredirects = true;
        $contents = $curl->get("$testurl?redir=3", array(), array('CURLOPT_FOLLOWLOCATION'=>0));
        $response = $curl->getResponse();
        $this->assertSame('302 Found', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(302, $curl->info['http_code']);
        $this->assertSame('', $contents);

        $curl = new curl();
        $contents = $curl->get("$testurl?redir=2", array(), array('CURLOPT_MAXREDIRS'=>1));
        $this->assertSame(CURLE_TOO_MANY_REDIRECTS, $curl->get_errno());
        $this->assertNotEmpty($contents);

        $curl = new curl();
        $curl->emulateredirects = true;
        $contents = $curl->get("$testurl?redir=2", array(), array('CURLOPT_MAXREDIRS'=>1));
        $this->assertSame(CURLE_TOO_MANY_REDIRECTS, $curl->get_errno());
        $this->assertNotEmpty($contents);

        $curl = new curl();
        $tofile = "$CFG->tempdir/test.html";
        @unlink($tofile);
        $fp = fopen($tofile, 'w');
        $result = $curl->get("$testurl?redir=1", array(), array('CURLOPT_FILE'=>$fp));
        $this->assertTrue($result);
        fclose($fp);
        $this->assertFileExists($tofile);
        $this->assertSame('done', file_get_contents($tofile));
        @unlink($tofile);

        $curl = new curl();
        $curl->emulateredirects = true;
        $tofile = "$CFG->tempdir/test.html";
        @unlink($tofile);
        $fp = fopen($tofile, 'w');
        $result = $curl->get("$testurl?redir=1", array(), array('CURLOPT_FILE'=>$fp));
        $this->assertTrue($result);
        fclose($fp);
        $this->assertFileExists($tofile);
        $this->assertSame('done', file_get_contents($tofile));
        @unlink($tofile);

        $curl = new curl();
        $tofile = "$CFG->tempdir/test.html";
        @unlink($tofile);
        $result = $curl->download_one("$testurl?redir=1", array(), array('filepath'=>$tofile));
        $this->assertTrue($result);
        $this->assertFileExists($tofile);
        $this->assertSame('done', file_get_contents($tofile));
        @unlink($tofile);

        $curl = new curl();
        $curl->emulateredirects = true;
        $tofile = "$CFG->tempdir/test.html";
        @unlink($tofile);
        $result = $curl->download_one("$testurl?redir=1", array(), array('filepath'=>$tofile));
        $this->assertTrue($result);
        $this->assertFileExists($tofile);
        $this->assertSame('done', file_get_contents($tofile));
        @unlink($tofile);
    }

    public function test_curl_relative_redirects() {
        // Test relative location redirects.
        $testurl = $this->getExternalTestFileUrl('/test_relative_redir.php');

        $curl = new curl();
        $contents = $curl->get($testurl);
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $curl->emulateredirects = true;
        $contents = $curl->get($testurl);
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        // Test different redirect types.
        $testurl = $this->getExternalTestFileUrl('/test_relative_redir.php');

        $curl = new curl();
        $contents = $curl->get("$testurl?type=301");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $curl->emulateredirects = true;
        $contents = $curl->get("$testurl?type=301");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $contents = $curl->get("$testurl?type=302");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $curl->emulateredirects = true;
        $contents = $curl->get("$testurl?type=302");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $contents = $curl->get("$testurl?type=303");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $curl->emulateredirects = true;
        $contents = $curl->get("$testurl?type=303");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $contents = $curl->get("$testurl?type=307");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $curl->emulateredirects = true;
        $contents = $curl->get("$testurl?type=307");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $contents = $curl->get("$testurl?type=308");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

        $curl = new curl();
        $curl->emulateredirects = true;
        $contents = $curl->get("$testurl?type=308");
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame(1, $curl->info['redirect_count']);
        $this->assertSame('done', $contents);

    }

    public function test_curl_proxybypass() {
        global $CFG;
        $testurl = $this->getExternalTestFileUrl('/test.html');

        $oldproxy = $CFG->proxyhost;
        $oldproxybypass = $CFG->proxybypass;

        // Test without proxy bypass and inaccessible proxy.
        $CFG->proxyhost = 'i.do.not.exist';
        $CFG->proxybypass = '';
        $curl = new curl();
        $contents = $curl->get($testurl);
        $this->assertNotEquals(0, $curl->get_errno());
        $this->assertNotEquals('47250a973d1b88d9445f94db4ef2c97a', md5($contents));

        // Test with proxy bypass.
        $testurlhost = parse_url($testurl, PHP_URL_HOST);
        $CFG->proxybypass = $testurlhost;
        $curl = new curl();
        $contents = $curl->get($testurl);
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame('47250a973d1b88d9445f94db4ef2c97a', md5($contents));

        $CFG->proxyhost = $oldproxy;
        $CFG->proxybypass = $oldproxybypass;
    }

    public function test_curl_post() {
        $testurl = $this->getExternalTestFileUrl('/test_post.php');

        // Test post request.
        $curl = new curl();
        $contents = $curl->post($testurl, 'data=moodletest');
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame('OK', $contents);

        // Test 100 requests.
        $curl = new curl();
        $curl->setHeader('Expect: 100-continue');
        $contents = $curl->post($testurl, 'data=moodletest');
        $response = $curl->getResponse();
        $this->assertSame('200 OK', reset($response));
        $this->assertSame(0, $curl->get_errno());
        $this->assertSame('OK', $contents);
    }

    /**
     * Testing prepare draft area
     *
     * @copyright 2012 Dongsheng Cai {@link http://dongsheng.org}
     * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    public function test_prepare_draft_area() {
        global $USER, $DB;

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $usercontext = context_user::instance($user->id);
        $USER = $DB->get_record('user', array('id'=>$user->id));

        $repositorypluginname = 'user';

        $args = array();
        $args['type'] = $repositorypluginname;
        $repos = repository::get_instances($args);
        $userrepository = reset($repos);
        $this->assertInstanceOf('repository', $userrepository);

        $fs = get_file_storage();

        $syscontext = context_system::instance();
        $component = 'core';
        $filearea  = 'unittest';
        $itemid    = 0;
        $filepath  = '/';
        $filename  = 'test.txt';
        $sourcefield = 'Copyright stuff';

        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => $component,
            'filearea'  => $filearea,
            'itemid'    => $itemid,
            'filepath'  => $filepath,
            'filename'  => $filename,
            'source'    => $sourcefield,
        );
        $ref = $fs->pack_reference($filerecord);
        $originalfile = $fs->create_file_from_string($filerecord, 'Test content');
        $fileid = $originalfile->get_id();
        $this->assertInstanceOf('stored_file', $originalfile);

        // Create a user private file.
        $userfilerecord = new stdClass;
        $userfilerecord->contextid = $usercontext->id;
        $userfilerecord->component = 'user';
        $userfilerecord->filearea  = 'private';
        $userfilerecord->itemid    = 0;
        $userfilerecord->filepath  = '/';
        $userfilerecord->filename  = 'userfile.txt';
        $userfilerecord->source    = 'test';
        $userfile = $fs->create_file_from_string($userfilerecord, 'User file content');
        $userfileref = $fs->pack_reference($userfilerecord);

        $filerefrecord = clone((object)$filerecord);
        $filerefrecord->filename = 'testref.txt';

        // Create a file reference.
        $fileref = $fs->create_file_from_reference($filerefrecord, $userrepository->id, $userfileref);
        $this->assertInstanceOf('stored_file', $fileref);
        $this->assertEquals($userrepository->id, $fileref->get_repository_id());
        $this->assertSame($userfile->get_contenthash(), $fileref->get_contenthash());
        $this->assertEquals($userfile->get_filesize(), $fileref->get_filesize());
        $this->assertRegExp('#' . $userfile->get_filename(). '$#', $fileref->get_reference_details());

        $draftitemid = 0;
        file_prepare_draft_area($draftitemid, $syscontext->id, $component, $filearea, $itemid);

        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid);
        $this->assertCount(3, $draftfiles);

        $draftfile = $fs->get_file($usercontext->id, 'user', 'draft', $draftitemid, $filepath, $filename);
        $source = unserialize($draftfile->get_source());
        $this->assertSame($ref, $source->original);
        $this->assertSame($sourcefield, $source->source);

        $draftfileref = $fs->get_file($usercontext->id, 'user', 'draft', $draftitemid, $filepath, $filerefrecord->filename);
        $this->assertInstanceOf('stored_file', $draftfileref);
        $this->assertTrue($draftfileref->is_external_file());

        // Change some information.
        $author = 'Dongsheng Cai';
        $draftfile->set_author($author);
        $newsourcefield = 'Get from Flickr';
        $license = 'GPLv3';
        $draftfile->set_license($license);
        // If you want to really just change source field, do this.
        $source = unserialize($draftfile->get_source());
        $newsourcefield = 'From flickr';
        $source->source = $newsourcefield;
        $draftfile->set_source(serialize($source));

        // Save changed file.
        file_save_draft_area_files($draftitemid, $syscontext->id, $component, $filearea, $itemid);

        $file = $fs->get_file($syscontext->id, $component, $filearea, $itemid, $filepath, $filename);

        // Make sure it's the original file id.
        $this->assertEquals($fileid, $file->get_id());
        $this->assertInstanceOf('stored_file', $file);
        $this->assertSame($author, $file->get_author());
        $this->assertSame($license, $file->get_license());
        $this->assertEquals($newsourcefield, $file->get_source());
    }

    /**
     * Testing deleting original files.
     *
     * @copyright 2012 Dongsheng Cai {@link http://dongsheng.org}
     * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    public function test_delete_original_file_from_draft() {
        global $USER, $DB;

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $usercontext = context_user::instance($user->id);
        $USER = $DB->get_record('user', array('id'=>$user->id));

        $repositorypluginname = 'user';

        $args = array();
        $args['type'] = $repositorypluginname;
        $repos = repository::get_instances($args);
        $userrepository = reset($repos);
        $this->assertInstanceOf('repository', $userrepository);

        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $filecontent = 'User file content';

        // Create a user private file.
        $userfilerecord = new stdClass;
        $userfilerecord->contextid = $usercontext->id;
        $userfilerecord->component = 'user';
        $userfilerecord->filearea  = 'private';
        $userfilerecord->itemid    = 0;
        $userfilerecord->filepath  = '/';
        $userfilerecord->filename  = 'userfile.txt';
        $userfilerecord->source    = 'test';
        $userfile = $fs->create_file_from_string($userfilerecord, $filecontent);
        $userfileref = $fs->pack_reference($userfilerecord);
        $contenthash = $userfile->get_contenthash();

        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'core',
            'filearea'  => 'phpunit',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => 'test.txt',
        );
        // Create a file reference.
        $fileref = $fs->create_file_from_reference($filerecord, $userrepository->id, $userfileref);
        $this->assertInstanceOf('stored_file', $fileref);
        $this->assertEquals($userrepository->id, $fileref->get_repository_id());
        $this->assertSame($userfile->get_contenthash(), $fileref->get_contenthash());
        $this->assertEquals($userfile->get_filesize(), $fileref->get_filesize());
        $this->assertRegExp('#' . $userfile->get_filename(). '$#', $fileref->get_reference_details());

        $draftitemid = 0;
        file_prepare_draft_area($draftitemid, $usercontext->id, 'user', 'private', 0);
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid);
        $this->assertCount(2, $draftfiles);
        $draftfile = $fs->get_file($usercontext->id, 'user', 'draft', $draftitemid, $userfilerecord->filepath, $userfilerecord->filename);
        $draftfile->delete();
        // Save changed file.
        file_save_draft_area_files($draftitemid, $usercontext->id, 'user', 'private', 0);

        // The file reference should be a regular moodle file now.
        $fileref = $fs->get_file($syscontext->id, 'core', 'phpunit', 0, '/', 'test.txt');
        $this->assertFalse($fileref->is_external_file());
        $this->assertSame($contenthash, $fileref->get_contenthash());
        $this->assertEquals($filecontent, $fileref->get_content());
    }

    /**
     * Tests the strip_double_headers function in the curl class.
     */
    public function test_curl_strip_double_headers() {
        // Example from issue tracker.
        $mdl30648example = <<<EOF
HTTP/1.0 407 Proxy Authentication Required
Server: squid/2.7.STABLE9
Date: Thu, 08 Dec 2011 14:44:33 GMT
Content-Type: text/html
Content-Length: 1275
X-Squid-Error: ERR_CACHE_ACCESS_DENIED 0
Proxy-Authenticate: Basic realm="Squid proxy-caching web server"
X-Cache: MISS from homer.lancs.ac.uk
X-Cache-Lookup: NONE from homer.lancs.ac.uk:3128
Via: 1.0 homer.lancs.ac.uk:3128 (squid/2.7.STABLE9)
Connection: close

HTTP/1.0 200 OK
Server: Apache
X-Lb-Nocache: true
Cache-Control: private, max-age=15, no-transform
ETag: "4d69af5d8ba873ea9192c489e151bd7b"
Content-Type: text/html
Date: Thu, 08 Dec 2011 14:44:53 GMT
Set-Cookie: BBC-UID=c4de2e109c8df6a51de627cee11b214bd4fb6054a030222488317afb31b343360MoodleBot/1.0; expires=Mon, 07-Dec-15 14:44:53 GMT; path=/; domain=bbc.co.uk
X-Cache-Action: MISS
X-Cache-Age: 0
Vary: Cookie,X-Country,X-Ip-is-uk-combined,X-Ip-is-advertise-combined,X-Ip_is_uk_combined,X-Ip_is_advertise_combined, X-GeoIP
X-Cache: MISS from ww

<html>...
EOF;
        $mdl30648expected = <<<EOF
HTTP/1.0 200 OK
Server: Apache
X-Lb-Nocache: true
Cache-Control: private, max-age=15, no-transform
ETag: "4d69af5d8ba873ea9192c489e151bd7b"
Content-Type: text/html
Date: Thu, 08 Dec 2011 14:44:53 GMT
Set-Cookie: BBC-UID=c4de2e109c8df6a51de627cee11b214bd4fb6054a030222488317afb31b343360MoodleBot/1.0; expires=Mon, 07-Dec-15 14:44:53 GMT; path=/; domain=bbc.co.uk
X-Cache-Action: MISS
X-Cache-Age: 0
Vary: Cookie,X-Country,X-Ip-is-uk-combined,X-Ip-is-advertise-combined,X-Ip_is_uk_combined,X-Ip_is_advertise_combined, X-GeoIP
X-Cache: MISS from ww

<html>...
EOF;
        // For HTTP, replace the \n with \r\n.
        $mdl30648example = preg_replace("~(?!<\r)\n~", "\r\n", $mdl30648example);
        $mdl30648expected = preg_replace("~(?!<\r)\n~", "\r\n", $mdl30648expected);

        // Test stripping works OK.
        $this->assertSame($mdl30648expected, curl::strip_double_headers($mdl30648example));
        // Test it does nothing to the 'plain' data.
        $this->assertSame($mdl30648expected, curl::strip_double_headers($mdl30648expected));

        // Example from OU proxy.
        $httpsexample = <<<EOF
HTTP/1.0 200 Connection established

HTTP/1.1 200 OK
Date: Fri, 22 Feb 2013 17:14:23 GMT
Server: Apache/2
X-Powered-By: PHP/5.3.3-7+squeeze14
Content-Type: text/xml
Connection: close
Content-Encoding: gzip
Transfer-Encoding: chunked

<?xml version="1.0" encoding="ISO-8859-1" ?>
<rss version="2.0">...
EOF;
        $httpsexpected = <<<EOF
HTTP/1.1 200 OK
Date: Fri, 22 Feb 2013 17:14:23 GMT
Server: Apache/2
X-Powered-By: PHP/5.3.3-7+squeeze14
Content-Type: text/xml
Connection: close
Content-Encoding: gzip
Transfer-Encoding: chunked

<?xml version="1.0" encoding="ISO-8859-1" ?>
<rss version="2.0">...
EOF;
        // For HTTP, replace the \n with \r\n.
        $httpsexample = preg_replace("~(?!<\r)\n~", "\r\n", $httpsexample);
        $httpsexpected = preg_replace("~(?!<\r)\n~", "\r\n", $httpsexpected);

        // Test stripping works OK.
        $this->assertSame($httpsexpected, curl::strip_double_headers($httpsexample));
        // Test it does nothing to the 'plain' data.
        $this->assertSame($httpsexpected, curl::strip_double_headers($httpsexpected));
    }
}
