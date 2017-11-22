Feature: Validate checksums for WordPress plugins

  Scenario: Verify plugin checksums
    Given a WP install

    When I run `wp plugin install https://downloads.wordpress.org/plugins/test-plugin-3.test-tag.zip`
    Then STDOUT should not be empty
    And STDERR should be empty

    When I run `wp checksum plugin test-plugin-3`
    Then STDOUT should be:
      """
      Success: Plugin verifies against checksums.
      """

  Scenario: Modified plugin doesn't verify
    Given a WP install

    When I run `wp plugin install https://downloads.wordpress.org/plugins/test-plugin-3.test-tag.zip`
    Then STDOUT should not be empty
    And STDERR should be empty

    Given "WordPress" replaced with "Wordpress" in the wp-content/plugins/test-plugin-3/README.md file

    When I run `wp checksum plugin test-plugin-3`
    Then STDERR should be:
      """
      Warning: File doesn't verify against checksum: README.md
      Error: Plugin doesn't verify against checksums.
      """

    When I run `rm wp-content/plugins/test-plugin-3/README.md`
    Then STDERR should be empty

    When I try `wp checksum plugin test-plugin-3`
    Then STDERR should be:
      """
      Warning: File doesn't exist: README.md
      Error: Plugin doesn't verify against checksums.
      """
