@core @core_badges @_only_local @_file_upload
Feature: Award badges
  In order to award badges to users for their achievements
  As an admin
  I need to add criteria to badges in the system

  @javascript
  Scenario: Award profile badge
    Given I log in as "admin"
    And I expand "Site administration" node
    And I expand "Badges" node
    And I follow "Add a new badge"
    And I fill the moodle form with:
      | Name | Profile Badge |
      | Description | Test badge description |
      | issuername | Test Badge Site |
      | issuercontact | testuser@test-badge-site.com |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    And I select "Profile completion" from "type"
    And I check "First name"
    And I check "Email address"
    And I check "Phone"
    When I press "Save"
    Then I should see "Profile completion"
    And I should see "First name"
    And I should see "Email address"
    And I should not see "Criteria for this badge have not been set up yet."
    And I press "Enable access"
    And I press "Continue"
    And I expand "My profile settings" node
    And I follow "Edit profile"
    And I expand all fieldsets
    And I fill in "Phone" with "123456789"
    And I press "Update profile"
    And I follow "My badges"
    Then I should see "Profile Badge"
    And I should not see "There are no badges available."

  @javascript
  Scenario: Award site badge
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher | teacher | 1 | teacher1@asd.com |
      | student | student | 1 | student1@asd.com |
    And I log in as "admin"
    And I expand "Site administration" node
    And I expand "Badges" node
    And I follow "Add a new badge"
    And I fill the moodle form with:
      | Name | Site Badge |
      | Description | Site badge description |
      | issuername | Tester of site badge |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    And I select "Manual issue by role" from "type"
    And I check "Teacher"
    And I press "Save"
    And I press "Enable access"
    And I press "Continue"
    And I follow "Recipients (0)"
    And I press "Award badge"
    And I select "teacher 1 (teacher1@asd.com)" from "potentialrecipients[]"
    And I press "Award badge"
    And I select "student 1 (student1@asd.com)" from "potentialrecipients[]"
    And I press "Award badge"
    When I follow "Site Badge"
    Then I should see "Recipients (2)"
    And I log out
    And I log in as "student"
    And I expand "My profile" node
    And I follow "My badges"
    Then I should see "Site Badge"

  @javascript
  Scenario: Award course badge
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
    And the following "courses" exists:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I click on "//span[text()='Badges']" "xpath_element" in the "Administration" "block"
    And I follow "Add a new badge"
    And I fill the moodle form with:
      | Name | Course Badge |
      | Description | Course badge description |
      | issuername | Tester of course badge |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    And I select "Manual issue by role" from "type"
    And I check "Teacher"
    And I press "Save"
    And I press "Enable access"
    And I press "Continue"
    And I follow "Recipients (0)"
    And I press "Award badge"
    And I select "Student 2 (student2@asd.com)" from "potentialrecipients[]"
    And I press "Award badge"
    And I select "Student 1 (student1@asd.com)" from "potentialrecipients[]"
    When I press "Award badge"
    And I follow "Course Badge"
    Then I should see "Recipients (2)"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I expand "My profile" node
    And I follow "My badges"
    Then I should see "Course Badge"

  @javascript
  Scenario: Award badge on activity completion
    Given the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Frist | teacher1@asd.com |
      | student1 | Student | First | student1@asd.com |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
    And I follow "Home"
    And I follow "Course 1"
    And I follow "Edit settings"
    And I fill the moodle form with:
      | Enable completion tracking | Yes |
    And I press "Save changes"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I click on "//span[text()='Badges']" "xpath_element" in the "Administration" "block"
    And I follow "Add a new badge"
    And I fill the moodle form with:
      | Name | Course Badge |
      | Description | Course badge description |
      | issuername | Tester of course badge |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    And I select "Activity completion" from "type"
    And I check "Test assignment name"
    And I press "Save"
    And I press "Enable access"
    When I press "Continue"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I expand "My profile" node
    And I follow "My badges"
    Then I should see "There are no badges available."
    And I follow "Home"
    And I follow "Course 1"
    And I press "Mark as complete: Test assignment name"
    And I expand "My profile" node
    And I follow "My badges"
    Then I should see "Course Badge"

  @javascript
  Scenario: Award badge on course completion
    Given the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Frist | teacher1@asd.com |
      | student1 | Student | First | student1@asd.com |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
    And I follow "Home"
    And I follow "Course 1"
    And I follow "Edit settings"
    And I fill the moodle form with:
      | Enable completion tracking | Yes |
    And I press "Save changes"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
    And I follow "Course completion"
    And I select "2" from "id_overall_aggregation"
    And I click on "Condition: Activity completion" "link"
    And I check "Assign - Test assignment name"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I click on "//span[text()='Badges']" "xpath_element" in the "Administration" "block"
    And I follow "Add a new badge"
    And I fill the moodle form with:
      | Name | Course Badge |
      | Description | Course badge description |
      | issuername | Tester of course badge |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    And I select "Course completion" from "type"
    And I fill the moodle form with:
      | grade_2 | 0 |
    And I press "Save"
    And I press "Enable access"
    When I press "Continue"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I expand "My profile" node
    And I follow "My badges"
    Then I should see "There are no badges available."
    And I follow "Home"
    And I follow "Course 1"
    And I press "Mark as complete: Test assignment name"
    And I log out
    And I log in as "admin"
    # We can't wait for cron to happen, so the admin manually triggers it.
    And I trigger cron
    # The admin needs to trigger cron twice to see the completion status as completed.
    And I trigger cron
    # Finally the admin goes back to homepage to continue the user story.
    And I am on homepage
    And I log out
    And I log in as "student1"
    And I expand "My profile" node
    And I follow "My badges"
    Then I should see "Course Badge"
