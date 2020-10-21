<?php
namespace NamelessCoder\TYPO3RepositoryClient;

/**
 * Class ExtensionUploadPacker
 */
class ExtensionUploadPacker
{
    const KIND_DEPENDENCY = 'depends';
    const KIND_CONFLICT = 'conflicts';
    const KIND_SUGGEST = 'suggests';
    protected $permittedDotFiles = ['.htaccess', '.htpasswd'];

    /**
     * @param string $directory
     * @param string $username
     * @param string $password
     * @param string $comment
     * @return string
     */
    public function pack($directory, $username, $password, $comment)
    {
        $extensionKey = pathinfo($directory, PATHINFO_FILENAME);
        $extensionConfiguration = $this->readExtensionConfigurationFile($directory, $extensionKey);

        $data = $this->createFileDataArray($directory);
        $data['EM_CONF'] = $extensionConfiguration;

        return $this->createSoapData($extensionKey, $data, $username, $password, $comment);
    }

    /**
     * @param string $directory
     * @param string $_EXTKEY
     * @return array
     * @throws \RuntimeException
     */
    protected function readExtensionConfigurationFile($directory, $_EXTKEY)
    {
        $expectedFilename = $directory . '/ext_emconf.php';
        if (false === file_exists($expectedFilename)) {
            throw new \RuntimeException('Directory "' . $directory . '" does not contain an ext_emconf.php file');
        }
        $EM_CONF = [];
        include $expectedFilename;
        $this->validateVersionNumber($EM_CONF[$_EXTKEY]['version']);
        return $EM_CONF[$_EXTKEY];
    }

    /**
     * @param string $version
     * @throws \RuntimeException
     */
    protected function validateVersionNumber($version)
    {
        if (1 !== preg_match('/^[\\d]{1,2}\.[\\d]{1,2}\.[\\d]{1,2}$/i', $version)) {
            throw new \RuntimeException(
                'Invalid version number "' . $version
                . '" detected in ext_emconf.php, refusing to pack extension for upload',
                1426383996
            );
        }
    }

    /**
     * @param string $extensionData
     * @param string $key
     * @return array
     * @throws \RuntimeException
     */
    protected function createDependenciesArray($extensionData, $key)
    {
        $dependenciesArr = [];
        if (false === isset($extensionData['EM_CONF']['constraints'][$key])) {
            return $dependenciesArr;
        }
        if (false === is_array($extensionData['EM_CONF']['constraints'][$key])) {
            return $dependenciesArr;
        }
        foreach ($extensionData['EM_CONF']['constraints'][$key] as $extKey => $version) {
            if (false === is_string($extKey)) {
                throw new \RuntimeException(
                    'Invalid dependency definition! Dependencies must be an array indexed by extension key'
                );
            }
            $dependenciesArr[] = [
                'kind' => $key,
                'extensionKey' => $extKey,
                'versionRange' => $version,
            ];
        }
        return $dependenciesArr;
    }

    /**
     * @param array $extensionData
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function valueOrDefault($extensionData, $key, $default = null)
    {
        return true === isset($extensionData['EM_CONF'][$key]) ? $extensionData['EM_CONF'][$key] : $default;
    }

    /**
     * @param string $extensionKey
     * @param array $extensionData
     * @param string $username
     * @param string $password
     * @param string $comment
     * @return array
     */
    public function createSoapData($extensionKey, $extensionData, $username, $password, $comment)
    {
        // Create dependency / conflict information:
        $dependenciesArr = $this->createDependenciesArray($extensionData, ExtensionUploadPacker::KIND_DEPENDENCY);
        $dependenciesArr = array_merge(
            $dependenciesArr,
            $this->createDependenciesArray($extensionData, ExtensionUploadPacker::KIND_CONFLICT)
        );
        $dependenciesArr = array_merge(
            $dependenciesArr,
            $this->createDependenciesArray($extensionData, ExtensionUploadPacker::KIND_SUGGEST)
        );

        // Compile data for SOAP call:
        $extension = [
            'extensionKey' => $extensionKey,
            'version' => $this->valueOrDefault($extensionData, 'version'),
            'metaData' => [
                'title' => $this->valueOrDefault($extensionData, 'title'),
                'description' => $this->valueOrDefault($extensionData, 'description'),
                'category' => $this->valueOrDefault($extensionData, 'category'),
                'state' => $this->valueOrDefault($extensionData, 'state'),
                'authorName' => $this->valueOrDefault($extensionData, 'author'),
                'authorEmail' => $this->valueOrDefault($extensionData, 'author_email'),
                'authorCompany' => $this->valueOrDefault($extensionData, 'author_company'),
            ],
            'technicalData' => [
                'dependencies' => $dependenciesArr,
                'loadOrder' => $this->valueOrDefault($extensionData, 'loadOrder'),
                'uploadFolder' => (boolean)$this->valueOrDefault($extensionData, 'uploadFolder'),
                'createDirs' => $this->valueOrDefault($extensionData, 'createDirs'),
                'shy' => $this->valueOrDefault($extensionData, 'shy', false),
                'modules' => $this->valueOrDefault($extensionData, 'module'),
                'modifyTables' => $this->valueOrDefault($extensionData, 'modify_tables'),
                'priority' => $this->valueOrDefault($extensionData, 'priority'),
                'clearCacheOnLoad' => (boolean)$this->valueOrDefault($extensionData, 'clearCacheOnLoad'),
                'lockType' => $this->valueOrDefault($extensionData, 'lockType'),
                'doNotLoadInFEe' => $this->valueOrDefault($extensionData, 'doNotLoadInFE'),
                'docPath' => $this->valueOrDefault($extensionData, 'docPath'),
            ],
            'infoData' => [
                'codeLines' => (int)$extensionData['misc']['codelines'],
                'codeBytes' => (int)$extensionData['misc']['codebytes'],
                'codingGuidelinesCompliance' => $this->valueOrDefault($extensionData, 'CGLcompliance'),
                'codingGuidelinesComplianceNotes' => $this->valueOrDefault($extensionData, 'CGLcompliance_note'),
                'uploadComment' => $comment,
                'techInfo' => $extensionData['techInfo'],
            ],
        ];

        $files = [];
        foreach ($extensionData['FILES'] as $filename => $infoArr) {
            $files[] = [
                'name' => $infoArr['name'],
                'size' => (int)$infoArr['size'],
                'modificationTime' => (int)$infoArr['mtime'],
                'isExecutable' => (int)$infoArr['is_executable'],
                'content' => $infoArr['content'],
                'contentMD5' => $infoArr['content_md5'],
            ];
        }
        $compiler = new SoapDataCompiler();
        return $compiler->createSoapData(
            $username,
            $password,
            [
                'extensionData' => $extension,
                'filesData' => $files
            ]
        );
    }

