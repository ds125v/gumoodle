@core @core_message
Feature: Manage contacts
  In order to easily access the users I interact more with
  As a user
  I need to add and remove users to/from my contacts list

  Background:
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | user1 | User | One | one@asd.com |
      | user2 | User | Two | two@asd.com |
    And I log in as "user1"
    And I send "Message 1 from user1 to user2" message to "user2"
    And I send "Message 2 from user1 to user2" message to "user2"
    And I expand "My profile" node
    And I click on "Messages" "link" in the "Navigation" "block"
    And I fill in "Search people and messages" with "User Two"
    And I press "Search people and messages"
    When I click on "Add contact" "link" in the "User Two" "table_row"
    Then I should see "Message 1 from user1 to user2"
    And I should see "Message 2 from user1 to user2"
    And I should see "User Two" in the "#message_contacts" "css_element"
    And I follow "Remove contact"
    And I should not see "User Two" in the "#message_contacts" "css_element"
    And I should not see "Remove contact"
    And I should see "Add contact"
    And I follow "Add contact"
    And I should see "User Two" in the "#message_contacts" "css_element"

  @javascript
  Scenario: Adding and removing contacts with Javascript enabled

  Scenario: Adding and removing contacts with Javascript disabled
