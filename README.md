wp-cli/checksum-command
=======================

Verify WordPress core checksums.

[![Build Status](https://travis-ci.org/wp-cli/checksum-command.svg?branch=master)](https://travis-ci.org/wp-cli/checksum-command)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing)

## Using

~~~
wp checksum core [--version=<version>] [--locale=<locale>]
~~~

Downloads md5 checksums for the current version from WordPress.org, and
compares those checksums against the currently installed files.

For security, avoids loading WordPress when verifying checksums.

**OPTIONS**

	[--version=<version>]
		Verify checksums against a specific version of WordPress.

	[--locale=<locale>]
		Verify checksums against a specific locale of WordPress.

**EXAMPLES**

    # Verify checksums
    $ wp core verify-checksums
    Success: WordPress install verifies against checksums.

    # Verify checksums for given WordPress version
    $ wp core verify-checksums --version=4.0
    Success: WordPress install verifies against checksums.

    # Verify checksums for given locale
    $ wp core verify-checksums --locale=en_US
    Success: WordPress install verifies against checksums.

    # Verify checksums for given locale
    $ wp core verify-checksums --locale=ja
    Warning: File doesn't verify against checksum: wp-includes/version.php
    Warning: File doesn't verify against checksum: readme.html
    Warning: File doesn't verify against checksum: wp-config-sample.php
    Error: WordPress install doesn't verify against checksums.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/checksum-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/checksum-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/checksum-command/issues/new) with the following:

1. What you were doing (e.g. "When I run `wp post list`").
2. What you saw (e.g. "I see a fatal about a class being undefined.").
3. What you expected to see (e.g. "I expected to see the list of posts.")

Include as much detail as you can, and clear steps to reproduce if possible.

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/checksum-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, please follow our guidelines for creating a pull request to make sure it's a pleasant experience:

1. Create a feature branch for each contribution.
2. Submit your pull request early for feedback.
3. Include functional tests with your changes. [Read the WP-CLI documentation](https://wp-cli.org/docs/pull-requests/#functional-tests) for an introduction.
4. Follow the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/).


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
