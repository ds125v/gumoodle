@core @core_message
Feature: Message history displays correctly
  In order to read messages between two users
  As a user
  I need to view the conversation with another user

  Background:
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | user1 | User | One | one@asd.com |
      | user2 | User | Two | two@asd.com |
    And I log in as "user1"
    And I send "Message 1 from user1 to user2" message to "user2"
    And I send "Message 2 from user1 to user2" message to "user2"
    And I send "Message 3 from user1 to user2" message to "user2"
    And I send "Message 4 from user1 to user2" message to "user2"
    And I send "Message 5 from user1 to user2" message to "user2"
    And I send "Message 6 from user1 to user2" message to "user2"
    And I send "Message 7 from user1 to user2" message to "user2"
    And I send "Message 8 from user1 to user2" message to "user2"
    And I send "Message 9 from user1 to user2" message to "user2"
    And I send "Message 10 from user1 to user2" message to "user2"

  Scenario: View sent messages
    When I expand "My profile" node
    And I click on "Messages" "link" in the "Navigation" "block"
    And I fill in "Search people and messages" with "User Two"
    And I press "Search people and messages"
    And I click on "Message history" "link" in the "User Two" "table_row"
    # The message history link shows all messages.
    Then I should see "Message 1 from user1 to user2"
    And I should see "Message 10 from user1 to user2"
    # Only the last eight messages.
    And I follow "Recent messages"
    And I should see "Message 10 from user1 to user2"
    And I should not see "Message 1 from user1 to user2"

  Scenario: View received messages
    When I log out
    And I log in as "user2"
    And I expand "My profile" node
    And I click on "Messages" "link" in the "Navigation" "block"
    And I follow "User One (10)"
    # Should show all of the user's unread messages.
    Then I should see "Message 1 from user1 to user2"
    And I should see "Message 10 from user1 to user2"
