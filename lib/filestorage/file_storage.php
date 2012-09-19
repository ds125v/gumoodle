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
 * Core file storage class definition.
 *
 * @package   core_files
 * @copyright 2008 Petr Skoda {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/filestorage/stored_file.php");

/**
 * File storage class used for low level access to stored files.
 *
 * Only owner of file area may use this class to access own files,
 * for example only code in mod/assignment/* may access assignment
 * attachments. When some other part of moodle needs to access
 * files of modules it has to use file_browser class instead or there
 * has to be some callback API.
 *
 * @package   core_files
 * @category  files
 * @copyright 2008 Petr Skoda {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class file_storage {
    /** @var string Directory with file contents */
    private $filedir;
    /** @var string Contents of deleted files not needed any more */
    private $trashdir;
    /** @var string tempdir */
    private $tempdir;
    /** @var int Permissions for new directories */
    private $dirpermissions;
    /** @var int Permissions for new files */
    private $filepermissions;

    /**
     * Constructor - do not use directly use {@link get_file_storage()} call instead.
     *
     * @param string $filedir full path to pool directory
     * @param string $trashdir temporary storage of deleted area
     * @param string $tempdir temporary storage of various files
     * @param int $dirpermissions new directory permissions
     * @param int $filepermissions new file permissions
     */
    public function __construct($filedir, $trashdir, $tempdir, $dirpermissions, $filepermissions) {
        $this->filedir         = $filedir;
        $this->trashdir        = $trashdir;
        $this->tempdir         = $tempdir;
        $this->dirpermissions  = $dirpermissions;
        $this->filepermissions = $filepermissions;

        // make sure the file pool directory exists
        if (!is_dir($this->filedir)) {
            if (!mkdir($this->filedir, $this->dirpermissions, true)) {
                throw new file_exception('storedfilecannotcreatefiledirs'); // permission trouble
            }
            // place warning file in file pool root
            if (!file_exists($this->filedir.'/warning.txt')) {
                file_put_contents($this->filedir.'/warning.txt',
                                  'This directory contains the content of uploaded files and is controlled by Moodle code. Do not manually move, change or rename any of the files and subdirectories here.');
            }
        }
        // make sure the file pool directory exists
        if (!is_dir($this->trashdir)) {
            if (!mkdir($this->trashdir, $this->dirpermissions, true)) {
                throw new file_exception('storedfilecannotcreatefiledirs'); // permission trouble
            }
        }
    }

    /**
     * Calculates sha1 hash of unique full path name information.
     *
     * This hash is a unique file identifier - it is used to improve
     * performance and overcome db index size limits.
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return string sha1 hash
     */
    public static function get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename) {
        return sha1("/$contextid/$component/$filearea/$itemid".$filepath.$filename);
    }

    /**
     * Does this file exist?
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return bool
     */
    public function file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename) {
        $filepath = clean_param($filepath, PARAM_PATH);
        $filename = clean_param($filename, PARAM_FILE);

        if ($filename === '') {
            $filename = '.';
        }

        $pathnamehash = $this->get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename);
        return $this->file_exists_by_hash($pathnamehash);
    }

    /**
     * Whether or not the file exist
     *
     * @param string $pathnamehash path name hash
     * @return bool
     */
    public function file_exists_by_hash($pathnamehash) {
        global $DB;

        return $DB->record_exists('files', array('pathnamehash'=>$pathnamehash));
    }

    /**
     * Create instance of file class from database record.
     *
     * @param stdClass $filerecord record from the files table left join files_reference table
     * @return stored_file instance of file abstraction class
     */
    public function get_file_instance(stdClass $filerecord) {
        $storedfile = new stored_file($this, $filerecord, $this->filedir);
        return $storedfile;
    }

    /**
     * Returns an image file that represent the given stored file as a preview
     *
     * At the moment, only GIF, JPEG and PNG files are supported to have previews. In the
     * future, the support for other mimetypes can be added, too (eg. generate an image
     * preview of PDF, text documents etc).
     *
     * @param stored_file $file the file we want to preview
     * @param string $mode preview mode, eg. 'thumb'
     * @return stored_file|bool false if unable to create the preview, stored file otherwise
     */
    public function get_file_preview(stored_file $file, $mode) {

        $context = context_system::instance();
        $path = '/' . trim($mode, '/') . '/';
        $preview = $this->get_file($context->id, 'core', 'preview', 0, $path, $file->get_contenthash());

        if (!$preview) {
            $preview = $this->create_file_preview($file, $mode);
            if (!$preview) {
                return false;
            }
        }

        return $preview;
    }

    /**
     * Generates a preview image for the stored file
     *
     * @param stored_file $file the file we want to preview
     * @param string $mode preview mode, eg. 'thumb'
     * @return stored_file|bool the newly created preview file or false
     */
    protected function create_file_preview(stored_file $file, $mode) {

        $mimetype = $file->get_mimetype();

        if ($mimetype === 'image/gif' or $mimetype === 'image/jpeg' or $mimetype === 'image/png') {
            // make a preview of the image
            $data = $this->create_imagefile_preview($file, $mode);

        } else {
            // unable to create the preview of this mimetype yet
            return false;
        }

        if (empty($data)) {
            return false;
        }

        // getimagesizefromstring() is available from PHP 5.4 but we need to support
        // lower versions, so...
        $tmproot = make_temp_directory('thumbnails');
        $tmpfilepath = $tmproot.'/'.$file->get_contenthash().'_'.$mode;
        file_put_contents($tmpfilepath, $data);
        $imageinfo = getimagesize($tmpfilepath);
        unlink($tmpfilepath);

        $context = context_system::instance();

        $record = array(
            'contextid' => $context->id,
            'component' => 'core',
            'filearea'  => 'preview',
            'itemid'    => 0,
            'filepath'  => '/' . trim($mode, '/') . '/',
            'filename'  => $file->get_contenthash(),
        );

        if ($imageinfo) {
            $record['mimetype'] = $imageinfo['mime'];
        }

        return $this->create_file_from_string($record, $data);
    }

    /**
     * Generates a preview for the stored image file
     *
     * @param stored_file $file the image we want to preview
     * @param string $mode preview mode, eg. 'thumb'
     * @return string|bool false if a problem occurs, the thumbnail image data otherwise
     */
    protected function create_imagefile_preview(stored_file $file, $mode) {
        global $CFG;
        require_once($CFG->libdir.'/gdlib.php');

        $tmproot = make_temp_directory('thumbnails');
        $tmpfilepath = $tmproot.'/'.$file->get_contenthash();
        $file->copy_content_to($tmpfilepath);

        if ($mode === 'tinyicon') {
            $data = generate_image_thumbnail($tmpfilepath, 24, 24);

        } else if ($mode === 'thumb') {
            $data = generate_image_thumbnail($tmpfilepath, 90, 90);

        } else {
            throw new file_exception('storedfileproblem', 'Invalid preview mode requested');
        }

        unlink($tmpfilepath);

        return $data;
    }

    /**
     * Fetch file using local file id.
     *
     * Please do not rely on file ids, it is usually easier to use
     * pathname hashes instead.
     *
     * @param int $fileid file ID
     * @return stored_file|bool stored_file instance if exists, false if not
     */
    public function get_file_by_id($fileid) {
        global $DB;

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE f.id = ?";
        if ($filerecord = $DB->get_record_sql($sql, array($fileid))) {
            return $this->get_file_instance($filerecord);
        } else {
            return false;
        }
    }

    /**
     * Fetch file using local file full pathname hash
     *
     * @param string $pathnamehash path name hash
     * @return stored_file|bool stored_file instance if exists, false if not
     */
    public function get_file_by_hash($pathnamehash) {
        global $DB;

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE f.pathnamehash = ?";
        if ($filerecord = $DB->get_record_sql($sql, array($pathnamehash))) {
            return $this->get_file_instance($filerecord);
        } else {
            return false;
        }
    }

    /**
     * Fetch locally stored file.
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return stored_file|bool stored_file instance if exists, false if not
     */
    public function get_file($contextid, $component, $filearea, $itemid, $filepath, $filename) {
        $filepath = clean_param($filepath, PARAM_PATH);
        $filename = clean_param($filename, PARAM_FILE);

        if ($filename === '') {
            $filename = '.';
        }

        $pathnamehash = $this->get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename);
        return $this->get_file_by_hash($pathnamehash);
    }

    /**
     * Are there any files (or directories)
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param bool|int $itemid item id or false if all items
     * @param bool $ignoredirs whether or not ignore directories
     * @return bool empty
     */
    public function is_area_empty($contextid, $component, $filearea, $itemid = false, $ignoredirs = true) {
        global $DB;

        $params = array('contextid'=>$contextid, 'component'=>$component, 'filearea'=>$filearea);
        $where = "contextid = :contextid AND component = :component AND filearea = :filearea";

        if ($itemid !== false) {
            $params['itemid'] = $itemid;
            $where .= " AND itemid = :itemid";
        }

        if ($ignoredirs) {
            $sql = "SELECT 'x'
                      FROM {files}
                     WHERE $where AND filename <> '.'";
        } else {
            $sql = "SELECT 'x'
                      FROM {files}
                     WHERE $where AND (filename <> '.' OR filepath <> '/')";
        }

        return !$DB->record_exists_sql($sql, $params);
    }

    /**
     * Returns all files belonging to given repository
     *
     * @param int $repositoryid
     * @param string $sort A fragment of SQL to use for sorting
     */
    public function get_external_files($repositoryid, $sort = 'sortorder, itemid, filepath, filename') {
        global $DB;
        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE r.repositoryid = ?";
        if (!empty($sort)) {
            $sql .= " ORDER BY {$sort}";
        }

        $result = array();
        $filerecords = $DB->get_records_sql($sql, array($repositoryid));
        foreach ($filerecords as $filerecord) {
            $result[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
        }
        return $result;
    }

    /**
     * Returns all area files (optionally limited by itemid)
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID or all files if not specified
     * @param string $sort A fragment of SQL to use for sorting
     * @param bool $includedirs whether or not include directories
     * @return array of stored_files indexed by pathanmehash
     */
    public function get_area_files($contextid, $component, $filearea, $itemid = false, $sort = "sortorder, itemid, filepath, filename", $includedirs = true) {
        global $DB;

        $conditions = array('contextid'=>$contextid, 'component'=>$component, 'filearea'=>$filearea);
        if ($itemid !== false) {
            $itemidsql = ' AND f.itemid = :itemid ';
            $conditions['itemid'] = $itemid;
        } else {
            $itemidsql = '';
        }

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE f.contextid = :contextid
                       AND f.component = :component
                       AND f.filearea = :filearea
                       $itemidsql";
        if (!empty($sort)) {
            $sql .= " ORDER BY {$sort}";
        }

        $result = array();
        $filerecords = $DB->get_records_sql($sql, $conditions);
        foreach ($filerecords as $filerecord) {
            if (!$includedirs and $filerecord->filename === '.') {
                continue;
            }
            $result[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
        }
        return $result;
    }

    /**
     * Returns array based tree structure of area files
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @return array each dir represented by dirname, subdirs, files and dirfile array elements
     */
    public function get_area_tree($contextid, $component, $filearea, $itemid) {
        $result = array('dirname'=>'', 'dirfile'=>null, 'subdirs'=>array(), 'files'=>array());
        $files = $this->get_area_files($contextid, $component, $filearea, $itemid, "sortorder, itemid, filepath, filename", true);
        // first create directory structure
        foreach ($files as $hash=>$dir) {
            if (!$dir->is_directory()) {
                continue;
            }
            unset($files[$hash]);
            if ($dir->get_filepath() === '/') {
                $result['dirfile'] = $dir;
                continue;
            }
            $parts = explode('/', trim($dir->get_filepath(),'/'));
            $pointer =& $result;
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                if (!isset($pointer['subdirs'][$part])) {
                    $pointer['subdirs'][$part] = array('dirname'=>$part, 'dirfile'=>null, 'subdirs'=>array(), 'files'=>array());
                }
                $pointer =& $pointer['subdirs'][$part];
            }
            $pointer['dirfile'] = $dir;
            unset($pointer);
        }
        foreach ($files as $hash=>$file) {
            $parts = explode('/', trim($file->get_filepath(),'/'));
            $pointer =& $result;
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                $pointer =& $pointer['subdirs'][$part];
            }
            $pointer['files'][$file->get_filename()] = $file;
            unset($pointer);
        }
        return $result;
    }

    /**
     * Returns all files and optionally directories
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param int $filepath directory path
     * @param bool $recursive include all subdirectories
     * @param bool $includedirs include files and directories
     * @param string $sort A fragment of SQL to use for sorting
     * @return array of stored_files indexed by pathanmehash
     */
    public function get_directory_files($contextid, $component, $filearea, $itemid, $filepath, $recursive = false, $includedirs = true, $sort = "filepath, filename") {
        global $DB;

        if (!$directory = $this->get_file($contextid, $component, $filearea, $itemid, $filepath, '.')) {
            return array();
        }

        $orderby = (!empty($sort)) ? " ORDER BY {$sort}" : '';

        if ($recursive) {

            $dirs = $includedirs ? "" : "AND filename <> '.'";
            $length = textlib::strlen($filepath);

            $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                      FROM {files} f
                 LEFT JOIN {files_reference} r
                           ON f.referencefileid = r.id
                     WHERE f.contextid = :contextid AND f.component = :component AND f.filearea = :filearea AND f.itemid = :itemid
                           AND ".$DB->sql_substr("f.filepath", 1, $length)." = :filepath
                           AND f.id <> :dirid
                           $dirs
                           $orderby";
            $params = array('contextid'=>$contextid, 'component'=>$component, 'filearea'=>$filearea, 'itemid'=>$itemid, 'filepath'=>$filepath, 'dirid'=>$directory->get_id());

            $files = array();
            $dirs  = array();
            $filerecords = $DB->get_records_sql($sql, $params);
            foreach ($filerecords as $filerecord) {
                if ($filerecord->filename == '.') {
                    $dirs[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
                } else {
                    $files[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
                }
            }
            $result = array_merge($dirs, $files);

        } else {
            $result = array();
            $params = array('contextid'=>$contextid, 'component'=>$component, 'filearea'=>$filearea, 'itemid'=>$itemid, 'filepath'=>$filepath, 'dirid'=>$directory->get_id());

            $length = textlib::strlen($filepath);

            if ($includedirs) {
                $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                          FROM {files} f
                     LEFT JOIN {files_reference} r
                               ON f.referencefileid = r.id
                         WHERE f.contextid = :contextid AND f.component = :component AND f.filearea = :filearea
                               AND f.itemid = :itemid AND f.filename = '.'
                               AND ".$DB->sql_substr("f.filepath", 1, $length)." = :filepath
                               AND f.id <> :dirid
                               $orderby";
                $reqlevel = substr_count($filepath, '/') + 1;
                $filerecords = $DB->get_records_sql($sql, $params);
                foreach ($filerecords as $filerecord) {
                    if (substr_count($filerecord->filepath, '/') !== $reqlevel) {
                        continue;
                    }
                    $result[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
                }
            }

            $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                      FROM {files} f
                 LEFT JOIN {files_reference} r
                           ON f.referencefileid = r.id
                     WHERE f.contextid = :contextid AND f.component = :component AND f.filearea = :filearea AND f.itemid = :itemid
                           AND f.filepath = :filepath AND f.filename <> '.'
                           $orderby";

            $filerecords = $DB->get_records_sql($sql, $params);
            foreach ($filerecords as $filerecord) {
                $result[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
            }
        }

        return $result;
    }

    /**
     * Delete all area files (optionally limited by itemid).
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area or all areas in context if not specified
     * @param int $itemid item ID or all files if not specified
     * @return bool success
     */
    public function delete_area_files($contextid, $component = false, $filearea = false, $itemid = false) {
        global $DB;

        $conditions = array('contextid'=>$contextid);
        if ($component !== false) {
            $conditions['component'] = $component;
        }
        if ($filearea !== false) {
            $conditions['filearea'] = $filearea;
        }
        if ($itemid !== false) {
            $conditions['itemid'] = $itemid;
        }

        $filerecords = $DB->get_records('files', $conditions);
        foreach ($filerecords as $filerecord) {
            $this->get_file_instance($filerecord)->delete();
        }

        return true; // BC only
    }

    /**
     * Delete all the files from certain areas where itemid is limited by an
     * arbitrary bit of SQL.
     *
     * @param int $contextid the id of the context the files belong to. Must be given.
     * @param string $component the owning component. Must be given.
     * @param string $filearea the file area name. Must be given.
     * @param string $itemidstest an SQL fragment that the itemid must match. Used
     *      in the query like WHERE itemid $itemidstest. Must used named parameters,
     *      and may not used named parameters called contextid, component or filearea.
     * @param array $params any query params used by $itemidstest.
     */
    public function delete_area_files_select($contextid, $component,
            $filearea, $itemidstest, array $params = null) {
        global $DB;

        $where = "contextid = :contextid
                AND component = :component
                AND filearea = :filearea
                AND itemid $itemidstest";
        $params['contextid'] = $contextid;
        $params['component'] = $component;
        $params['filearea'] = $filearea;

        $filerecords = $DB->get_recordset_select('files', $where, $params);
        foreach ($filerecords as $filerecord) {
            $this->get_file_instance($filerecord)->delete();
        }
        $filerecords->close();
    }

    /**
     * Move all the files in a file area from one context to another.
     *
     * @param int $oldcontextid the context the files are being moved from.
     * @param int $newcontextid the context the files are being moved to.
     * @param string $component the plugin that these files belong to.
     * @param string $filearea the name of the file area.
     * @param int $itemid file item ID
     * @return int the number of files moved, for information.
     */
    public function move_area_files_to_new_context($oldcontextid, $newcontextid, $component, $filearea, $itemid = false) {
        // Note, this code is based on some code that Petr wrote in
        // forum_move_attachments in mod/forum/lib.php. I moved it here because
        // I needed it in the question code too.
        $count = 0;

        $oldfiles = $this->get_area_files($oldcontextid, $component, $filearea, $itemid, 'id', false);
        foreach ($oldfiles as $oldfile) {
            $filerecord = new stdClass();
            $filerecord->contextid = $newcontextid;
            $this->create_file_from_storedfile($filerecord, $oldfile);
            $count += 1;
        }

        if ($count) {
            $this->delete_area_files($oldcontextid, $component, $filearea, $itemid);
        }

        return $count;
    }

    /**
     * Recursively creates directory.
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param int $userid the user ID
     * @return bool success
     */
    public function create_directory($contextid, $component, $filearea, $itemid, $filepath, $userid = null) {
        global $DB;

        // validate all parameters, we do not want any rubbish stored in database, right?
        if (!is_number($contextid) or $contextid < 1) {
            throw new file_exception('storedfileproblem', 'Invalid contextid');
        }

        $component = clean_param($component, PARAM_COMPONENT);
        if (empty($component)) {
            throw new file_exception('storedfileproblem', 'Invalid component');
        }

        $filearea = clean_param($filearea, PARAM_AREA);
        if (empty($filearea)) {
            throw new file_exception('storedfileproblem', 'Invalid filearea');
        }

        if (!is_number($itemid) or $itemid < 0) {
            throw new file_exception('storedfileproblem', 'Invalid itemid');
        }

        $filepath = clean_param($filepath, PARAM_PATH);
        if (strpos($filepath, '/') !== 0 or strrpos($filepath, '/') !== strlen($filepath)-1) {
            // path must start and end with '/'
            throw new file_exception('storedfileproblem', 'Invalid file path');
        }

        $pathnamehash = $this->get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, '.');

        if ($dir_info = $this->get_file_by_hash($pathnamehash)) {
            return $dir_info;
        }

        static $contenthash = null;
        if (!$contenthash) {
            $this->add_string_to_pool('');
            $contenthash = sha1('');
        }

        $now = time();

        $dir_record = new stdClass();
        $dir_record->contextid = $contextid;
        $dir_record->component = $component;
        $dir_record->filearea  = $filearea;
        $dir_record->itemid    = $itemid;
        $dir_record->filepath  = $filepath;
        $dir_record->filename  = '.';
        $dir_record->contenthash  = $contenthash;
        $dir_record->filesize  = 0;

        $dir_record->timecreated  = $now;
        $dir_record->timemodified = $now;
        $dir_record->mimetype     = null;
        $dir_record->userid       = $userid;

        $dir_record->pathnamehash = $pathnamehash;

        $DB->insert_record('files', $dir_record);
        $dir_info = $this->get_file_by_hash($pathnamehash);

        if ($filepath !== '/') {
            //recurse to parent dirs
            $filepath = trim($filepath, '/');
            $filepath = explode('/', $filepath);
            array_pop($filepath);
            $filepath = implode('/', $filepath);
            $filepath = ($filepath === '') ? '/' : "/$filepath/";
            $this->create_directory($contextid, $component, $filearea, $itemid, $filepath, $userid);
        }

        return $dir_info;
    }

    /**
     * Add new local file based on existing local file.
     *
     * @param stdClass|array $filerecord object or array describing changes
     * @param stored_file|int $fileorid id or stored_file instance of the existing local file
     * @return stored_file instance of newly created file
     */
    public function create_file_from_storedfile($filerecord, $fileorid) {
        global $DB;

        if ($fileorid instanceof stored_file) {
            $fid = $fileorid->get_id();
        } else {
            $fid = $fileorid;
        }

        $filerecord = (array)$filerecord; // We support arrays too, do not modify the submitted record!

        unset($filerecord['id']);
        unset($filerecord['filesize']);
        unset($filerecord['contenthash']);
        unset($filerecord['pathnamehash']);

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE f.id = ?";

        if (!$newrecord = $DB->get_record_sql($sql, array($fid))) {
            throw new file_exception('storedfileproblem', 'File does not exist');
        }

        unset($newrecord->id);

        foreach ($filerecord as $key => $value) {
            // validate all parameters, we do not want any rubbish stored in database, right?
            if ($key == 'contextid' and (!is_number($value) or $value < 1)) {
                throw new file_exception('storedfileproblem', 'Invalid contextid');
            }

            if ($key == 'component') {
                $value = clean_param($value, PARAM_COMPONENT);
                if (empty($value)) {
                    throw new file_exception('storedfileproblem', 'Invalid component');
                }
            }

            if ($key == 'filearea') {
                $value = clean_param($value, PARAM_AREA);
                if (empty($value)) {
                    throw new file_exception('storedfileproblem', 'Invalid filearea');
                }
            }

            if ($key == 'itemid' and (!is_number($value) or $value < 0)) {
                throw new file_exception('storedfileproblem', 'Invalid itemid');
            }


            if ($key == 'filepath') {
                $value = clean_param($value, PARAM_PATH);
                if (strpos($value, '/') !== 0 or strrpos($value, '/') !== strlen($value)-1) {
                    // path must start and end with '/'
                    throw new file_exception('storedfileproblem', 'Invalid file path');
                }
            }

            if ($key == 'filename') {
                $value = clean_param($value, PARAM_FILE);
                if ($value === '') {
                    // path must start and end with '/'
                    throw new file_exception('storedfileproblem', 'Invalid file name');
                }
            }

            if ($key === 'timecreated' or $key === 'timemodified') {
                if (!is_number($value)) {
                    throw new file_exception('storedfileproblem', 'Invalid file '.$key);
                }
                if ($value < 0) {
                    //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                    $value = 0;
                }
            }

            if ($key == 'referencefileid' or $key == 'referencelastsync' or $key == 'referencelifetime') {
                $value = clean_param($value, PARAM_INT);
            }

            $newrecord->$key = $value;
        }

        $newrecord->pathnamehash = $this->get_pathname_hash($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->filename);

        if ($newrecord->filename === '.') {
            // special case - only this function supports directories ;-)
            $directory = $this->create_directory($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->userid);
            // update the existing directory with the new data
            $newrecord->id = $directory->get_id();
            $DB->update_record('files', $newrecord);
            return $this->get_file_instance($newrecord);
        }

        // note: referencefileid is copied from the original file so that
        // creating a new file from an existing alias creates new alias implicitly.
        // here we just check the database consistency.
        if (!empty($newrecord->repositoryid)) {
            if ($newrecord->referencefileid != $this->get_referencefileid($newrecord->repositoryid, $newrecord->reference, MUST_EXIST)) {
                throw new file_reference_exception($newrecord->repositoryid, $newrecord->reference, $newrecord->referencefileid);
            }
        }

        try {
            $newrecord->id = $DB->insert_record('files', $newrecord);
        } catch (dml_exception $e) {
            throw new stored_file_creation_exception($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid,
                                                     $newrecord->filepath, $newrecord->filename, $e->debuginfo);
        }


        $this->create_directory($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->userid);

        return $this->get_file_instance($newrecord);
    }

    /**
     * Add new local file.
     *
     * @param stdClass|array $filerecord object or array describing file
     * @param string $url the URL to the file
     * @param array $options {@link download_file_content()} options
     * @param bool $usetempfile use temporary file for download, may prevent out of memory problems
     * @return stored_file
     */
    public function create_file_from_url($filerecord, $url, array $options = null, $usetempfile = false) {

        $filerecord = (array)$filerecord;  // Do not modify the submitted record, this cast unlinks objects.
        $filerecord = (object)$filerecord; // We support arrays too.

        $headers        = isset($options['headers'])        ? $options['headers'] : null;
        $postdata       = isset($options['postdata'])       ? $options['postdata'] : null;
        $fullresponse   = isset($options['fullresponse'])   ? $options['fullresponse'] : false;
        $timeout        = isset($options['timeout'])        ? $options['timeout'] : 300;
        $connecttimeout = isset($options['connecttimeout']) ? $options['connecttimeout'] : 20;
        $skipcertverify = isset($options['skipcertverify']) ? $options['skipcertverify'] : false;
        $calctimeout    = isset($options['calctimeout'])    ? $options['calctimeout'] : false;

        if (!isset($filerecord->filename)) {
            $parts = explode('/', $url);
            $filename = array_pop($parts);
            $filerecord->filename = clean_param($filename, PARAM_FILE);
        }
        $source = !empty($filerecord->source) ? $filerecord->source : $url;
        $filerecord->source = clean_param($source, PARAM_URL);

        if ($usetempfile) {
            check_dir_exists($this->tempdir);
            $tmpfile = tempnam($this->tempdir, 'newfromurl');
            $content = download_file_content($url, $headers, $postdata, $fullresponse, $timeout, $connecttimeout, $skipcertverify, $tmpfile, $calctimeout);
            if ($content === false) {
                throw new file_exception('storedfileproblem', 'Can not fetch file form URL');
            }
            try {
                $newfile = $this->create_file_from_pathname($filerecord, $tmpfile);
                @unlink($tmpfile);
                return $newfile;
            } catch (Exception $e) {
                @unlink($tmpfile);
                throw $e;
            }

        } else {
            $content = download_file_content($url, $headers, $postdata, $fullresponse, $timeout, $connecttimeout, $skipcertverify, NULL, $calctimeout);
            if ($content === false) {
                throw new file_exception('storedfileproblem', 'Can not fetch file form URL');
            }
            return $this->create_file_from_string($filerecord, $content);
        }
    }

    /**
     * Add new local file.
     *
     * @param stdClass|array $filerecord object or array describing file
     * @param string $pathname path to file or content of file
     * @return stored_file
     */
    public function create_file_from_pathname($filerecord, $pathname) {
        global $DB;

        $filerecord = (array)$filerecord;  // Do not modify the submitted record, this cast unlinks objects.
        $filerecord = (object)$filerecord; // We support arrays too.

        // validate all parameters, we do not want any rubbish stored in database, right?
        if (!is_number($filerecord->contextid) or $filerecord->contextid < 1) {
            throw new file_exception('storedfileproblem', 'Invalid contextid');
        }

        $filerecord->component = clean_param($filerecord->component, PARAM_COMPONENT);
        if (empty($filerecord->component)) {
            throw new file_exception('storedfileproblem', 'Invalid component');
        }

        $filerecord->filearea = clean_param($filerecord->filearea, PARAM_AREA);
        if (empty($filerecord->filearea)) {
            throw new file_exception('storedfileproblem', 'Invalid filearea');
        }

        if (!is_number($filerecord->itemid) or $filerecord->itemid < 0) {
            throw new file_exception('storedfileproblem', 'Invalid itemid');
        }

        if (!empty($filerecord->sortorder)) {
            if (!is_number($filerecord->sortorder) or $filerecord->sortorder < 0) {
                $filerecord->sortorder = 0;
            }
        } else {
            $filerecord->sortorder = 0;
        }

        $filerecord->filepath = clean_param($filerecord->filepath, PARAM_PATH);
        if (strpos($filerecord->filepath, '/') !== 0 or strrpos($filerecord->filepath, '/') !== strlen($filerecord->filepath)-1) {
            // path must start and end with '/'
            throw new file_exception('storedfileproblem', 'Invalid file path');
        }

        $filerecord->filename = clean_param($filerecord->filename, PARAM_FILE);
        if ($filerecord->filename === '') {
            // filename must not be empty
            throw new file_exception('storedfileproblem', 'Invalid file name');
        }

        $now = time();
        if (isset($filerecord->timecreated)) {
            if (!is_number($filerecord->timecreated)) {
                throw new file_exception('storedfileproblem', 'Invalid file timecreated');
            }
            if ($filerecord->timecreated < 0) {
                //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timecreated = 0;
            }
        } else {
            $filerecord->timecreated = $now;
        }

        if (isset($filerecord->timemodified)) {
            if (!is_number($filerecord->timemodified)) {
                throw new file_exception('storedfileproblem', 'Invalid file timemodified');
            }
            if ($filerecord->timemodified < 0) {
                //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timemodified = 0;
            }
        } else {
            $filerecord->timemodified = $now;
        }

        $newrecord = new stdClass();

        $newrecord->contextid = $filerecord->contextid;
        $newrecord->component = $filerecord->component;
        $newrecord->filearea  = $filerecord->filearea;
        $newrecord->itemid    = $filerecord->itemid;
        $newrecord->filepath  = $filerecord->filepath;
        $newrecord->filename  = $filerecord->filename;

        $newrecord->timecreated  = $filerecord->timecreated;
        $newrecord->timemodified = $filerecord->timemodified;
        $newrecord->mimetype     = empty($filerecord->mimetype) ? $this->mimetype($pathname, $filerecord->filename) : $filerecord->mimetype;
        $newrecord->userid       = empty($filerecord->userid) ? null : $filerecord->userid;
        $newrecord->source       = empty($filerecord->source) ? null : $filerecord->source;
        $newrecord->author       = empty($filerecord->author) ? null : $filerecord->author;
        $newrecord->license      = empty($filerecord->license) ? null : $filerecord->license;
        $newrecord->status       = empty($filerecord->status) ? 0 : $filerecord->status;
        $newrecord->sortorder    = $filerecord->sortorder;

        list($newrecord->contenthash, $newrecord->filesize, $newfile) = $this->add_file_to_pool($pathname);

        $newrecord->pathnamehash = $this->get_pathname_hash($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->filename);

        try {
            $newrecord->id = $DB->insert_record('files', $newrecord);
        } catch (dml_exception $e) {
            if ($newfile) {
                $this->deleted_file_cleanup($newrecord->contenthash);
            }
            throw new stored_file_creation_exception($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid,
                                                    $newrecord->filepath, $newrecord->filename, $e->debuginfo);
        }

        $this->create_directory($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->userid);

        return $this->get_file_instance($newrecord);
    }

    /**
     * Add new local file.
     *
     * @param stdClass|array $filerecord object or array describing file
     * @param string $content content of file
     * @return stored_file
     */
    public function create_file_from_string($filerecord, $content) {
        global $DB;

        $filerecord = (array)$filerecord;  // Do not modify the submitted record, this cast unlinks objects.
        $filerecord = (object)$filerecord; // We support arrays too.

        // validate all parameters, we do not want any rubbish stored in database, right?
        if (!is_number($filerecord->contextid) or $filerecord->contextid < 1) {
            throw new file_exception('storedfileproblem', 'Invalid contextid');
        }

        $filerecord->component = clean_param($filerecord->component, PARAM_COMPONENT);
        if (empty($filerecord->component)) {
            throw new file_exception('storedfileproblem', 'Invalid component');
        }

        $filerecord->filearea = clean_param($filerecord->filearea, PARAM_AREA);
        if (empty($filerecord->filearea)) {
            throw new file_exception('storedfileproblem', 'Invalid filearea');
        }

        if (!is_number($filerecord->itemid) or $filerecord->itemid < 0) {
            throw new file_exception('storedfileproblem', 'Invalid itemid');
        }

        if (!empty($filerecord->sortorder)) {
            if (!is_number($filerecord->sortorder) or $filerecord->sortorder < 0) {
                $filerecord->sortorder = 0;
            }
        } else {
            $filerecord->sortorder = 0;
        }

        $filerecord->filepath = clean_param($filerecord->filepath, PARAM_PATH);
        if (strpos($filerecord->filepath, '/') !== 0 or strrpos($filerecord->filepath, '/') !== strlen($filerecord->filepath)-1) {
            // path must start and end with '/'
            throw new file_exception('storedfileproblem', 'Invalid file path');
        }

        $filerecord->filename = clean_param($filerecord->filename, PARAM_FILE);
        if ($filerecord->filename === '') {
            // path must start and end with '/'
            throw new file_exception('storedfileproblem', 'Invalid file name');
        }

        $now = time();
        if (isset($filerecord->timecreated)) {
            if (!is_number($filerecord->timecreated)) {
                throw new file_exception('storedfileproblem', 'Invalid file timecreated');
            }
            if ($filerecord->timecreated < 0) {
                //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timecreated = 0;
            }
        } else {
            $filerecord->timecreated = $now;
        }

        if (isset($filerecord->timemodified)) {
            if (!is_number($filerecord->timemodified)) {
                throw new file_exception('storedfileproblem', 'Invalid file timemodified');
            }
            if ($filerecord->timemodified < 0) {
                //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timemodified = 0;
            }
        } else {
            $filerecord->timemodified = $now;
        }

        $newrecord = new stdClass();

        $newrecord->contextid = $filerecord->contextid;
        $newrecord->component = $filerecord->component;
        $newrecord->filearea  = $filerecord->filearea;
        $newrecord->itemid    = $filerecord->itemid;
        $newrecord->filepath  = $filerecord->filepath;
        $newrecord->filename  = $filerecord->filename;

        $newrecord->timecreated  = $filerecord->timecreated;
        $newrecord->timemodified = $filerecord->timemodified;
        $newrecord->userid       = empty($filerecord->userid) ? null : $filerecord->userid;
        $newrecord->source       = empty($filerecord->source) ? null : $filerecord->source;
        $newrecord->author       = empty($filerecord->author) ? null : $filerecord->author;
        $newrecord->license      = empty($filerecord->license) ? null : $filerecord->license;
        $newrecord->status       = empty($filerecord->status) ? 0 : $filerecord->status;
        $newrecord->sortorder    = $filerecord->sortorder;

        list($newrecord->contenthash, $newrecord->filesize, $newfile) = $this->add_string_to_pool($content);
        $filepathname = $this->path_from_hash($newrecord->contenthash) . '/' . $newrecord->contenthash;
        // get mimetype by magic bytes
        $newrecord->mimetype = empty($filerecord->mimetype) ? $this->mimetype($filepathname, $filerecord->filename) : $filerecord->mimetype;

        $newrecord->pathnamehash = $this->get_pathname_hash($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->filename);

        try {
            $newrecord->id = $DB->insert_record('files', $newrecord);
        } catch (dml_exception $e) {
            if ($newfile) {
                $this->deleted_file_cleanup($newrecord->contenthash);
            }
            throw new stored_file_creation_exception($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid,
                                                    $newrecord->filepath, $newrecord->filename, $e->debuginfo);
        }

        $this->create_directory($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->userid);

        return $this->get_file_instance($newrecord);
    }

    /**
     * Create a new alias/shortcut file from file reference information
     *
     * @param stdClass|array $filerecord object or array describing the new file
     * @param int $repositoryid the id of the repository that provides the original file
     * @param string $reference the information required by the repository to locate the original file
     * @param array $options options for creating the new file
     * @return stored_file
     */
    public function create_file_from_reference($filerecord, $repositoryid, $reference, $options = array()) {
        global $DB;

        $filerecord = (array)$filerecord;  // Do not modify the submitted record, this cast unlinks objects.
        $filerecord = (object)$filerecord; // We support arrays too.

        // validate all parameters, we do not want any rubbish stored in database, right?
        if (!is_number($filerecord->contextid) or $filerecord->contextid < 1) {
            throw new file_exception('storedfileproblem', 'Invalid contextid');
        }

        $filerecord->component = clean_param($filerecord->component, PARAM_COMPONENT);
        if (empty($filerecord->component)) {
            throw new file_exception('storedfileproblem', 'Invalid component');
        }

        $filerecord->filearea = clean_param($filerecord->filearea, PARAM_AREA);
        if (empty($filerecord->filearea)) {
            throw new file_exception('storedfileproblem', 'Invalid filearea');
        }

        if (!is_number($filerecord->itemid) or $filerecord->itemid < 0) {
            throw new file_exception('storedfileproblem', 'Invalid itemid');
        }

        if (!empty($filerecord->sortorder)) {
            if (!is_number($filerecord->sortorder) or $filerecord->sortorder < 0) {
                $filerecord->sortorder = 0;
            }
        } else {
            $filerecord->sortorder = 0;
        }

        // TODO MDL-33416 [2.4] fields referencelastsync and referencelifetime to be removed from {files} table completely
        unset($filerecord->referencelastsync);
        unset($filerecord->referencelifetime);

        $filerecord->mimetype          = empty($filerecord->mimetype) ? $this->mimetype($filerecord->filename) : $filerecord->mimetype;
        $filerecord->userid            = empty($filerecord->userid) ? null : $filerecord->userid;
        $filerecord->source            = empty($filerecord->source) ? null : $filerecord->source;
        $filerecord->author            = empty($filerecord->author) ? null : $filerecord->author;
        $filerecord->license           = empty($filerecord->license) ? null : $filerecord->license;
        $filerecord->status            = empty($filerecord->status) ? 0 : $filerecord->status;
        $filerecord->filepath          = clean_param($filerecord->filepath, PARAM_PATH);
        if (strpos($filerecord->filepath, '/') !== 0 or strrpos($filerecord->filepath, '/') !== strlen($filerecord->filepath)-1) {
            // Path must start and end with '/'.
            throw new file_exception('storedfileproblem', 'Invalid file path');
        }

        $filerecord->filename = clean_param($filerecord->filename, PARAM_FILE);
        if ($filerecord->filename === '') {
            // Path must start and end with '/'.
            throw new file_exception('storedfileproblem', 'Invalid file name');
        }

        $now = time();
        if (isset($filerecord->timecreated)) {
            if (!is_number($filerecord->timecreated)) {
                throw new file_exception('storedfileproblem', 'Invalid file timecreated');
            }
            if ($filerecord->timecreated < 0) {
                // NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timecreated = 0;
            }
        } else {
            $filerecord->timecreated = $now;
        }

        if (isset($filerecord->timemodified)) {
            if (!is_number($filerecord->timemodified)) {
                throw new file_exception('storedfileproblem', 'Invalid file timemodified');
            }
            if ($filerecord->timemodified < 0) {
                // NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timemodified = 0;
            }
        } else {
            $filerecord->timemodified = $now;
        }

        $transaction = $DB->start_delegated_transaction();

        try {
            $filerecord->referencefileid = $this->get_or_create_referencefileid($repositoryid, $reference);
        } catch (Exception $e) {
            throw new file_reference_exception($repositoryid, $reference, null, null, $e->getMessage());
        }

        if (isset($filerecord->contenthash) && $this->content_exists($filerecord->contenthash)) {
            // there was specified the contenthash for a file already stored in moodle filepool
            if (empty($filerecord->filesize)) {
                $filepathname = $this->path_from_hash($filerecord->contenthash) . '/' . $filerecord->contenthash;
                $filerecord->filesize = filesize($filepathname);
            } else {
                $filerecord->filesize = clean_param($filerecord->filesize, PARAM_INT);
            }
        } else {
            // atempt to get the result of last synchronisation for this reference
            $lastcontent = $DB->get_record('files', array('referencefileid' => $filerecord->referencefileid),
                    'id, contenthash, filesize', IGNORE_MULTIPLE);
            if ($lastcontent) {
                $filerecord->contenthash = $lastcontent->contenthash;
                $filerecord->filesize = $lastcontent->filesize;
            } else {
                // External file doesn't have content in moodle.
                // So we create an empty file for it.
                list($filerecord->contenthash, $filerecord->filesize, $newfile) = $this->add_string_to_pool(null);
            }
        }

        $filerecord->pathnamehash = $this->get_pathname_hash($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->filename);

        try {
            $filerecord->id = $DB->insert_record('files', $filerecord);
        } catch (dml_exception $e) {
            if (!empty($newfile)) {
                $this->deleted_file_cleanup($filerecord->contenthash);
            }
            throw new stored_file_creation_exception($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid,
                                                    $filerecord->filepath, $filerecord->filename, $e->debuginfo);
        }

        $this->create_directory($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->userid);

        $transaction->allow_commit();

        // this will retrieve all reference information from DB as well
        return $this->get_file_by_id($filerecord->id);
    }

    /**
     * Creates new image file from existing.
     *
     * @param stdClass|array $filerecord object or array describing new file
     * @param int|stored_file $fid file id or stored file object
     * @param int $newwidth in pixels
     * @param int $newheight in pixels
     * @param bool $keepaspectratio whether or not keep aspect ratio
     * @param int $quality depending on image type 0-100 for jpeg, 0-9 (0 means no compression) for png
     * @return stored_file
     */
    public function convert_image($filerecord, $fid, $newwidth = null, $newheight = null, $keepaspectratio = true, $quality = null) {
        if (!function_exists('imagecreatefromstring')) {
            //Most likely the GD php extension isn't installed
            //image conversion cannot succeed
            throw new file_exception('storedfileproblem', 'imagecreatefromstring() doesnt exist. The PHP extension "GD" must be installed for image conversion.');
        }

        if ($fid instanceof stored_file) {
            $fid = $fid->get_id();
        }

        $filerecord = (array)$filerecord; // We support arrays too, do not modify the submitted record!

        if (!$file = $this->get_file_by_id($fid)) { // Make sure file really exists and we we correct data.
            throw new file_exception('storedfileproblem', 'File does not exist');
        }

        if (!$imageinfo = $file->get_imageinfo()) {
            throw new file_exception('storedfileproblem', 'File is not an image');
        }

        if (!isset($filerecord['filename'])) {
            $filerecord['filename'] = $file->get_filename();
        }

        if (!isset($filerecord['mimetype'])) {
            $filerecord['mimetype'] = $imageinfo['mimetype'];
        }

        $width    = $imageinfo['width'];
        $height   = $imageinfo['height'];
        $mimetype = $imageinfo['mimetype'];

        if ($keepaspectratio) {
            if (0 >= $newwidth and 0 >= $newheight) {
                // no sizes specified
                $newwidth  = $width;
                $newheight = $height;

            } else if (0 < $newwidth and 0 < $newheight) {
                $xheight = ($newwidth*($height/$width));
                if ($xheight < $newheight) {
                    $newheight = (int)$xheight;
                } else {
                    $newwidth = (int)($newheight*($width/$height));
                }

            } else if (0 < $newwidth) {
                $newheight = (int)($newwidth*($height/$width));

            } else { //0 < $newheight
                $newwidth = (int)($newheight*($width/$height));
            }

        } else {
            if (0 >= $newwidth) {
                $newwidth = $width;
            }
            if (0 >= $newheight) {
                $newheight = $height;
            }
        }

        $img = imagecreatefromstring($file->get_content());
        if ($height != $newheight or $width != $newwidth) {
            $newimg = imagecreatetruecolor($newwidth, $newheight);
            if (!imagecopyresized($newimg, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height)) {
                // weird
                throw new file_exception('storedfileproblem', 'Can not resize image');
            }
            imagedestroy($img);
            $img = $newimg;
        }

        ob_start();
        switch ($filerecord['mimetype']) {
            case 'image/gif':
                imagegif($img);
                break;

            case 'image/jpeg':
                if (is_null($quality)) {
                    imagejpeg($img);
                } else {
                    imagejpeg($img, NULL, $quality);
                }
                break;

            case 'image/png':
                $quality = (int)$quality;
                imagepng($img, NULL, $quality, NULL);
                break;

            default:
                throw new file_exception('storedfileproblem', 'Unsupported mime type');
        }

        $content = ob_get_contents();
        ob_end_clean();
        imagedestroy($img);

        if (!$content) {
            throw new file_exception('storedfileproblem', 'Can not convert image');
        }

        return $this->create_file_from_string($filerecord, $content);
    }

    /**
     * Add file content to sha1 pool.
     *
     * @param string $pathname path to file
     * @param string $contenthash sha1 hash of content if known (performance only)
     * @return array (contenthash, filesize, newfile)
     */
    public function add_file_to_pool($pathname, $contenthash = NULL) {
        if (!is_readable($pathname)) {
            throw new file_exception('storedfilecannotread', '', $pathname);
        }

        if (is_null($contenthash)) {
            $contenthash = sha1_file($pathname);
        }

        $filesize = filesize($pathname);

        $hashpath = $this->path_from_hash($contenthash);
        $hashfile = "$hashpath/$contenthash";

        if (file_exists($hashfile)) {
            if (filesize($hashfile) !== $filesize) {
                throw new file_pool_content_exception($contenthash);
            }
            $newfile = false;

        } else {
            if (!is_dir($hashpath)) {
                if (!mkdir($hashpath, $this->dirpermissions, true)) {
                    throw new file_exception('storedfilecannotcreatefiledirs'); // permission trouble
                }
            }
            $newfile = true;

            if (!copy($pathname, $hashfile)) {
                throw new file_exception('storedfilecannotread', '', $pathname);
            }

            if (filesize($hashfile) !== $filesize) {
                @unlink($hashfile);
                throw new file_pool_content_exception($contenthash);
            }
            chmod($hashfile, $this->filepermissions); // fix permissions if needed
        }


        return array($contenthash, $filesize, $newfile);
    }

    /**
     * Add string content to sha1 pool.
     *
     * @param string $content file content - binary string
     * @return array (contenthash, filesize, newfile)
     */
    public function add_string_to_pool($content) {
        $contenthash = sha1($content);
        $filesize = strlen($content); // binary length

        $hashpath = $this->path_from_hash($contenthash);
        $hashfile = "$hashpath/$contenthash";


        if (file_exists($hashfile)) {
            if (filesize($hashfile) !== $filesize) {
                throw new file_pool_content_exception($contenthash);
            }
            $newfile = false;

        } else {
            if (!is_dir($hashpath)) {
                if (!mkdir($hashpath, $this->dirpermissions, true)) {
                    throw new file_exception('storedfilecannotcreatefiledirs'); // permission trouble
                }
            }
            $newfile = true;

            file_put_contents($hashfile, $content);

            if (filesize($hashfile) !== $filesize) {
                @unlink($hashfile);
                throw new file_pool_content_exception($contenthash);
            }
            chmod($hashfile, $this->filepermissions); // fix permissions if needed
        }

        return array($contenthash, $filesize, $newfile);
    }

    /**
     * Serve file content using X-Sendfile header.
     * Please make sure that all headers are already sent
     * and the all access control checks passed.
     *
     * @param string $contenthash sah1 hash of the file content to be served
     * @return bool success
     */
    public function xsendfile($contenthash) {
        global $CFG;
        require_once("$CFG->libdir/xsendfilelib.php");

        $hashpath = $this->path_from_hash($contenthash);
        return xsendfile("$hashpath/$contenthash");
    }

    /**
     * Content exists
     *
     * @param string $contenthash
     * @return bool
     */
    public function content_exists($contenthash) {
        $dir = $this->path_from_hash($contenthash);
        $filepath = $dir . '/' . $contenthash;
        return file_exists($filepath);
    }

    /**
     * Return path to file with given hash.
     *
     * NOTE: must not be public, files in pool must not be modified
     *
     * @param string $contenthash content hash
     * @return string expected file location
     */
    protected function path_from_hash($contenthash) {
        $l1 = $contenthash[0].$contenthash[1];
        $l2 = $contenthash[2].$contenthash[3];
        return "$this->filedir/$l1/$l2";
    }

    /**
     * Return path to file with given hash.
     *
     * NOTE: must not be public, files in pool must not be modified
     *
     * @param string $contenthash content hash
     * @return string expected file location
     */
    protected function trash_path_from_hash($contenthash) {
        $l1 = $contenthash[0].$contenthash[1];
        $l2 = $contenthash[2].$contenthash[3];
        return "$this->trashdir/$l1/$l2";
    }

    /**
     * Tries to recover missing content of file from trash.
     *
     * @param stored_file $file stored_file instance
     * @return bool success
     */
    public function try_content_recovery($file) {
        $contenthash = $file->get_contenthash();
        $trashfile = $this->trash_path_from_hash($contenthash).'/'.$contenthash;
        if (!is_readable($trashfile)) {
            if (!is_readable($this->trashdir.'/'.$contenthash)) {
                return false;
            }
            // nice, at least alternative trash file in trash root exists
            $trashfile = $this->trashdir.'/'.$contenthash;
        }
        if (filesize($trashfile) != $file->get_filesize() or sha1_file($trashfile) != $contenthash) {
            //weird, better fail early
            return false;
        }
        $contentdir  = $this->path_from_hash($contenthash);
        $contentfile = $contentdir.'/'.$contenthash;
        if (file_exists($contentfile)) {
            //strange, no need to recover anything
            return true;
        }
        if (!is_dir($contentdir)) {
            if (!mkdir($contentdir, $this->dirpermissions, true)) {
                return false;
            }
        }
        return rename($trashfile, $contentfile);
    }

    /**
     * Marks pool file as candidate for deleting.
     *
     * DO NOT call directly - reserved for core!!
     *
     * @param string $contenthash
     */
    public function deleted_file_cleanup($contenthash) {
        global $DB;

        //Note: this section is critical - in theory file could be reused at the same
        //      time, if this happens we can still recover the file from trash
        if ($DB->record_exists('files', array('contenthash'=>$contenthash))) {
            // file content is still used
            return;
        }
        //move content file to trash
        $contentfile = $this->path_from_hash($contenthash).'/'.$contenthash;
        if (!file_exists($contentfile)) {
            //weird, but no problem
            return;
        }
        $trashpath = $this->trash_path_from_hash($contenthash);
        $trashfile = $trashpath.'/'.$contenthash;
        if (file_exists($trashfile)) {
            // we already have this content in trash, no need to move it there
            unlink($contentfile);
            return;
        }
        if (!is_dir($trashpath)) {
            mkdir($trashpath, $this->dirpermissions, true);
        }
        rename($contentfile, $trashfile);
        chmod($trashfile, $this->filepermissions); // fix permissions if needed
    }

    /**
     * When user referring to a moodle file, we build the reference field
     *
     * @param array $params
     * @return string
     */
    public static function pack_reference($params) {
        $params = (array)$params;
        $reference = array();
        $reference['contextid'] = is_null($params['contextid']) ? null : clean_param($params['contextid'], PARAM_INT);
        $reference['component'] = is_null($params['component']) ? null : clean_param($params['component'], PARAM_COMPONENT);
        $reference['itemid']    = is_null($params['itemid'])    ? null : clean_param($params['itemid'],    PARAM_INT);
        $reference['filearea']  = is_null($params['filearea'])  ? null : clean_param($params['filearea'],  PARAM_AREA);
        $reference['filepath']  = is_null($params['filepath'])  ? null : clean_param($params['filepath'],  PARAM_PATH);;
        $reference['filename']  = is_null($params['filename'])  ? null : clean_param($params['filename'],  PARAM_FILE);
        return base64_encode(serialize($reference));
    }

    /**
     * Unpack reference field
     *
     * @param string $str
     * @param bool $cleanparams if set to true, array elements will be passed through {@link clean_param()}
     * @throws file_reference_exception if the $str does not have the expected format
     * @return array
     */
    public static function unpack_reference($str, $cleanparams = false) {
        $decoded = base64_decode($str, true);
        if ($decoded === false) {
            throw new file_reference_exception(null, $str, null, null, 'Invalid base64 format');
        }
        $params = @unserialize($decoded); // hide E_NOTICE
        if ($params === false) {
            throw new file_reference_exception(null, $decoded, null, null, 'Not an unserializeable value');
        }
        if (is_array($params) && $cleanparams) {
            $params = array(
                'component' => is_null($params['component']) ? ''   : clean_param($params['component'], PARAM_COMPONENT),
                'filearea'  => is_null($params['filearea'])  ? ''   : clean_param($params['filearea'], PARAM_AREA),
                'itemid'    => is_null($params['itemid'])    ? 0    : clean_param($params['itemid'], PARAM_INT),
                'filename'  => is_null($params['filename'])  ? null : clean_param($params['filename'], PARAM_FILE),
                'filepath'  => is_null($params['filepath'])  ? null : clean_param($params['filepath'], PARAM_PATH),
                'contextid' => is_null($params['contextid']) ? null : clean_param($params['contextid'], PARAM_INT)
            );
        }
        return $params;
    }

    /**
     * Returns all aliases that refer to some stored_file via the given reference
     *
     * All repositories that provide access to a stored_file are expected to use
     * {@link self::pack_reference()}. This method can't be used if the given reference
     * does not use this format or if you are looking for references to an external file
     * (for example it can't be used to search for all aliases that refer to a given
     * Dropbox or Box.net file).
     *
     * Aliases in user draft areas are excluded from the returned list.
     *
     * @param string $reference identification of the referenced file
     * @return array of stored_file indexed by its pathnamehash
     */
    public function search_references($reference) {
        global $DB;

        if (is_null($reference)) {
            throw new coding_exception('NULL is not a valid reference to an external file');
        }

        // Give {@link self::unpack_reference()} a chance to throw exception if the
        // reference is not in a valid format.
        self::unpack_reference($reference);

        $referencehash = sha1($reference);

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
                  JOIN {files_reference} r ON f.referencefileid = r.id
                  JOIN {repository_instances} ri ON r.repositoryid = ri.id
                 WHERE r.referencehash = ?
                       AND (f.component <> ? OR f.filearea <> ?)";

        $rs = $DB->get_recordset_sql($sql, array($referencehash, 'user', 'draft'));
        $files = array();
        foreach ($rs as $filerecord) {
            $files[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
        }

        return $files;
    }

    /**
     * Returns the number of aliases that refer to some stored_file via the given reference
     *
     * All repositories that provide access to a stored_file are expected to use
     * {@link self::pack_reference()}. This method can't be used if the given reference
     * does not use this format or if you are looking for references to an external file
     * (for example it can't be used to count aliases that refer to a given Dropbox or
     * Box.net file).
     *
     * Aliases in user draft areas are not counted.
     *
     * @param string $reference identification of the referenced file
     * @return int
     */
    public function search_references_count($reference) {
        global $DB;

        if (is_null($reference)) {
            throw new coding_exception('NULL is not a valid reference to an external file');
        }

        // Give {@link self::unpack_reference()} a chance to throw exception if the
        // reference is not in a valid format.
        self::unpack_reference($reference);

        $referencehash = sha1($reference);

        $sql = "SELECT COUNT(f.id)
                  FROM {files} f
                  JOIN {files_reference} r ON f.referencefileid = r.id
                  JOIN {repository_instances} ri ON r.repositoryid = ri.id
                 WHERE r.referencehash = ?
                       AND (f.component <> ? OR f.filearea <> ?)";

        return (int)$DB->count_records_sql($sql, array($referencehash, 'user', 'draft'));
    }

    /**
     * Returns all aliases that link to the given stored_file
     *
     * Aliases in user draft areas are excluded from the returned list.
     *
     * @param stored_file $storedfile
     * @return array of stored_file
     */
    public function get_references_by_storedfile(stored_file $storedfile) {
        global $DB;

        $params = array();
        $params['contextid'] = $storedfile->get_contextid();
        $params['component'] = $storedfile->get_component();
        $params['filearea']  = $storedfile->get_filearea();
        $params['itemid']    = $storedfile->get_itemid();
        $params['filename']  = $storedfile->get_filename();
        $params['filepath']  = $storedfile->get_filepath();

        return $this->search_references(self::pack_reference($params));
    }

    /**
     * Returns the number of aliases that link to the given stored_file
     *
     * Aliases in user draft areas are not counted.
     *
     * @param stored_file $storedfile
     * @return int
     */
    public function get_references_count_by_storedfile(stored_file $storedfile) {
        global $DB;

        $params = array();
        $params['contextid'] = $storedfile->get_contextid();
        $params['component'] = $storedfile->get_component();
        $params['filearea']  = $storedfile->get_filearea();
        $params['itemid']    = $storedfile->get_itemid();
        $params['filename']  = $storedfile->get_filename();
        $params['filepath']  = $storedfile->get_filepath();

        return $this->search_references_count(self::pack_reference($params));
    }

    /**
     * Updates all files that are referencing this file with the new contenthash
     * and filesize
     *
     * @param stored_file $storedfile
     */
    public function update_references_to_storedfile(stored_file $storedfile) {
        global $CFG;
        $params = array();
        $params['contextid'] = $storedfile->get_contextid();
        $params['component'] = $storedfile->get_component();
        $params['filearea']  = $storedfile->get_filearea();
        $params['itemid']    = $storedfile->get_itemid();
        $params['filename']  = $storedfile->get_filename();
        $params['filepath']  = $storedfile->get_filepath();
        $reference = self::pack_reference($params);
        $referencehash = sha1($reference);

        $sql = "SELECT repositoryid, id FROM {files_reference}
                 WHERE referencehash = ? and reference = ?";
        $rs = $DB->get_recordset_sql($sql, array($referencehash, $reference));

        $now = time();
        foreach ($rs as $record) {
            require_once($CFG->dirroot.'/repository/lib.php');
            $repo = repository::get_instance($record->repositoryid);
            $lifetime = $repo->get_reference_file_lifetime($reference);
            $this->update_references($record->id, $now, $lifetime,
                    $storedfile->get_contenthash(), $storedfile->get_filesize(), 0);
        }
        $rs->close();
    }

    /**
     * Convert file alias to local file
     *
     * @throws moodle_exception if file could not be downloaded
     *
     * @param stored_file $storedfile a stored_file instances
     * @param int $maxbytes throw an exception if file size is bigger than $maxbytes (0 means no limit)
     * @return stored_file stored_file
     */
    public function import_external_file(stored_file $storedfile, $maxbytes = 0) {
        global $CFG;
        $storedfile->import_external_file_contents($maxbytes);
        $storedfile->delete_reference();
        return $storedfile;
    }

    /**
     * Return mimetype by given file pathname
     *
     * If file has a known extension, we return the mimetype based on extension.
     * Otherwise (when possible) we try to get the mimetype from file contents.
     *
     * @param string $pathname full path to the file
     * @param string $filename correct file name with extension, if omitted will be taken from $path
     * @return string
     */
    public static function mimetype($pathname, $filename = null) {
        if (empty($filename)) {
            $filename = $pathname;
        }
        $type = mimeinfo('type', $filename);
        if ($type === 'document/unknown' && class_exists('finfo') && file_exists($pathname)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $type = mimeinfo_from_type('type', $finfo->file($pathname));
        }
        return $type;
    }

    /**
     * Cron cleanup job.
     */
    public function cron() {
        global $CFG, $DB;

        // find out all stale draft areas (older than 4 days) and purge them
        // those are identified by time stamp of the /. root dir
        mtrace('Deleting old draft files... ', '');
        $old = time() - 60*60*24*4;
        $sql = "SELECT *
                  FROM {files}
                 WHERE component = 'user' AND filearea = 'draft' AND filepath = '/' AND filename = '.'
                       AND timecreated < :old";
        $rs = $DB->get_recordset_sql($sql, array('old'=>$old));
        foreach ($rs as $dir) {
            $this->delete_area_files($dir->contextid, $dir->component, $dir->filearea, $dir->itemid);
        }
        $rs->close();
        mtrace('done.');

        // remove orphaned preview files (that is files in the core preview filearea without
        // the existing original file)
        mtrace('Deleting orphaned preview files... ', '');
        $sql = "SELECT p.*
                  FROM {files} p
             LEFT JOIN {files} o ON (p.filename = o.contenthash)
                 WHERE p.contextid = ? AND p.component = 'core' AND p.filearea = 'preview' AND p.itemid = 0
                       AND o.id IS NULL";
        $syscontext = context_system::instance();
        $rs = $DB->get_recordset_sql($sql, array($syscontext->id));
        foreach ($rs as $orphan) {
            $file = $this->get_file_instance($orphan);
            if (!$file->is_directory()) {
                $file->delete();
            }
        }
        $rs->close();
        mtrace('done.');

        // remove trash pool files once a day
        // if you want to disable purging of trash put $CFG->fileslastcleanup=time(); into config.php
        if (empty($CFG->fileslastcleanup) or $CFG->fileslastcleanup < time() - 60*60*24) {
            require_once($CFG->libdir.'/filelib.php');
            // Delete files that are associated with a context that no longer exists.
            mtrace('Cleaning up files from deleted contexts... ', '');
            $sql = "SELECT DISTINCT f.contextid
                    FROM {files} f
                    LEFT OUTER JOIN {context} c ON f.contextid = c.id
                    WHERE c.id IS NULL";
            $rs = $DB->get_recordset_sql($sql);
            if ($rs->valid()) {
                $fs = get_file_storage();
                foreach ($rs as $ctx) {
                    $fs->delete_area_files($ctx->contextid);
                }
            }
            $rs->close();
            mtrace('done.');

            mtrace('Deleting trash files... ', '');
            fulldelete($this->trashdir);
            set_config('fileslastcleanup', time());
            mtrace('done.');
        }
    }

    /**
     * Get the sql formated fields for a file instance to be created from a
     * {files} and {files_refernece} join.
     *
     * @param string $filesprefix the table prefix for the {files} table
     * @param string $filesreferenceprefix the table prefix for the {files_reference} table
     * @return string the sql to go after a SELECT
     */
    private static function instance_sql_fields($filesprefix, $filesreferenceprefix) {
        // Note, these fieldnames MUST NOT overlap between the two tables,
        // else problems like MDL-33172 occur.
        $filefields = array('contenthash', 'pathnamehash', 'contextid', 'component', 'filearea',
            'itemid', 'filepath', 'filename', 'userid', 'filesize', 'mimetype', 'status', 'source',
            'author', 'license', 'timecreated', 'timemodified', 'sortorder', 'referencefileid');

        $referencefields = array('repositoryid' => 'repositoryid',
            'reference' => 'reference',
            'lastsync' => 'referencelastsync',
            'lifetime' => 'referencelifetime');

        // id is specifically named to prevent overlaping between the two tables.
        $fields = array();
        $fields[] = $filesprefix.'.id AS id';
        foreach ($filefields as $field) {
            $fields[] = "{$filesprefix}.{$field}";
        }

        foreach ($referencefields as $field => $alias) {
            $fields[] = "{$filesreferenceprefix}.{$field} AS {$alias}";
        }

        return implode(', ', $fields);
    }

    /**
     * Returns the id of the record in {files_reference} that matches the passed repositoryid and reference
     *
     * If the record already exists, its id is returned. If there is no such record yet,
     * new one is created (using the lastsync and lifetime provided, too) and its id is returned.
     *
     * @param int $repositoryid
     * @param string $reference
     * @return int
     */
    private function get_or_create_referencefileid($repositoryid, $reference, $lastsync = null, $lifetime = null) {
        global $DB;

        $id = $this->get_referencefileid($repositoryid, $reference, IGNORE_MISSING);

        if ($id !== false) {
            // bah, that was easy
            return $id;
        }

        // no such record yet, create one
        try {
            $id = $DB->insert_record('files_reference', array(
                'repositoryid'  => $repositoryid,
                'reference'     => $reference,
                'referencehash' => sha1($reference),
                'lastsync'      => $lastsync,
                'lifetime'      => $lifetime));
        } catch (dml_exception $e) {
            // if inserting the new record failed, chances are that the race condition has just
            // occured and the unique index did not allow to create the second record with the same
            // repositoryid + reference combo
            $id = $this->get_referencefileid($repositoryid, $reference, MUST_EXIST);
        }

        return $id;
    }

    /**
     * Returns the id of the record in {files_reference} that matches the passed parameters
     *
     * Depending on the required strictness, false can be returned. The behaviour is consistent
     * with standard DML methods.
     *
     * @param int $repositoryid
     * @param string $reference
     * @param int $strictness either {@link IGNORE_MISSING}, {@link IGNORE_MULTIPLE} or {@link MUST_EXIST}
     * @return int|bool
     */
    private function get_referencefileid($repositoryid, $reference, $strictness) {
        global $DB;

        return $DB->get_field('files_reference', 'id',
            array('repositoryid' => $repositoryid, 'referencehash' => sha1($reference)), $strictness);
    }

    /**
     * Updates a reference to the external resource and all files that use it
     *
     * This function is called after synchronisation of an external file and updates the
     * contenthash, filesize and status of all files that reference this external file
     * as well as time last synchronised and sync lifetime (how long we don't need to call
     * synchronisation for this reference).
     *
     * @param int $referencefileid
     * @param int $lastsync
     * @param int $lifetime
     * @param string $contenthash
     * @param int $filesize
     * @param int $status 0 if ok or 666 if source is missing
     */
    public function update_references($referencefileid, $lastsync, $lifetime, $contenthash, $filesize, $status) {
        global $DB;
        $referencefileid = clean_param($referencefileid, PARAM_INT);
        $lastsync = clean_param($lastsync, PARAM_INT);
        $lifetime = clean_param($lifetime, PARAM_INT);
        validate_param($contenthash, PARAM_TEXT, NULL_NOT_ALLOWED);
        $filesize = clean_param($filesize, PARAM_INT);
        $status = clean_param($status, PARAM_INT);
        $params = array('contenthash' => $contenthash,
                    'filesize' => $filesize,
                    'status' => $status,
                    'referencefileid' => $referencefileid,
                    'lastsync' => $lastsync,
                    'lifetime' => $lifetime);
        $DB->execute('UPDATE {files} SET contenthash = :contenthash, filesize = :filesize,
            status = :status, referencelastsync = :lastsync, referencelifetime = :lifetime
            WHERE referencefileid = :referencefileid', $params);
        $data = array('id' => $referencefileid, 'lastsync' => $lastsync, 'lifetime' => $lifetime);
        $DB->update_record('files_reference', (object)$data);
    }
}
