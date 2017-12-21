Feature: Validate checksums for WordPress plugins

  Scenario: Verify plugin checksums
    Given a WP install

    When I run `wp plugin install duplicate-post --version=3.2.1`
    Then STDOUT should not be empty
    And STDERR should be empty

    When I run `wp checksum plugin duplicate-post`
    Then STDOUT should be:
      """
      Success: Plugin verifies against checksums.
      """

  Scenario: Modified plugin doesn't verify
    Given a WP install

    When I run `wp plugin install duplicate-post --version=3.2.1`
    Then STDOUT should not be empty
    And STDERR should be empty

    Given "Duplicate Post" replaced with "Different Name" in the wp-content/plugins/duplicate-post/duplicate-post.php file

    When I try `wp checksum plugin duplicate-post --format=json`
    Then STDOUT should contain:
      """
      "plugin_name":"duplicate-post","file":"duplicate-post.php","message":"Checksum does not match"
      """
    And STDERR should be:
      """
      Error: Plugin doesn't verify against checksums.
      """

    When I run `rm wp-content/plugins/duplicate-post/duplicate-post.css`
    Then STDERR should be empty

    When I try `wp checksum plugin duplicate-post --format=json`
    Then STDOUT should contain:
      """
      "plugin_name":"duplicate-post","file":"duplicate-post.css","message":"File is missing"
      """
    And STDERR should be:
      """
      Error: Plugin doesn't verify against checksums.
      """

    When I run `touch wp-content/plugins/duplicate-post/additional-file.php`
    Then STDERR should be empty

    When I try `wp checksum plugin duplicate-post --format=json`
    Then STDOUT should contain:
      """
      "plugin_name":"duplicate-post","file":"additional-file.php","message":"File was added"
      """
    And STDERR should be:
      """
      Error: Plugin doesn't verify against checksums.
      """

  Scenario: Soft changes are only reported in strict mode
    Given a WP install

    When I run `wp plugin install duplicate-post --version=3.2.1`
    Then STDOUT should not be empty
    And STDERR should be empty

    Given "Duplicate Post" replaced with "Different Name" in the wp-content/plugins/duplicate-post/README.txt file

    When I run `wp checksum plugin duplicate-post`
    Then STDOUT should be:
      """
      Success: Plugin verifies against checksums.
      """
    And STDERR should be empty

    When I try `wp checksum plugin duplicate-post --strict`
    Then STDOUT should not be empty
    And STDERR should contain:
      """
      Error: Plugin doesn't verify against checksums.
      """

  Scenario: Multiple checksums for a single file are supported
    Given a WP install

    When I run `wp plugin install wptouch --version=4.3.22`
    Then STDOUT should not be empty
    And STDERR should be empty

    When I run `wp checksum plugin wptouch`
    Then STDOUT should be:
      """
      Success: Plugin verifies against checksums.
      """
    And STDERR should be empty
