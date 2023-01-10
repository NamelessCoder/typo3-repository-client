TYPO3 Repository Client API/CLI
===============================

# OBSOLETE!

This library uses the long-deprecated and now removed SOAP API for the TYPO3 Extension Repository. **It is no longer functioning!**

### The library is archived and no longer receives updates. It is kept available here only for posterity.

## Alternative

I recommend using the official "Tailor" library from TYPO3: https://github.com/TYPO3/tailor

The Tailor library allows you to interact with the TYPO3 Extension Repository using the current REST API. It is possible
to use it from a local machine or through CI. An example GitHub action (for example as `.github/workflows/release.yml`):

```yaml
on:
  push:
    tags:
      - "**"

jobs:
  release:
    runs-on: ubuntu-20.04
    steps:
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: mbstring, json
          ini-values: date.timezone="Europe/Copenhagen", opcache.fast_shutdown=0
          tools: composer:v2.2
      - name: "create working directory"
        run: "mkdir tailor"
      - name: "Install Tailor"
        working-directory: tailor
        run: "composer require typo3/tailor"
      - name: "Upload to TER"
        working-directory: tailor
        run: "./vendor/bin/tailor ter:publish $TAG $EXTENSION_KEY --artefact $ARTEFECT_URL --comment \"$MESSAGE\""
        env:
          TYPO3_API_USERNAME: ${{ secrets.TER_USERNAME }}
          TYPO3_API_TOKEN: ${{ secrets.TER_TOKEN }}
          TAG: ${{ github.ref_name }}
          EXTENSION_KEY: testing
          ARTEFECT_URL: "https://github.com/${{ github.repository }}/archive/${{ github.ref }}.zip"
          MESSAGE: "Automatic release built from GitHub. See the CHANGELOG.md file that is shipped with this release for details."
```

(Requires two "secrets" entered in the GitHub repository; `TER_TOKEN` and `TER_USERNAME`. See Tailor's documentation for
further information on how to obtain a token. Note that the token has a limited lifetime and will need to be refreshed.)

This action has the benefit that it *does not operate on artefact files locally in the CI pipeline*. Instead it refers
TER to read the files from the artefect produced by GitHub whenever a new tag is uploaded. If you need/want to operate
on files before a release is made, e.g. to remove certain files, Thomas Norre has provided a drop-in GitHub action that
works in that particular way: https://github.com/tomasnorre/typo3-upload-ter

See Tailor's documentation for alternative CI integrations and manual usage instructions.

_So long, and thanks for all the fish!_ ;)

------------------------------------------------------------------------------------------------------------------------

[![Build Status](https://img.shields.io/travis/NamelessCoder/typo3-repository-client.svg?style=flat-square&label=package)](https://travis-ci.org/NamelessCoder/typo3-repository-client) [![Coverage Status](https://img.shields.io/coveralls/NamelessCoder/typo3-repository-client.svg?style=flat-square)](https://coveralls.io/r/NamelessCoder/typo3-repository-client)

TYPO3 Extension Repository (TER) client library and CLI commands

Usage
-----

Each command which can be executed has a corresponding class, for example `NamelessCoder\TYPO3RepositoryClient\Uploader` and a CLI script which acts as a wrapper for said class. The parameters which should be passed to each CLI script *must be the same arguments and in the same order as required by the class' method*.

### Uploader

As component:

```php
$uploader = new \NamelessCoder\TYPO3RepositoryClient\Uploader();
$uploader->upload('/path/to/extension', 'myusername', 'mypassword', 'An optional comment');
```

And as CLI command:

```bash
./bin/upload /path/to/extension myusername mypassword "An optional comment"
```

### Version Updater (local)

As component:

```php
$versioner = new \NamelessCoder\TYPO3RepositoryClient\Versioner();
$version = $versioner->read('/path/to/extension/');
$version[0] = '1.2.3';
$version[1] = 'beta';
$versioner->write('/path/to/extension/', '1.2.3', 'beta');

```

And as CLI command:

```bash
# with all parameters
./bin/setversion 1.2.3 beta /optional/path/to/extension/
# without changing current stability:
./bin/setversion 1.2.3
```

### Version Deleter (admins only)

As component:

```php
$deleter = new \NamelessCoder\TYPO3RepositoryClient\VersionDeleter();
$deleter->deleteExtensionVersion('extensionkey', '1.2.3', 'myusername', 'mypassword');
```

And as CLI command:

```bash
./bin/rmversion extensionkey 1.2.3 myusername mypassword
```


FAQ
---

### Excluded files and folders

* Dotfiles (like `.editorconfig` or `.php_cs.dist`) will be ignored except for `.htpasswd` and `.htaccess` files.
* Use a `.gitignore` file to exclude more files and folders from being included in the final TER release.
