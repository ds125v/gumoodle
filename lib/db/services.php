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
 * Core external functions and service definitions.
 *
 * The functions and services defined on this file are
 * processed and registered into the Moodle DB after any
 * install or upgrade operation. All plugins support this.
 *
 * For more information, take a look to the documentation available:
 *     - Webservices API: {@link http://docs.moodle.org/dev/Web_services_API}
 *     - External API: {@link http://docs.moodle.org/dev/External_functions_API}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package    core_webservice
 * @category   webservice
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    // === group related functions ===

    'moodle_group_create_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'create_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_group_create_groups(). ',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'core_group_create_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'create_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'Creates new groups.',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'moodle_group_get_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'get_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_group_get_groups()',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'core_group_get_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'get_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'Returns group details.',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'moodle_group_get_course_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'get_course_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_group_get_course_groups()',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'core_group_get_course_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'get_course_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'Returns all groups in specified course.',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'moodle_group_delete_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'delete_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_group_delete_groups()',
        'type'        => 'delete',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'core_group_delete_groups' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'delete_groups',
        'classpath'   => 'group/externallib.php',
        'description' => 'Deletes all specified groups.',
        'type'        => 'delete',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'moodle_group_get_groupmembers' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'get_group_members',
        'classpath'   => 'group/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_group_get_group_members()',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'core_group_get_group_members' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'get_group_members',
        'classpath'   => 'group/externallib.php',
        'description' => 'Returns group members.',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'moodle_group_add_groupmembers' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'add_group_members',
        'classpath'   => 'group/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_group_add_group_members()',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'core_group_add_group_members' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'add_group_members',
        'classpath'   => 'group/externallib.php',
        'description' => 'Adds group members.',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'moodle_group_delete_groupmembers' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'delete_group_members',
        'classpath'   => 'group/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_group_delete_group_members()',
        'type'        => 'delete',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'core_group_delete_group_members' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'delete_group_members',
        'classpath'   => 'group/externallib.php',
        'description' => 'Deletes group members.',
        'type'        => 'delete',
        'capabilities'=> 'moodle/course:managegroups',
    ),

    'core_group_create_groupings' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'create_groupings',
        'classpath'   => 'group/externallib.php',
        'description' => 'Creates new groupings',
        'type'        => 'write',
    ),

    'core_group_update_groupings' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'update_groupings',
        'classpath'   => 'group/externallib.php',
        'description' => 'Updates existing groupings',
        'type'        => 'write',
    ),

    'core_group_get_groupings' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'get_groupings',
        'classpath'   => 'group/externallib.php',
        'description' => 'Returns groupings details.',
        'type'        => 'read',
    ),

    'core_group_get_course_groupings' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'get_course_groupings',
        'classpath'   => 'group/externallib.php',
        'description' => 'Returns all groupings in specified course.',
        'type'        => 'read',
    ),

    'core_group_delete_groupings' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'delete_groupings',
        'classpath'   => 'group/externallib.php',
        'description' => 'Deletes all specified groupings.',
        'type'        => 'write',
    ),

    'core_group_assign_grouping' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'assign_grouping',
        'classpath'   => 'group/externallib.php',
        'description' => 'Assing groups from groupings',
        'type'        => 'write',
    ),

    'core_group_unassign_grouping' => array(
        'classname'   => 'core_group_external',
        'methodname'  => 'unassign_grouping',
        'classpath'   => 'group/externallib.php',
        'description' => 'Unassing groups from groupings',
        'type'        => 'write',
    ),

    // === file related functions ===

    'moodle_file_get_files' => array(
        'classname'   => 'core_files_external',
        'methodname'  => 'get_files',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_files_get_files()',
        'type'        => 'read',
        'classpath'   => 'files/externallib.php',
    ),

    'core_files_get_files' => array(
        'classname'   => 'core_files_external',
        'methodname'  => 'get_files',
        'description' => 'browse moodle files',
        'type'        => 'read',
        'classpath'   => 'files/externallib.php',
    ),

    'moodle_file_upload' => array(
        'classname'   => 'core_files_external',
        'methodname'  => 'upload',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_files_upload()',
        'type'        => 'write',
        'classpath'   => 'files/externallib.php',
    ),

    'core_files_upload' => array(
        'classname'   => 'core_files_external',
        'methodname'  => 'upload',
        'description' => 'upload a file to moodle',
        'type'        => 'write',
        'classpath'   => 'files/externallib.php',
    ),

    // === user related functions ===

    'moodle_user_create_users' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'create_users',
        'classpath'   => 'user/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_user_create_users()',
        'type'        => 'write',
        'capabilities'=> 'moodle/user:create',
    ),

    'core_user_create_users' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'create_users',
        'classpath'   => 'user/externallib.php',
        'description' => 'Create users.',
        'type'        => 'write',
        'capabilities'=> 'moodle/user:create',
    ),

    'moodle_user_get_users_by_id' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'get_users_by_id',
        'classpath'   => 'user/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_user_get_users_by_id()',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update',
    ),

    'core_user_get_users_by_id' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'get_users_by_id',
        'classpath'   => 'user/externallib.php',
        'description' => 'Get users by id.',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update',
    ),

    'moodle_user_get_users_by_courseid' => array(
        'classname'   => 'core_enrol_external',
        'methodname'  => 'get_enrolled_users',
        'classpath'   => 'enrol/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_enrol_get_enrolled_users()',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update, moodle/site:accessallgroups',
    ),

    'moodle_user_get_course_participants_by_id' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'get_course_user_profiles',
        'classpath'   => 'user/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_user_get_course_user_profiles()',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update, moodle/site:accessallgroups',
    ),

    'core_user_get_course_user_profiles' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'get_course_user_profiles',
        'classpath'   => 'user/externallib.php',
        'description' => 'Get course user profiles (each of the profils matching a course id and a user id).',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update, moodle/site:accessallgroups',
    ),

    'moodle_user_delete_users' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'delete_users',
        'classpath'   => 'user/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_user_delete_users()',
        'type'        => 'write',
        'capabilities'=> 'moodle/user:delete',
    ),

    'core_user_delete_users' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'delete_users',
        'classpath'   => 'user/externallib.php',
        'description' => 'Delete users.',
        'type'        => 'write',
        'capabilities'=> 'moodle/user:delete',
    ),

    'moodle_user_update_users' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'update_users',
        'classpath'   => 'user/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_user_update_users()',
        'type'        => 'write',
        'capabilities'=> 'moodle/user:update',
    ),

    'core_user_update_users' => array(
        'classname'   => 'core_user_external',
        'methodname'  => 'update_users',
        'classpath'   => 'user/externallib.php',
        'description' => 'Update users.',
        'type'        => 'write',
        'capabilities'=> 'moodle/user:update',
    ),

    // === enrol related functions ===

    'moodle_enrol_get_enrolled_users' => array(
        'classname'   => 'moodle_enrol_external',
        'methodname'  => 'get_enrolled_users',
        'classpath'   => 'enrol/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. Please use core_enrol_get_enrolled_users() (previously known as moodle_user_get_users_by_courseid).',
        'type'        => 'read',
        'capabilities'=> 'moodle/site:viewparticipants, moodle/course:viewparticipants,
            moodle/role:review, moodle/site:accessallgroups, moodle/course:enrolreview',
    ),

    'core_enrol_get_enrolled_users' => array(
        'classname'   => 'core_enrol_external',
        'methodname'  => 'get_enrolled_users',
        'classpath'   => 'enrol/externallib.php',
        'description' => 'Get enrolled users by course id.',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update, moodle/site:accessallgroups',
    ),

    'moodle_enrol_get_users_courses' => array(
        'classname'   => 'core_enrol_external',
        'methodname'  => 'get_users_courses',
        'classpath'   => 'enrol/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_enrol_get_users_courses()',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:viewparticipants',
    ),

    'core_enrol_get_users_courses' => array(
        'classname'   => 'core_enrol_external',
        'methodname'  => 'get_users_courses',
        'classpath'   => 'enrol/externallib.php',
        'description' => 'Get the list of courses where a user is enrolled in',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:viewparticipants',
    ),

    // === Role related functions ===

    'moodle_role_assign' => array(
        'classname'   => 'core_role_external',
        'methodname'  => 'assign_roles',
        'classpath'   => 'enrol/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_role_assign_role()',
        'type'        => 'write',
        'capabilities'=> 'moodle/role:assign',
    ),

    'core_role_assign_roles' => array(
        'classname'   => 'core_role_external',
        'methodname'  => 'assign_roles',
        'classpath'   => 'enrol/externallib.php',
        'description' => 'Manual role assignments.',
        'type'        => 'write',
        'capabilities'=> 'moodle/role:assign',
    ),

    'moodle_role_unassign' => array(
        'classname'   => 'core_role_external',
        'methodname'  => 'unassign_roles',
        'classpath'   => 'enrol/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_role_unassign_role()',
        'type'        => 'write',
        'capabilities'=> 'moodle/role:assign',
    ),

    'core_role_unassign_roles' => array(
        'classname'   => 'core_role_external',
        'methodname'  => 'unassign_roles',
        'classpath'   => 'enrol/externallib.php',
        'description' => 'Manual role unassignments.',
        'type'        => 'write',
        'capabilities'=> 'moodle/role:assign',
    ),

    // === course related functions ===

    'core_course_get_contents' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'get_course_contents',
        'classpath'   => 'course/externallib.php',
        'description' => 'Get course contents',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:update,moodle/course:viewhiddencourses',
    ),

    'moodle_course_get_courses' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'get_courses',
        'classpath'   => 'course/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_course_get_courses()',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:view,moodle/course:update,moodle/course:viewhiddencourses',
    ),

    'core_course_get_courses' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'get_courses',
        'classpath'   => 'course/externallib.php',
        'description' => 'Return course details',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:view,moodle/course:update,moodle/course:viewhiddencourses',
    ),

    'moodle_course_create_courses' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'create_courses',
        'classpath'   => 'course/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_course_create_courses()',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:create,moodle/course:visibility',
    ),

    'core_course_create_courses' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'create_courses',
        'classpath'   => 'course/externallib.php',
        'description' => 'Create new courses',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:create,moodle/course:visibility',
    ),

    'core_course_delete_courses' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'delete_courses',
        'classpath'   => 'course/externallib.php',
        'description' => 'Deletes all specified courses',
        'type'        => 'write',
        'capabilities'=> 'moodle/course:delete',
    ),

    'core_course_duplicate_course' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'duplicate_course',
        'classpath'   => 'course/externallib.php',
        'description' => 'Duplicate an existing course (creating a new one) without user data',
        'type'        => 'write',
        'capabilities'=> 'moodle/backup:backupcourse,moodle/restore:restorecourse,moodle/course:create',
    ),

    // === course category related functions ===

    'core_course_get_categories' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'get_categories',
        'classpath'   => 'course/externallib.php',
        'description' => 'Return category details',
        'type'        => 'read',
        'capabilities'=> 'moodle/category:viewhiddencategories',
    ),

    'core_course_create_categories' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'create_categories',
        'classpath'   => 'course/externallib.php',
        'description' => 'Create course categories',
        'type'        => 'write',
        'capabilities'=> 'moodle/category:manage',
    ),

    'core_course_update_categories' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'update_categories',
        'classpath'   => 'course/externallib.php',
        'description' => 'Update categories',
        'type'        => 'write',
        'capabilities'=> 'moodle/category:manage',
    ),

    'core_course_delete_categories' => array(
        'classname'   => 'core_course_external',
        'methodname'  => 'delete_categories',
        'classpath'   => 'course/externallib.php',
        'description' => 'Delete course categories',
        'type'        => 'write',
        'capabilities'=> 'moodle/category:manage',
    ),

    // === message related functions ===

    'moodle_message_send_instantmessages' => array(
        'classname'   => 'core_message_external',
        'methodname'  => 'send_instant_messages',
        'classpath'   => 'message/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_message_send_instant_messages()',
        'type'        => 'write',
        'capabilities'=> 'moodle/site:sendmessage',
    ),

    'core_message_send_instant_messages' => array(
        'classname'   => 'core_message_external',
        'methodname'  => 'send_instant_messages',
        'classpath'   => 'message/externallib.php',
        'description' => 'Send instant messages',
        'type'        => 'write',
        'capabilities'=> 'moodle/site:sendmessage',
    ),

    // === notes related functions ===

    'moodle_notes_create_notes' => array(
        'classname'   => 'core_notes_external',
        'methodname'  => 'create_notes',
        'classpath'   => 'notes/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_notes_create_notes()',
        'type'        => 'write',
        'capabilities'=> 'moodle/notes:manage',
    ),

    'core_notes_create_notes' => array(
        'classname'   => 'core_notes_external',
        'methodname'  => 'create_notes',
        'classpath'   => 'notes/externallib.php',
        'description' => 'Create notes',
        'type'        => 'write',
        'capabilities'=> 'moodle/notes:manage',
    ),

    // === webservice related functions ===

    'moodle_webservice_get_siteinfo' => array(
        'classname'   => 'core_webservice_external',
        'methodname'  => 'get_site_info',
        'classpath'   => 'webservice/externallib.php',
        'description' => 'DEPRECATED: this deprecated function will be removed in a future version. This function has be renamed as core_webservice_get_site_info()',
        'type'        => 'read',
    ),

    'core_webservice_get_site_info' => array(
        'classname'   => 'core_webservice_external',
        'methodname'  => 'get_site_info',
        'classpath'   => 'webservice/externallib.php',
        'description' => 'Return some site info / user info / list web service functions',
        'type'        => 'read',
    ),

);

$services = array(
   'Moodle mobile web service'  => array(
        'functions' => array (
            'moodle_enrol_get_users_courses',
            'moodle_enrol_get_enrolled_users',
            'moodle_user_get_users_by_id',
            'moodle_webservice_get_siteinfo',
            'moodle_notes_create_notes',
            'moodle_user_get_course_participants_by_id',
            'moodle_user_get_users_by_courseid',
            'moodle_message_send_instantmessages',
            'core_course_get_contents'),
        'enabled' => 0,
        'restrictedusers' => 0,
        'shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE,
        'downloadfiles' => 1
    ),
);
