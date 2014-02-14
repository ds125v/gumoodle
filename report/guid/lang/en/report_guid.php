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
 * GUID report
 *
 * @package    report_guid
 * @copyright  2013 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['accountcreated'] = 'Account created for {$a}';
$string['accountexists'] = 'Account not created, {$a} already exists';
$string['counterrors'] = '{$a} lines caused an error';
$string['countexistingaccounts'] = '{$a} accounts already existed';
$string['countnewaccounts'] = '{$a} new accounts created';
$string['create'] = '(Create an account for this user)';
$string['csvfile'] = 'CSV File';
$string['email'] = 'Email address';
$string['emptycsv'] = 'CSV file is empty';
$string['enrolments'] = 'Enrolments for {$a}';
$string['enrolmentsonsite'] = 'Enrolments on {$a} Moodle';
$string['externalmail'] = 'Emails in italics are non UofG addresses';
$string['filtererror'] = 'Error building filter. Please refine your search and try again';
$string['firstname'] = 'First name';
$string['guid'] = 'GUID Search';
$string['guid:view'] = 'View GUID form';
$string['guidform'] = 'GUID';
$string['guidnomatch'] = 'GUID does not match in data (name changed?)';
$string['heading'] = 'GUID Search';
$string['instructions'] = 'Enter whatever you know about the user. Use a * for wildcards (e.g. Mc*). Data Vault will be searched for matches.';
$string['lastname'] = 'Last name';
$string['ldapnotloaded'] = 'LDAP drivers are not loaded';
$string['ldapsearcherror'] = 'LDAP search failed (perhaps try with debugging on)';
$string['more'] = 'more...';
$string['moreresults'] = 'There are more results (not shown). Please give more specific search criteria';
$string['mycampus'] = 'MyCampus enrolments';
$string['multipleresults'] = 'Error - unexpected multiple results';
$string['noemail'] = '(Cannot create Moodle account - no email)';
$string['noenrolments'] = 'No Moodle enrolment data found for this user';
$string['nogudatabase'] = 'gudatabase enrolment plugin is not configured (needed for MyCampus results)';
$string['noguenrol'] = 'GUSYNC local plugin is not configured (needed for enrolment results)';
$string['nomycampus'] = 'No MyCampus data for this user';
$string['noresults'] = 'No results for this search';
$string['nouser'] = 'Error - unable to find the user in LDAP';
$string['numbercsvlines'] = 'Number of lines in CSV file = {$a}';
$string['numberofresults'] = 'Number of results = {$a}';
$string['pluginname'] = 'GUID search';
$string['resultfor'] = 'LDAP record for user';
$string['search'] = 'Search';
$string['searcherror'] = 'Error returned by search (possibly too many results). Please refine your search and try again ({$a})';
$string['submitfile'] = 'Upload CSV file';
$string['uploadfile'] = 'CSV file';
$string['uploadheader'] = 'Upload csv file';
$string['uploadinstructions'] = 'Upload a csv file. First column must contain the GUID of the users. Subsequent columns (if present) are completely ignored. The first line (headings) is also ignored';
$string['uploadguid'] = 'Create users from uploaded CSV file';
$string['usercreated'] = 'User has been created ({$a})';