    /**
     * @param string $directory
     * @return array
     */
    protected function createFileDataArray($directory)
    {
        // Initialize output array:
        $uploadArray = [];
        $uploadArray['extKey'] = rtrim(pathinfo($directory, PATHINFO_FILENAME), '/');
        $uploadArray['misc']['codelines'] = 0;
        $uploadArray['misc']['codebytes'] = 0;
        $uploadArray['techInfo'] = 'All good, baby';
        $uploadArray['FILES'] = [];
        $directoryLength = strlen(rtrim($directory, '/')) + 1;

        $gitIgnoreFile = $directory . '/.gitignore';
        $skipFiles = [];
        if (file_exists($gitIgnoreFile)) {
            $lines = file($gitIgnoreFile);
            foreach ($lines as $line) {
                $line = trim($line);
                // Since we only support the root .gitignore as skip list, all paths *are* relative. Strip absolute.
                $line = ltrim($line, '/');
                if ($line === '') {
                    continue;
                }
                # empty line
                if (substr($line, 0, 1) == '#') {
                    continue;
                }
                # a comment
                if (substr($line, 0, 1) == '!') {
                    # negated glob
                    $line = substr($line, 1);
                    $files = array_diff(glob("$directory/*"), glob("$directory/$line"));
                } else {
                    # normal glob
                    $files = glob("$directory/$line");
                }
                $skipFiles = array_merge($skipFiles, $files);
            }
        }

        foreach ($this->recursivelyScanFolderForFiles($directory, $skipFiles) as $file) {
            /** @var \SplFileInfo $file */
            $filename = $file->getPathname();
            $relativeFilename = substr($filename, $directoryLength);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $uploadArray['FILES'][$relativeFilename] = [
                'name' => $relativeFilename,
                'size' => filesize($filename),
                'mtime' => filemtime($filename),
                'is_executable' => is_executable($filename),
                'content' => file_get_contents($filename)
            ];
            if (true === in_array($extension, ['php', 'inc'])) {
                $uploadArray['FILES'][$relativeFilename]['codelines'] =
                    count(explode(PHP_EOL, $uploadArray['FILES'][$relativeFilename]['content']));
                $uploadArray['misc']['codelines'] += $uploadArray['FILES'][$relativeFilename]['codelines'];
                $uploadArray['misc']['codebytes'] += $uploadArray['FILES'][$relativeFilename]['size'];
            }
            $uploadArray['FILES'][$relativeFilename]['content_md5'] =
                md5($uploadArray['FILES'][$relativeFilename]['content']);
        }

        return $uploadArray;
    }

    /**
     * @param string $directory
     * @param array $skipFiles
     * @return \Generator
     */
    protected function recursivelyScanFolderForFiles($directory, array $skipFiles)
    {
        $iterator = new \DirectoryIterator($directory);
        foreach ($iterator as $fileOrFolder) {
            if (!$this->isFilePermitted($fileOrFolder, $directory)) {
                continue;
            }
            if (in_array($fileOrFolder->getPathname(), $skipFiles)) {
                echo 'Skipped ' . $fileOrFolder->getPathname() . PHP_EOL;
                continue;
            }
            if ($fileOrFolder->isDir()) {
                yield from $this->recursivelyScanFolderForFiles($fileOrFolder->getPathname(), $skipFiles);
            } else {
                yield $fileOrFolder;
            }
        }
    }

    /**
     * @param \SplFileInfo $file
     * @param string $inPath
     * @return boolean
     */
    protected function isFilePermitted(\SplFileInfo $file, $inPath)
    {
        $name = $file->getFilename();
        if (true === $this->isDotFileAndNotPermitted($name)) {
            return false;
        }
        $consideredPathLength = strlen($inPath);
        foreach (explode('/', trim(substr($file->getPathname(), $consideredPathLength), '/')) as $segment) {
            if (true === $this->isDotFileAndNotPermitted($segment)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $filename
     * @return boolean
     */
    protected function isDotFileAndNotPermitted($filename)
    {
        return (false === empty($filename) && '.' === $filename[0]
            && false === in_array($filename, $this->permittedDotFiles));
    }
}
