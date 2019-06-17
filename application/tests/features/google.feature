Feature: Google integrations

  Scenario: Getting user password metadata
    Given I can make authenticated calls to Google
    When I try to get a specific user's metadata
    Then an exception should not have been thrown
     And I should get back metadata about that user's password

  Scenario: Setting a user's password
    Given I can make authenticated calls to Google
    When I try to set a specific user's password
    Then an exception should not have been thrown
     And I should get back metadata about that user's password
