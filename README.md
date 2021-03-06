TYPO3 Repository Client API/CLI
===============================

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
