@local @local_iomadcertregen
Feature: Review an independent corrected certificate copy
  In order to correct an existing certificate without replacing its native evidence
  As a site administrator
  I need protected preparation and explicit confirmation

  Background:
    Given the following "users" exist:
      | username | firstname | middlename | lastname | email               |
      | learner1 | Example   | Fictional  | Learner  | learner@example.com |
    And the following config values are set as admin:
      | enabled_stores | logstore_standard | tool_log |
    And the following "courses" exist:
      | fullname                | shortname | category |
      | Certificate test course | ICR       | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | ICR    | student |
    And the following "activities" exist:
      | activity         | name                 | course | idnumber | iomadcertificatetype | printnumber | savecert |
      | iomadcertificate | Existing certificate | ICR    | ICRCERT  | A4_non_embedded      | 1           | 1        |
    And an IOMAD certificate has been issued to "learner1" in "Existing certificate"

  Scenario: Administrator prepares and confirms corrected bytes
    When I log in as "admin"
    And I open the certificate regenerator for "Existing certificate"
    Then I should see "Example Learner"
    And I should see "Saved original confirmed"
    When I follow "Prepare corrected copy"
    And I set the field "acknowledge" to "1"
    And I press "Prepare corrected copy"
    Then I should see "Review the prepared PDF before confirming"
    And "Open PDF for review" "link" should exist
    And "Download confirmed PDF" "link" should not exist
    And the prepared certificate bytes are recorded for review
    When I set the field "acknowledge" to "1"
    And I press "Confirm corrected copy"
    Then I should see "Corrected copy confirmed"
    And "Download confirmed PDF" "link" should exist
    And the corrected certificate retains its native evidence
    And I download the confirmed certificate with its audit trail

  Scenario: Acknowledgement is required without JavaScript
    When I log in as "admin"
    And I open the certificate regenerator for "Existing certificate"
    And I follow "Prepare corrected copy"
    And I press "Prepare corrected copy"
    Then I should see "Required"
    And "Confirm corrected copy" "button" should not exist

  Scenario: An enrolled beneficiary cannot open the administrative tool
    When I log in as "learner1"
    Then the certificate regenerator refuses my access to "Existing certificate"

  Scenario: Pagination and filtering keep certificate issues in the correct page
    Given 1001 issued IOMAD certificates exist in "Existing certificate"
    When I log in as "admin"
    And I open the certificate regenerator for "Existing certificate"
    Then I should see "Example Volume001"
    And I should not see "Example Volume030"
    When I follow "2"
    Then I should see "Example Volume030"
    And I should not see "Example Volume001"
    When I set the field "search" to "Volume005"
    And I press "Search"
    Then I should see "Example Volume005"
    And I should not see "Example Volume030"

  Scenario: Configured full names use the native Moodle name fields
    Given the following config values are set as admin:
      | fullnamedisplay | firstname middlename lastname |
    When I log in as "admin"
    And I open the certificate regenerator for "Existing certificate"
    Then I should see "Example Fictional Learner"

  @icr_publication
  Scenario: Reviewed publication retains the original and serves the native learner button
    Given the certificate "Existing certificate" uses email delivery
    And a native history PDF exists for "learner1" in "Existing certificate"
    When I log in as "admin"
    And I open the certificate regenerator for "Existing certificate"
    And I follow "Prepare corrected copy"
    And I set the field "acknowledge" to "1"
    And I press "Prepare corrected copy"
    And I set the field "acknowledge" to "1"
    And I press "Confirm corrected copy"
    And I follow "Publish corrected certificate for the learner"
    And the publication review for "Existing certificate" is recorded
    And I set the field "acknowledge" to "1"
    And I press "Publish corrected certificate for the learner"
    Then I should see "This correction is published for the learner"
    And the published certificate retains its original PDF and issue identity
    When I log out
    And I log in as "learner1"
    Then the native certificate button for "Existing certificate" serves the published PDF
    And I download my native course-history PDF

  @icr_publication
  Scenario: Administrator prepares confirms and publishes two selected learners
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | learner2 | Another   | Example  | second@example.com  |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner2 | ICR    | student |
    And an IOMAD certificate has been issued to "learner2" in "Existing certificate"
    When I log in as "admin"
    And I open the certificate regenerator for "Existing certificate"
    And I follow "Correct several learners"
    And I select the existing certificates of "learner1" and "learner2"
    And I set the field "acknowledge" to "1"
    And I press "Prepare selected corrections"
    Then both selected certificate copies are "prepared"
    When I set the field "acknowledge" to "1"
    And I press "Confirm reviewed copies"
    Then both selected certificate copies are "confirmed"
    When I follow "Publish reviewed copies for learners"
    And I set the field "acknowledge" to "1"
    And I press "Publish reviewed copies for learners"
    Then both selected certificate copies are "published"

  @icr_publication @icr_history_security
  Scenario: Another logged-in learner cannot download a personal native course-history PDF
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | learner2 | Another   | Example  | second@example.com  |
    And a native history PDF exists for "learner1" in "Existing certificate"
    When I log in as "learner2"
    Then the other learner's native history PDF is refused
