@core @core_filepicker @repository @repository_user @_only_local @_file_upload
Feature: Create shortcuts
  In order to automatically synchronize copies of the file with the source
  As a teacher
  I need to be able to pick file as a shortcut

  @javascript @_bug_phantomjs
  Scenario: Upload a file as a copy and as a shortcut in filemanager
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Terry | Teacher | teacher1@asd.com |
    And the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    When I log in as "teacher1"
    And I expand "My profile" node
    And I follow "My private files"
    And I upload "lib/tests/fixtures/empty.txt" file to "Files" filemanager
    Then I should see "empty.txt" in the "div.fp-content" "css_element"
    And I press "Save changes"
    And I am on homepage
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Folder" to section "1"
    And I fill the moodle form with:
      | Name        | Test folder             |
      | Description | Test folder description |
    And I add "empty.txt" file from "Private files" to "Files" filemanager
    And I should see "1" elements in "Files" filemanager
    And I should see "empty.txt" in the ".fp-content .fp-file" "css_element"
    And ".fp-content .fp-file.fp-isreference" "css_element" should not exists
    And I add "empty.txt" file from "Private files" to "Files" filemanager as:
      | Save as | empty_ref.txt |
      | Create an alias/shortcut to the file | 1 |
    And I should see "2" elements in "Files" filemanager
    And I should see "empty_ref.txt" in the ".fp-content .fp-file.fp-isreference" "css_element"
    And I press "Save and display"
    And I should see "empty.txt"
    And I should see "empty_ref.txt"
    And I press "Edit"
    And I should see "2" elements in "Files" filemanager
    And I should see "empty_ref.txt" in the ".fp-content .fp-file.fp-isreference" "css_element"
    # ------ Overwriting the reference with a non-reference ---------
    And I add and overwrite "empty.txt" file from "Private files" to "Files" filemanager as:
      | Save as | empty_ref.txt |
    And I should see "2" elements in "Files" filemanager
    And ".fp-content .fp-file.fp-isreference" "css_element" should not exists
    And I press "Save changes"
    And I should see "empty.txt"
    And I should see "empty_ref.txt"
    And I press "Edit"
    And I should see "2" elements in "Files" filemanager
    And ".fp-content .fp-file.fp-isreference" "css_element" should not exists
    # ------ Overwriting non-reference with a reference ---------
    And I add and overwrite "empty.txt" file from "Private files" to "Files" filemanager as:
      | Save as | empty_ref.txt |
      | Create an alias/shortcut to the file | 1 |
    And I should see "2" elements in "Files" filemanager
    And I should see "empty_ref.txt" in the ".fp-content .fp-file.fp-isreference" "css_element"
    And I press "Save changes"
    And I should see "empty.txt"
    And I should see "empty_ref.txt"
    And I press "Edit"
    And I should see "2" elements in "Files" filemanager
    And I should see "empty_ref.txt" in the ".fp-content .fp-file.fp-isreference" "css_element"
