Feature: Multiple backends

  Scenario: Calling getMeta()
    Given 3 password stores are configured
    When I get metadata about a user
    Then I should receive the response from the first password store

  Scenario: Successfully setting a password on all password stores
    Given 3 password stores are configured
    When I set a user's password
    Then an exception should NOT have been thrown
    And I should receive the response from the first password store

  Scenario: Only managing to set a password on some password stores
    Given 3 password stores are configured
    But password store 2 will fail when I try to set a user's password
    When I set a user's password
    Then an exception SHOULD have been thrown
    And the exception should indicate which password store failed

  Scenario: Not configuring any password stores
    Given 0 password stores are configured
    When I get metadata about a user
    Then an exception SHOULD have been thrown

  Scenario: Not trying because a password store is down
    Given 3 password stores are configured
    But password store 2 will fail our status precheck
    When I set a user's password
    Then an exception SHOULD have been thrown
    And the exception should indicate that it did not try to set the password anywhere
