@local @local_iomadcertregen
Feature: Export certificate inventory through native report downloads
  As a site administrator
  I need actual CSV data and protected report audiences

  Background:
    Given the following "users" exist:
      | username        | firstname | lastname | email              |
      | inventoryviewer | Fictional | Viewer   | viewer@example.com |
    And a fictional certificate inventory is available for CSV review

  Scenario: Administrator exports the system inventory
    When I log in as "admin"
    Then the "system" certificate inventory downloads both fictional templates as CSV

  Scenario: Administrator exports the custom report inventory
    When I log in as "admin"
    Then the "custom" certificate inventory downloads both fictional templates as CSV

  Scenario: Ordinary user cannot export the system inventory
    When I log in as "inventoryviewer"
    Then the "system" certificate inventory CSV download is refused

  Scenario: An all-users audience cannot expose custom inventory data
    When I log in as "inventoryviewer"
    Then the "custom" certificate inventory CSV download is refused
