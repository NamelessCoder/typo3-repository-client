Changelog - TYPO3 Extension Repository Client
=============================================

2.0.0 - 2017-02-12
------------------

- Dropped support for discontinued PHP 5.x branches
- Implemented Generators to resolve files more efficiently

1.3.1 - 2017-02-12
------------------

- Avoided sub-generators to preserve PHP 5.x compatibility.
- This is the last PHP 5.x compatible release; future ones will be PHP 7.0+.

1.3.0 - 2017-02-12
------------------

- Added processing of `.gitignore` rules as exclude list before uploading. Allows the binary to be executed from local
  repositories without first cleaning up the folder to remove any files not intended for TER upload.

1.2.0 - 2016-07-28
------------------

- Suggested extensions now included in TER request (Thanks to @helhum)
- Third-party binaries added to ignored files (Thanks @helhum)
- `setversion` command improved; graceful handling of `composer.json` version (Thanks @helhum)
- Composer branch alias `$version-dev` usage introduced  (Thanks @helhum)

1.1.1 - 2015-08-08
------------------

- Fixed incorrect order of arguments in `upload` help text.

1.1.0 - 2015-03-15
------------------

- Feature to validate the version number set in `composer.json`.

1.0.5 - 2015-03-03
------------------

- Guard against incorrectly terminated path arguments.

1.0.4 - 2015-03-02
------------------

- Fix for file paths submitted to TER; switched to relative paths.

1.0.3 - 2015-02-28
------------------

- Fix for PSR compliance in class file locations

1.0.2 - 2014-12-08
------------------

- Fix for autoloader path inclusions when installed as dependency

1.0.1 - 2014-12-08
------------------

- Binaries configured in composer for correct install

1.0.0 - 2014-12-08
------------------

- First release containing uploader
