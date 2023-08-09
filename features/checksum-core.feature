Feature: Validate checksums for WordPress install

  @require-php-7.0
  Scenario: Verify core checksums
    Given a WP install

    When I run `wp core update`
    Then STDOUT should not be empty

    When I run `wp core verify-checksums`
    Then STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """

  Scenario: Core checksums don't verify
    Given a WP install
    And "WordPress" replaced with "Wordpress" in the readme.html file

    When I try `wp core verify-checksums`
    Then STDERR should be:
      """
      Warning: File doesn't verify against checksum: readme.html
      Error: WordPress installation doesn't verify against checksums.
      """

    When I run `rm readme.html`
    Then STDERR should be empty

    When I try `wp core verify-checksums`
    Then STDERR should be:
      """
      Warning: File doesn't exist: readme.html
      Error: WordPress installation doesn't verify against checksums.
      """
    And the return code should be 1

  Scenario: Core checksums don't verify because wp-cli.yml is present
    Given a WP install
    And a wp-cli.yml file:
      """
      plugin install:
        - user-switching
      """

    When I try `wp core verify-checksums`
    Then STDERR should be:
      """
      Warning: File should not exist: wp-cli.yml
      """
    And STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """
    And the return code should be 0

    When I run `rm wp-cli.yml`
    Then STDERR should be empty

    When I run `wp core verify-checksums`
    Then STDERR should be empty
    And STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """
    And the return code should be 0

  Scenario: Verify core checksums without loading WordPress
    Given an empty directory
    And I run `wp core download --version=4.3`

    When I run `wp core verify-checksums`
    Then STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """

    When I run `wp core verify-checksums --version=4.3 --locale=en_US`
    Then STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """

    When I try `wp core verify-checksums --version=4.2 --locale=en_US`
    Then STDERR should contain:
      """
      Error: WordPress installation doesn't verify against checksums.
      """

  Scenario: Verify core checksums for a non US local
    Given an empty directory
    And I run `wp core download --locale=en_GB --version=4.3.1 --force`
    Then STDOUT should contain:
      """
      Success: WordPress downloaded.
      """
    And the return code should be 0

    When I run `wp core verify-checksums`
    Then STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """
    And the return code should be 0

  @require-php-7.0
  Scenario: Verify core checksums with extra files
    Given a WP install

    When I run `wp core update`
    Then STDOUT should not be empty

    Given a wp-includes/extra-file.txt file:
      """
      hello world
      """
    Then the wp-includes/extra-file.txt file should exist

    When I try `wp core verify-checksums`
    Then STDERR should be:
      """
      Warning: File should not exist: wp-includes/extra-file.txt
      """
    And STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """
    And the return code should be 0

  Scenario: Verify core checksums when extra files prefixed with 'wp-' are included in WordPress root
    Given a WP install
    And a wp-extra-file.php file:
      """
      hello world
      """

    When I try `wp core verify-checksums`
    Then STDERR should be:
      """
      Warning: File should not exist: wp-extra-file.php
      """
    And STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """
    And the return code should be 0

  Scenario: Verify core checksums when extra files are included in WordPress root and --include-root is passed
    Given a WP install
    And a .htaccess file:
      """
      # BEGIN WordPress
      """
    And a .maintenance file:
      """
      <?php
      $upgrading = time();
      """
    And a extra-file.php file:
      """
      hello world
      """
    And a unknown-folder/unknown-file.php file:
      """
      taco burrito
      """
    And a wp-content/unknown-file.php file:
      """
      foobar
      """

    When I try `wp core verify-checksums --include-root`
    Then STDERR should contain:
      """
      Warning: File should not exist: unknown-folder/unknown-file.php
      """
    And STDERR should contain:
      """
      Warning: File should not exist: extra-file.php
      """
    And STDERR should not contain:
      """
      Warning: File should not exist: .htaccess
      """
    And STDERR should not contain:
      """
      Warning: File should not exist: .maintenance
      """
    And STDERR should not contain:
      """
      Warning: File should not exist: wp-content/unknown-file.php
      """
    And STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """
    And the return code should be 0

    When I run `wp core verify-checksums`
    Then STDERR should not contain:
      """
      Warning: File should not exist: unknown-folder/unknown-file.php
      """
    And STDERR should not contain:
      """
      Warning: File should not exist: extra-file.php
      """
    And STDERR should not contain:
      """
      Warning: File should not exist: wp-content/unknown-file.php
      """
    And STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """
    And the return code should be 0

  Scenario: Verify core checksums with a plugin that has wp-admin
    Given a WP install
    And a wp-content/plugins/akismet/wp-admin/extra-file.txt file:
      """
      hello world
      """

    When I run `wp core verify-checksums`
    Then STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """
    And STDERR should be empty
