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
 * Unit tests for /lib/filestorage/file_storage.php
 *
 * @package   core
 * @category  test
 * @copyright 2012 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/repository/lib.php');

class filestoragelib_testcase extends advanced_testcase {

    /**
     * Local files can be added to the filepool
     */
    public function test_create_file_from_pathname() {
        global $CFG;

        $this->resetAfterTest(false);

        $filepath = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';
        $syscontext = context_system::instance();
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'core',
            'filearea'  => 'unittest',
            'itemid'    => 0,
            'filepath'  => '/images/',
            'filename'  => 'testimage.jpg',
        );

        $fs = get_file_storage();
        $fs->create_file_from_pathname($filerecord, $filepath);

        $this->assertTrue($fs->file_exists($syscontext->id, 'core', 'unittest', 0, '/images/', 'testimage.jpg'));

        return $fs->get_file($syscontext->id, 'core', 'unittest', 0, '/images/', 'testimage.jpg');
    }

    /**
     * Local images can be added to the filepool and their preview can be obtained
     *
     * @depends test_create_file_from_pathname
     */
    public function test_get_file_preview(stored_file $file) {
        global $CFG;

        $this->resetAfterTest(true);
        $fs = get_file_storage();

        $previewtinyicon = $fs->get_file_preview($file, 'tinyicon');
        $this->assertInstanceOf('stored_file', $previewtinyicon);
        $this->assertEquals('6b9864ae1536a8eeef54e097319175a8be12f07c', $previewtinyicon->get_filename());

        $previewtinyicon = $fs->get_file_preview($file, 'thumb');
        $this->assertInstanceOf('stored_file', $previewtinyicon);
        $this->assertEquals('6b9864ae1536a8eeef54e097319175a8be12f07c', $previewtinyicon->get_filename());

        $this->setExpectedException('file_exception');
        $fs->get_file_preview($file, 'amodewhichdoesntexist');
    }

    public function test_get_file_preview_nonimage() {
        $this->resetAfterTest(true);
        $syscontext = context_system::instance();
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'core',
            'filearea'  => 'unittest',
            'itemid'    => 0,
            'filepath'  => '/textfiles/',
            'filename'  => 'testtext.txt',
        );

        $fs = get_file_storage();
        $fs->create_file_from_string($filerecord, 'text contents');
        $textfile = $fs->get_file($syscontext->id, $filerecord['component'], $filerecord['filearea'],
            $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename']);

        $preview = $fs->get_file_preview($textfile, 'thumb');
        $this->assertFalse($preview);
    }

    /**
     * Make sure renaming is working
     *
     * @copyright 2012 Dongsheng Cai {@link http://dongsheng.org}
     */
    public function test_file_renaming() {
        global $CFG;

        $this->resetAfterTest(true);
        $fs = get_file_storage();
        $syscontext = context_system::instance();
        $component = 'core';
        $filearea  = 'unittest';
        $itemid    = 0;
        $filepath  = '/';
        $filename  = 'test.txt';

        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => $component,
            'filearea'  => $filearea,
            'itemid'    => $itemid,
            'filepath'  => $filepath,
            'filename'  => $filename,
        );

        $originalfile = $fs->create_file_from_string($filerecord, 'Test content');
        $this->assertInstanceOf('stored_file', $originalfile);
        $contenthash = $originalfile->get_contenthash();
        $newpath = '/test/';
        $newname = 'newtest.txt';

        // this should work
        $originalfile->rename($newpath, $newname);
        $file = $fs->get_file($syscontext->id, $component, $filearea, $itemid, $newpath, $newname);
        $this->assertInstanceOf('stored_file', $file);
        $this->assertEquals($contenthash, $file->get_contenthash());

        // try break it
        $this->setExpectedException('file_exception');
        // this shall throw exception
        $originalfile->rename($newpath, $newname);
    }

    /**
     * Create file from reference tests
     *
     * @copyright 2012 Dongsheng Cai {@link http://dongsheng.org}
     */
    public function test_create_file_from_reference() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        // create user
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);
        $usercontext = context_user::instance($user->id);
        $syscontext = context_system::instance();

        $fs = get_file_storage();

        $repositorypluginname = 'user';
        // override repository permission
        $capability = 'repository/' . $repositorypluginname . ':view';
        $allroles = $DB->get_records_menu('role', array(), 'id', 'archetype, id');
        assign_capability($capability, CAP_ALLOW, $allroles['guest'], $syscontext->id, true);


        $args = array();
        $args['type'] = $repositorypluginname;
        $repos = repository::get_instances($args);
        $userrepository = reset($repos);
        $this->assertInstanceOf('repository', $userrepository);

        $component = 'user';
        $filearea  = 'private';
        $itemid    = 0;
        $filepath  = '/';
        $filename  = 'userfile.txt';

        $filerecord = array(
            'contextid' => $usercontext->id,
            'component' => $component,
            'filearea'  => $filearea,
            'itemid'    => $itemid,
            'filepath'  => $filepath,
            'filename'  => $filename,
        );

        $content = 'Test content';
        $originalfile = $fs->create_file_from_string($filerecord, $content);
        $this->assertInstanceOf('stored_file', $originalfile);

        $newfilerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'core',
            'filearea'  => 'phpunit',
            'itemid'    => 0,
            'filepath'  => $filepath,
            'filename'  => $filename,
        );
        $ref = $fs->pack_reference($filerecord);
        $newstoredfile = $fs->create_file_from_reference($newfilerecord, $userrepository->id, $ref);
        $this->assertInstanceOf('stored_file', $newstoredfile);
        $this->assertEquals($userrepository->id, $newstoredfile->get_repository_id());
        $this->assertEquals($originalfile->get_contenthash(), $newstoredfile->get_contenthash());
        $this->assertEquals($originalfile->get_filesize(), $newstoredfile->get_filesize());
        $this->assertRegExp('#' . $filename. '$#', $newstoredfile->get_reference_details());

        // Test looking for references
        $count = $fs->get_references_count_by_storedfile($originalfile);
        $this->assertEquals(1, $count);
        $files = $fs->get_references_by_storedfile($originalfile);
        $file = reset($files);
        $this->assertEquals($file, $newstoredfile);

        // Look for references by repository ID
        $files = $fs->get_external_files($userrepository->id);
        $file = reset($files);
        $this->assertEquals($file, $newstoredfile);

        // Try convert reference to local file
        $importedfile = $fs->import_external_file($newstoredfile);
        $this->assertFalse($importedfile->is_external_file());
        $this->assertInstanceOf('stored_file', $importedfile);
        // still readable?
        $this->assertEquals($content, $importedfile->get_content());
    }

    private function setup_three_private_files() {

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user->id);
        $usercontext = context_user::instance($user->id);
        // create a user private file
        $file1 = new stdClass;
        $file1->contextid = $usercontext->id;
        $file1->component = 'user';
        $file1->filearea  = 'private';
        $file1->itemid    = 0;
        $file1->filepath  = '/';
        $file1->filename  = '1.txt';
        $file1->source    = 'test';

        $fs = get_file_storage();
        $userfile1 = $fs->create_file_from_string($file1, 'file1 content');
        $this->assertInstanceOf('stored_file', $userfile1);

        $file2 = clone($file1);
        $file2->filename = '2.txt';
        $userfile2 = $fs->create_file_from_string($file2, 'file2 content');
        $this->assertInstanceOf('stored_file', $userfile2);

        $file3 = clone($file1);
        $file3->filename = '3.txt';
        $userfile3 = $fs->create_file_from_storedfile($file3, $userfile2);
        $this->assertInstanceOf('stored_file', $userfile3);

        $user->ctxid = $usercontext->id;

        return $user;
    }

    public function test_get_area_files() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();

        // Get area files with default options.
        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private');

        // Should be the two files we added plus the folder.
        $this->assertEquals(4, count($areafiles));

        // Verify structure.
        foreach ($areafiles as $key => $file) {
            $this->assertInstanceOf('stored_file', $file);
            $this->assertEquals($key, $file->get_pathnamehash());
        }

        // Get area files without a folder.
        $folderlessfiles = $fs->get_area_files($user->ctxid, 'user', 'private', false, 'sortorder', false);
        // Should be the two files without folder.
        $this->assertEquals(3, count($folderlessfiles));

        // Verify structure.
        foreach ($folderlessfiles as $key => $file) {
            $this->assertInstanceOf('stored_file', $file);
            $this->assertEquals($key, $file->get_pathnamehash());
        }

        // Get area files ordered by id.
        $filesbyid  = $fs->get_area_files($user->ctxid, 'user', 'private', false, 'id', false);
        // Should be the two files without folder.
        $this->assertEquals(3, count($filesbyid));

        // Verify structure.
        foreach ($filesbyid as $key => $file) {
            $this->assertInstanceOf('stored_file', $file);
            $this->assertEquals($key, $file->get_pathnamehash());
        }

        // Test with an itemid with no files
        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private', 666, 'sortorder', false);
        // Should be none.
        $this->assertEmpty($areafiles);
    }

    public function test_get_area_tree() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();


        // Get area files with default options.
        $areatree = $fs->get_area_tree($user->ctxid, 'user', 'private', 0);
        $this->assertEmpty($areatree['subdirs']);
        $this->assertNotEmpty($areatree['files']);
        $this->assertCount(3, $areatree['files']);

        // Ensure an empty try with a fake itemid.
        $emptytree = $fs->get_area_tree($user->ctxid, 'user', 'private', 666);
        $this->assertEmpty($emptytree['subdirs']);
        $this->assertEmpty($emptytree['files']);

        // Create a subdir.
        $dir = $fs->create_directory($user->ctxid, 'user', 'private', 0, '/testsubdir/');
        $this->assertInstanceOf('stored_file', $dir);

        // Add a file to the subdir.
        $filerecord = array(
            'contextid' => $user->ctxid,
            'component' => 'user',
            'filearea'  => 'private',
            'itemid'    => 0,
            'filepath'  => '/testsubdir/',
            'filename'  => 'test-get-area-tree.txt',
        );

        $directoryfile = $fs->create_file_from_string($filerecord, 'Test content');
        $this->assertInstanceOf('stored_file', $directoryfile);

        $areatree = $fs->get_area_tree($user->ctxid, 'user', 'private', 0);

        // At the top level there should still be 3 files.
        $this->assertCount(3, $areatree['files']);

        // There should now be a subdirectory.
        $this->assertCount(1, $areatree['subdirs']);

        // The test subdir is named testsubdir.
        $subdir = $areatree['subdirs']['testsubdir'];
        $this->assertNotEmpty($subdir);
        // It should have one file we added.
        $this->assertCount(1, $subdir['files']);
        // And no subdirs itself.
        $this->assertCount(0, $subdir['subdirs']);

        // Verify the file is the one we added.
        $subdirfile = reset($subdir['files']);
        $this->assertInstanceOf('stored_file', $subdirfile);
        $this->assertEquals($filerecord['filename'], $subdirfile->get_filename());
    }

    public function test_get_file_by_id() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();

        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private');

        // Test get_file_by_id.
        $filebyid = reset($areafiles);
        $shouldbesame = $fs->get_file_by_id($filebyid->get_id());
        $this->assertEquals($filebyid->get_contenthash(), $shouldbesame->get_contenthash());

        // Test an id which doens't exist.
        $doesntexist = $fs->get_file_by_id(99999);
        $this->assertFalse($doesntexist);
    }

    public function test_get_file_by_hash() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();

        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private');
        // Test get_file_by_hash
        $filebyhash = reset($areafiles);
        $shouldbesame = $fs->get_file_by_hash($filebyhash->get_pathnamehash());
        $this->assertEquals($filebyhash->get_id(), $shouldbesame->get_id());

        // Test an hash which doens't exist.
        $doesntexist = $fs->get_file_by_hash('DOESNTEXIST');
        $this->assertFalse($doesntexist);
    }

    public function test_get_external_files() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();

        $repos = repository::get_instances(array('type'=>'user'));
        $userrepository = reset($repos);
        $this->assertInstanceOf('repository', $userrepository);

        // no aliases yet
        $exfiles = $fs->get_external_files($userrepository->id, 'id');
        $this->assertEquals(array(), $exfiles);

        // create three aliases linking the same original: $aliasfile1 and $aliasfile2 are
        // created via create_file_from_reference(), $aliasfile3 created from $aliasfile2
        $originalfile = null;
        foreach ($fs->get_area_files($user->ctxid, 'user', 'private') as $areafile) {
            if (!$areafile->is_directory()) {
                $originalfile = $areafile;
                break;
            }
        }
        $this->assertInstanceOf('stored_file', $originalfile);
        $originalrecord = array(
            'contextid' => $originalfile->get_contextid(),
            'component' => $originalfile->get_component(),
            'filearea'  => $originalfile->get_filearea(),
            'itemid'    => $originalfile->get_itemid(),
            'filepath'  => $originalfile->get_filepath(),
            'filename'  => $originalfile->get_filename(),
        );

        $aliasrecord = $this->generate_file_record();
        $aliasrecord->filepath = '/foo/';
        $aliasrecord->filename = 'one.txt';

        $ref = $fs->pack_reference($originalrecord);
        $aliasfile1 = $fs->create_file_from_reference($aliasrecord, $userrepository->id, $ref);

        $aliasrecord->filepath = '/bar/';
        $aliasrecord->filename = 'uno.txt';
        // change the order of the items in the array to make sure that it does not matter
        ksort($originalrecord);
        $ref = $fs->pack_reference($originalrecord);
        $aliasfile2 = $fs->create_file_from_reference($aliasrecord, $userrepository->id, $ref);

        $aliasrecord->filepath = '/bar/';
        $aliasrecord->filename = 'jedna.txt';
        $aliasfile3 = $fs->create_file_from_storedfile($aliasrecord, $aliasfile2);

        // make sure we get three aliases now
        $exfiles = $fs->get_external_files($userrepository->id, 'id');
        $this->assertEquals(3, count($exfiles));
        foreach ($exfiles as $exfile) {
            $this->assertTrue($exfile->is_external_file());
        }
        // make sure they all link the same original (thence that all are linked with the same
        // record in {files_reference})
        $this->assertEquals($aliasfile1->get_referencefileid(), $aliasfile2->get_referencefileid());
        $this->assertEquals($aliasfile3->get_referencefileid(), $aliasfile2->get_referencefileid());
    }

    public function test_create_directory_contextid_negative() {
        $fs = get_file_storage();

        $this->setExpectedException('file_exception');
        $fs->create_directory(-1, 'core', 'unittest', 0, '/');
    }

    public function test_create_directory_contextid_invalid() {
        $fs = get_file_storage();

        $this->setExpectedException('file_exception');
        $fs->create_directory('not an int', 'core', 'unittest', 0, '/');
    }

    public function test_create_directory_component_invalid() {
        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $this->setExpectedException('file_exception');
        $fs->create_directory($syscontext->id, 'bad/component', 'unittest', 0, '/');
    }

    public function test_create_directory_filearea_invalid() {
        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $this->setExpectedException('file_exception');
        $fs->create_directory($syscontext->id, 'core', 'bad-filearea', 0, '/');
    }

    public function test_create_directory_itemid_negative() {
        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $this->setExpectedException('file_exception');
        $fs->create_directory($syscontext->id, 'core', 'unittest', -1, '/');
    }

    public function test_create_directory_itemid_invalid() {
        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $this->setExpectedException('file_exception');
        $fs->create_directory($syscontext->id, 'core', 'unittest', 'notanint', '/');
    }

    public function test_create_directory_filepath_invalid() {
        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $this->setExpectedException('file_exception');
        $fs->create_directory($syscontext->id, 'core', 'unittest', 0, '/not-with-trailing/or-leading-slash');
    }

    public function test_get_directory_files() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();

        $dir = $fs->create_directory($user->ctxid, 'user', 'private', 0, '/testsubdir/');
        $this->assertInstanceOf('stored_file', $dir);

        // Add a file to the subdir.
        $filerecord = array(
            'contextid' => $user->ctxid,
            'component' => 'user',
            'filearea'  => 'private',
            'itemid'    => 0,
            'filepath'  => '/testsubdir/',
            'filename'  => 'test-get-area-tree.txt',
        );

        $directoryfile = $fs->create_file_from_string($filerecord, 'Test content');
        $this->assertInstanceOf('stored_file', $directoryfile);

        // Don't recurse without dirs
        $files = $fs->get_directory_files($user->ctxid, 'user', 'private', 0, '/', false, false, 'id');
        // 3 files only.
        $this->assertCount(3, $files);
        foreach ($files as $key => $file) {
            $this->assertInstanceOf('stored_file', $file);
            $this->assertEquals($key, $file->get_pathnamehash());
        }

        // Don't recurse with dirs.
        $files = $fs->get_directory_files($user->ctxid, 'user', 'private', 0, '/', false, true, 'id');
        // 3 files + 1 directory.
        $this->assertCount(4, $files);
        foreach ($files as $key => $file) {
            $this->assertInstanceOf('stored_file', $file);
            $this->assertEquals($key, $file->get_pathnamehash());
        }

        // Recurse with dirs.
        $files = $fs->get_directory_files($user->ctxid, 'user', 'private', 0, '/', true, true, 'id');
        // 3 files + 1 directory +  1 subdir file.
        $this->assertCount(5, $files);
        foreach ($files as $key => $file) {
            $this->assertInstanceOf('stored_file', $file);
            $this->assertEquals($key, $file->get_pathnamehash());
        }

        // Recurse without dirs.
        $files = $fs->get_directory_files($user->ctxid, 'user', 'private', 0, '/', true, false, 'id');
        // 3 files +  1 subdir file.
        $this->assertCount(4, $files);
        foreach ($files as $key => $file) {
            $this->assertInstanceOf('stored_file', $file);
            $this->assertEquals($key, $file->get_pathnamehash());
        }
    }

    public function test_search_references() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();
        $repos = repository::get_instances(array('type'=>'user'));
        $repo = reset($repos);

        $alias1 = array(
            'contextid' => $user->ctxid,
            'component' => 'user',
            'filearea'  => 'private',
            'itemid'    => 0,
            'filepath'  => '/aliases/',
            'filename'  => 'alias-to-1.txt'
        );

        $alias2 = array(
            'contextid' => $user->ctxid,
            'component' => 'user',
            'filearea'  => 'private',
            'itemid'    => 0,
            'filepath'  => '/aliases/',
            'filename'  => 'another-alias-to-1.txt'
        );

        $reference = file_storage::pack_reference(array(
            'contextid' => $user->ctxid,
            'component' => 'user',
            'filearea'  => 'private',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => '1.txt'
        ));

        // There are no aliases now.
        $result = $fs->search_references($reference);
        $this->assertEquals(array(), $result);

        $result = $fs->search_references_count($reference);
        $this->assertSame($result, 0);

        // Create two aliases and make sure they are returned.
        $fs->create_file_from_reference($alias1, $repo->id, $reference);
        $fs->create_file_from_reference($alias2, $repo->id, $reference);

        $result = $fs->search_references($reference);
        $this->assertTrue(is_array($result));
        $this->assertEquals(count($result), 2);
        foreach ($result as $alias) {
            $this->assertTrue($alias instanceof stored_file);
        }

        $result = $fs->search_references_count($reference);
        $this->assertSame($result, 2);

        // The method can't be used for references to files outside the filepool
        $exceptionthrown = false;
        try {
            $fs->search_references('http://dl.dropbox.com/download/1234567/naked-dougiamas.jpg');
        } catch (file_reference_exception $e) {
            $exceptionthrown = true;
        }
        $this->assertTrue($exceptionthrown);

        $exceptionthrown = false;
        try {
            $fs->search_references_count('http://dl.dropbox.com/download/1234567/naked-dougiamas.jpg');
        } catch (file_reference_exception $e) {
            $exceptionthrown = true;
        }
        $this->assertTrue($exceptionthrown);
    }

    public function test_delete_area_files() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();

        // Get area files with default options.
        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private');
        // Should be the two files we added plus the folder.
        $this->assertEquals(4, count($areafiles));
        $fs->delete_area_files($user->ctxid, 'user', 'private');

        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private');
        // Should be the two files we added plus the folder.
        $this->assertEquals(0, count($areafiles));
    }

    public function test_delete_area_files_itemid() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();

        // Get area files with default options.
        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private');
        // Should be the two files we added plus the folder.
        $this->assertEquals(4, count($areafiles));
        $fs->delete_area_files($user->ctxid, 'user', 'private', 9999);

        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private');
        $this->assertEquals(4, count($areafiles));
    }

    public function test_delete_area_files_select() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();

        // Get area files with default options.
        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private');
        // Should be the two files we added plus the folder.
        $this->assertEquals(4, count($areafiles));
        $fs->delete_area_files_select($user->ctxid, 'user', 'private', '!= :notitemid', array('notitemid'=>9999));

        $areafiles = $fs->get_area_files($user->ctxid, 'user', 'private');
        // Should be the two files we added plus the folder.
        $this->assertEquals(0, count($areafiles));
    }

    public function test_create_file_from_url() {
        $this->resetAfterTest(true);

        $syscontext = context_system::instance();
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'core',
            'filearea'  => 'unittest',
            'itemid'    => 0,
            'filepath'  => '/downloadtest/',
        );
        $url = 'http://download.moodle.org/unittest/test.html';

        $fs = get_file_storage();

        // Test creating file without filename.
        $file1 = $fs->create_file_from_url($filerecord, $url);
        $this->assertInstanceOf('stored_file', $file1);

        // Set filename.
        $filerecord['filename'] = 'unit-test-filename.html';
        $file2 = $fs->create_file_from_url($filerecord, $url);
        $this->assertInstanceOf('stored_file', $file2);

        // Use temporary file.
        $filerecord['filename'] = 'unit-test-with-temp-file.html';
        $file3 = $fs->create_file_from_url($filerecord, $url, null, true);
        $file3 = $this->assertInstanceOf('stored_file', $file3);
    }

    public function test_cron() {
        $this->resetAfterTest(true);

        // Note: this is only testing DB compatibility atm, rather than
        // that work is done.
        $fs = get_file_storage();

        $this->expectOutputRegex('/Cleaning up/');
        $fs->cron();
    }

    public function test_is_area_empty() {
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();

        $this->assertFalse($fs->is_area_empty($user->ctxid, 'user', 'private'));

        // File area with madeup itemid should be empty.
        $this->assertTrue($fs->is_area_empty($user->ctxid, 'user', 'private', 9999));
        // Still empty with dirs included.
        $this->assertTrue($fs->is_area_empty($user->ctxid, 'user', 'private', 9999, false));
    }

    public function test_move_area_files_to_new_context() {
        $this->resetAfterTest(true);

        // Create a course with a page resource.
        $course = $this->getDataGenerator()->create_course();
        $page1 = $this->getDataGenerator()->create_module('page', array('course'=>$course->id));
        $page1context = context_module::instance($page1->cmid);

        // Add a file to the page.
        $fs = get_file_storage();
        $filerecord = array(
            'contextid' => $page1context->id,
            'component' => 'mod_page',
            'filearea'  => 'content',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => 'unit-test-file.txt',
        );

        $originalfile = $fs->create_file_from_string($filerecord, 'Test content');
        $this->assertInstanceOf('stored_file', $originalfile);

        $pagefiles = $fs->get_area_files($page1context->id, 'mod_page', 'content', 0, 'sortorder', false);
        // Should be one file in filearea.
        $this->assertFalse($fs->is_area_empty($page1context->id, 'mod_page', 'content'));

        // Create a new page.
        $page2 = $this->getDataGenerator()->create_module('page', array('course'=>$course->id));
        $page2context = context_module::instance($page2->cmid);

        // Newly created page area is empty.
        $this->assertTrue($fs->is_area_empty($page2context->id, 'mod_page', 'content'));

        // Move the files.
        $fs->move_area_files_to_new_context($page1context->id, $page2context->id, 'mod_page', 'content');

        // Page2 filearea should no longer be empty.
        $this->assertFalse($fs->is_area_empty($page2context->id, 'mod_page', 'content'));

        // Page1 filearea should now be empty.
        $this->assertTrue($fs->is_area_empty($page1context->id, 'mod_page', 'content'));

        $page2files = $fs->get_area_files($page2context->id, 'mod_page', 'content', 0, 'sortorder', false);
        $movedfile = reset($page2files);

        // The two files should have the same content hash.
        $this->assertEquals($movedfile->get_contenthash(), $originalfile->get_contenthash());
    }

    public function test_convert_image() {
        global $CFG;

        $this->resetAfterTest(false);

        $filepath = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';
        $syscontext = context_system::instance();
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'core',
            'filearea'  => 'unittest',
            'itemid'    => 0,
            'filepath'  => '/images/',
            'filename'  => 'testimage.jpg',
        );

        $fs = get_file_storage();
        $original = $fs->create_file_from_pathname($filerecord, $filepath);

        $filerecord['filename'] = 'testimage-converted-10x10.jpg';
        $converted = $fs->convert_image($filerecord, $original, 10, 10, true, 100);
        $this->assertInstanceOf('stored_file', $converted);

        $filerecord['filename'] = 'testimage-convereted-nosize.jpg';
        $converted = $fs->convert_image($filerecord, $original);
        $this->assertInstanceOf('stored_file', $converted);
    }

    private function generate_file_record() {
        $syscontext = context_system::instance();
        $filerecord = new stdClass();
        $filerecord->contextid = $syscontext->id;
        $filerecord->component = 'core';
        $filerecord->filearea = 'phpunit';
        $filerecord->filepath = '/';
        $filerecord->filename = 'testfile.txt';
        $filerecord->itemid = 0;

        return $filerecord;
    }

    public function test_create_file_from_storedfile_file_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $this->setExpectedException('file_exception');
        // Create a file from a file id which doesn't exist.
        $fs->create_file_from_storedfile($filerecord,  9999);
    }

    public function test_create_file_from_storedfile_contextid_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        $filerecord->filename = 'invalid.txt';
        $filerecord->contextid = 'invalid';

        $this->setExpectedException('file_exception', 'Invalid contextid');
        $fs->create_file_from_storedfile($filerecord, $file1->get_id());
    }

    public function test_create_file_from_storedfile_component_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        $filerecord->filename = 'invalid.txt';
        $filerecord->component = 'bad/component';

        $this->setExpectedException('file_exception', 'Invalid component');
        $fs->create_file_from_storedfile($filerecord, $file1->get_id());
    }

    public function test_create_file_from_storedfile_filearea_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        $filerecord->filename = 'invalid.txt';
        $filerecord->filearea = 'bad-filearea';

        $this->setExpectedException('file_exception', 'Invalid filearea');
        $fs->create_file_from_storedfile($filerecord, $file1->get_id());
    }

    public function test_create_file_from_storedfile_itemid_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        $filerecord->filename = 'invalid.txt';
        $filerecord->itemid = 'bad-itemid';

        $this->setExpectedException('file_exception', 'Invalid itemid');
        $fs->create_file_from_storedfile($filerecord, $file1->get_id());
    }

    public function test_create_file_from_storedfile_filepath_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        $filerecord->filename = 'invalid.txt';
        $filerecord->filepath = 'a-/bad/-filepath';

        $this->setExpectedException('file_exception', 'Invalid file path');
        $fs->create_file_from_storedfile($filerecord, $file1->get_id());
    }

    public function test_create_file_from_storedfile_filename_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        $filerecord->filename = '';

        $this->setExpectedException('file_exception', 'Invalid file name');
        $fs->create_file_from_storedfile($filerecord, $file1->get_id());
    }

    public function test_create_file_from_storedfile_timecreated_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        $filerecord->filename = 'invalid.txt';
        $filerecord->timecreated = 'today';

        $this->setExpectedException('file_exception', 'Invalid file timecreated');
        $fs->create_file_from_storedfile($filerecord, $file1->get_id());
    }

    public function test_create_file_from_storedfile_timemodified_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        $filerecord->filename = 'invalid.txt';
        $filerecord->timemodified  = 'today';

        $this->setExpectedException('file_exception', 'Invalid file timemodified');
        $fs->create_file_from_storedfile($filerecord, $file1->get_id());
    }

    public function test_create_file_from_storedfile_duplicate() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();

        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        // Creating a file validating unique constraint.
        $this->setExpectedException('stored_file_creation_exception');
        $fs->create_file_from_storedfile($filerecord, $file1->get_id());
    }

    public function test_create_file_from_storedfile() {
        $this->resetAfterTest(true);

        $syscontext = context_system::instance();

        $filerecord = new stdClass();
        $filerecord->contextid = $syscontext->id;
        $filerecord->component = 'core';
        $filerecord->filearea = 'phpunit';
        $filerecord->filepath = '/';
        $filerecord->filename = 'testfile.txt';
        $filerecord->itemid = 0;

        $fs = get_file_storage();

        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
        $this->assertInstanceOf('stored_file', $file1);

        $filerecord->filename = 'test-create-file-from-storedfile.txt';
        $file2 = $fs->create_file_from_storedfile($filerecord, $file1->get_id());
        $this->assertInstanceOf('stored_file', $file2);

        // These will be normalised to current time..
        $filerecord->timecreated = -100;
        $filerecord->timemodified= -100;
        $filerecord->filename = 'test-create-file-from-storedfile-bad-dates.txt';

        $file3 = $fs->create_file_from_storedfile($filerecord, $file1->get_id());
        $this->assertInstanceOf('stored_file', $file3);

        $this->assertNotEquals($file3->get_timemodified(), $filerecord->timemodified);
        $this->assertNotEquals($file3->get_timecreated(), $filerecord->timecreated);
    }

    public function test_create_file_from_string_contextid_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->contextid = 'invalid';

        $this->setExpectedException('file_exception', 'Invalid contextid');
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
    }

    public function test_create_file_from_string_component_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->component = 'bad/component';

        $this->setExpectedException('file_exception', 'Invalid component');
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
    }

    public function test_create_file_from_string_filearea_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->filearea = 'bad-filearea';

        $this->setExpectedException('file_exception', 'Invalid filearea');
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
    }

    public function test_create_file_from_string_itemid_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->itemid = 'bad-itemid';

        $this->setExpectedException('file_exception', 'Invalid itemid');
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
    }

    public function test_create_file_from_string_filepath_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->filepath = 'a-/bad/-filepath';

        $this->setExpectedException('file_exception', 'Invalid file path');
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
    }

    public function test_create_file_from_string_filename_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->filename = '';

        $this->setExpectedException('file_exception', 'Invalid file name');
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
    }

    public function test_create_file_from_string_timecreated_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->timecreated = 'today';

        $this->setExpectedException('file_exception', 'Invalid file timecreated');
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
    }

    public function test_create_file_from_string_timemodified_invalid() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->timemodified  = 'today';

        $this->setExpectedException('file_exception', 'Invalid file timemodified');
        $file1 = $fs->create_file_from_string($filerecord, 'text contents');
    }

    public function test_create_file_from_string_duplicate() {
        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $file1 = $fs->create_file_from_string($filerecord, 'text contents');

        // Creating a file validating unique constraint.
        $this->setExpectedException('stored_file_creation_exception');
        $file2 = $fs->create_file_from_string($filerecord, 'text contents');
    }

    public function test_create_file_from_pathname_contextid_invalid() {
        global $CFG;
        $path = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->contextid = 'invalid';

        $this->setExpectedException('file_exception', 'Invalid contextid');
        $file1 = $fs->create_file_from_pathname($filerecord, $path);
    }

    public function test_create_file_from_pathname_component_invalid() {
        global $CFG;
        $path = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->component = 'bad/component';

        $this->setExpectedException('file_exception', 'Invalid component');
        $file1 = $fs->create_file_from_pathname($filerecord, $path);
    }

    public function test_create_file_from_pathname_filearea_invalid() {
        global $CFG;
        $path = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->filearea = 'bad-filearea';

        $this->setExpectedException('file_exception', 'Invalid filearea');
        $file1 = $fs->create_file_from_pathname($filerecord, $path);
    }

    public function test_create_file_from_pathname_itemid_invalid() {
        global $CFG;
        $path = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->itemid = 'bad-itemid';

        $this->setExpectedException('file_exception', 'Invalid itemid');
        $file1 = $fs->create_file_from_pathname($filerecord, $path);
    }

    public function test_create_file_from_pathname_filepath_invalid() {
        global $CFG;
        $path = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->filepath = 'a-/bad/-filepath';

        $this->setExpectedException('file_exception', 'Invalid file path');
        $file1 = $fs->create_file_from_pathname($filerecord, $path);
    }

    public function test_create_file_from_pathname_filename_invalid() {
        global $CFG;
        $path = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->filename = '';

        $this->setExpectedException('file_exception', 'Invalid file name');
        $file1 = $fs->create_file_from_pathname($filerecord, $path);
    }

    public function test_create_file_from_pathname_timecreated_invalid() {
        global $CFG;
        $path = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->timecreated = 'today';

        $this->setExpectedException('file_exception', 'Invalid file timecreated');
        $file1 = $fs->create_file_from_pathname($filerecord, $path);
    }

    public function test_create_file_from_pathname_timemodified_invalid() {
        global $CFG;
        $path = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $this->resetAfterTest(true);

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $filerecord->timemodified  = 'today';

        $this->setExpectedException('file_exception', 'Invalid file timemodified');
        $file1 = $fs->create_file_from_pathname($filerecord, $path);
    }

    public function test_create_file_from_pathname_duplicate_file() {
        global $CFG;
        $this->resetAfterTest(true);

        $path = $CFG->dirroot.'/lib/filestorage/tests/fixtures/testimage.jpg';

        $filerecord = $this->generate_file_record();
        $fs = get_file_storage();

        $file1 = $fs->create_file_from_pathname($filerecord, $path);
        $this->assertInstanceOf('stored_file', $file1);

        // Creating a file validating unique constraint.
        $this->setExpectedException('stored_file_creation_exception');
        $file2 = $fs->create_file_from_pathname($filerecord, $path);
    }

    /**
     * Calling stored_file::delete_reference() on a non-reference file throws coding_exception
     */
    public function test_delete_reference_on_nonreference() {

        $this->resetAfterTest(true);
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();
        $repos = repository::get_instances(array('type'=>'user'));
        $repo = reset($repos);

        $file = null;
        foreach ($fs->get_area_files($user->ctxid, 'user', 'private') as $areafile) {
            if (!$areafile->is_directory()) {
                $file = $areafile;
                break;
            }
        }
        $this->assertInstanceOf('stored_file', $file);
        $this->assertFalse($file->is_external_file());

        $this->setExpectedException('coding_exception');
        $file->delete_reference();
    }

    /**
     * Calling stored_file::delete_reference() on a reference file does not affect other
     * symlinks to the same original
     */
    public function test_delete_reference_one_symlink_does_not_rule_them_all() {

        $this->resetAfterTest(true);
        $user = $this->setup_three_private_files();
        $fs = get_file_storage();
        $repos = repository::get_instances(array('type'=>'user'));
        $repo = reset($repos);

        // create two aliases linking the same original

        $originalfile = null;
        foreach ($fs->get_area_files($user->ctxid, 'user', 'private') as $areafile) {
            if (!$areafile->is_directory()) {
                $originalfile = $areafile;
                break;
            }
        }
        $this->assertInstanceOf('stored_file', $originalfile);

        // calling delete_reference() on a non-reference file

        $originalrecord = array(
            'contextid' => $originalfile->get_contextid(),
            'component' => $originalfile->get_component(),
            'filearea'  => $originalfile->get_filearea(),
            'itemid'    => $originalfile->get_itemid(),
            'filepath'  => $originalfile->get_filepath(),
            'filename'  => $originalfile->get_filename(),
        );

        $aliasrecord = $this->generate_file_record();
        $aliasrecord->filepath = '/A/';
        $aliasrecord->filename = 'symlink.txt';

        $ref = $fs->pack_reference($originalrecord);
        $aliasfile1 = $fs->create_file_from_reference($aliasrecord, $repo->id, $ref);

        $aliasrecord->filepath = '/B/';
        $aliasrecord->filename = 'symlink.txt';
        $ref = $fs->pack_reference($originalrecord);
        $aliasfile2 = $fs->create_file_from_reference($aliasrecord, $repo->id, $ref);

        // refetch A/symlink.txt
        $symlink1 = $fs->get_file($aliasrecord->contextid, $aliasrecord->component,
            $aliasrecord->filearea, $aliasrecord->itemid, '/A/', 'symlink.txt');
        $this->assertTrue($symlink1->is_external_file());

        // unlink the A/symlink.txt
        $symlink1->delete_reference();
        $this->assertFalse($symlink1->is_external_file());

        // make sure that B/symlink.txt has not been affected
        $symlink2 = $fs->get_file($aliasrecord->contextid, $aliasrecord->component,
            $aliasrecord->filearea, $aliasrecord->itemid, '/B/', 'symlink.txt');
        $this->assertTrue($symlink2->is_external_file());
    }
}
