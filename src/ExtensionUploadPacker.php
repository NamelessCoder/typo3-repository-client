<?php
namespace NamelessCoder\TYPO3RepositoryClient;

/**
 * Class ExtensionUploadPacker
 */
class ExtensionUploadPacker {

	const KIND_DEPENDENCY = 'depends';
	const KIND_CONFLICT = 'conflicts';
	const KIND_SUGGEST = 'suggests';
	protected $permittedDotFiles = array('.htaccess', '.htpasswd');

	/**
	 * @param string $directory
	 * @param string $username
	 * @param string $password
	 * @param string $comment
	 * @return string
	 */
	public function pack($directory, $username, $password, $comment) {
		$extensionKey = pathinfo($directory, PATHINFO_FILENAME);
		$extensionConfiguration = $this->readExtensionConfigurationFile($directory, $extensionKey);
		$data = $this->createFileDataArray($directory);
		$data['EM_CONF'] = $extensionConfiguration;
		$soap = $this->createSoapData($extensionKey, $data, $username, $password, $comment);
		return $soap;
	}

	/**
	 * @param string $directory
	 * @param string $_EXTKEY
	 * @return array
	 * @throws \RuntimeException
	 */
	protected function readExtensionConfigurationFile($directory, $_EXTKEY) {
		$expectedFilename = $directory . '/ext_emconf.php';
		if (FALSE === file_exists($expectedFilename)) {
			throw new \RuntimeException('Directory "' . $directory . "' does not contain an ext_emconf.php file");
		}
		$EM_CONF = array();
		include $expectedFilename;
		$this->validateVersionNumber($EM_CONF[$_EXTKEY]['version']);
		return $EM_CONF[$_EXTKEY];
	}

	/**
	 * @param string $version
	 * @throws \RuntimeException
	 */
	protected function validateVersionNumber($version) {
		if (1 !== preg_match('/^[\\d]{1,2}\.[\\d]{1,2}\.[\\d]{1,2}$/i', $version)) {
			throw new \RuntimeException(
				'Invalid version number "' . $version . '" detected in ext_emconf.php, refusing to pack extension for upload',
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
	protected function createDependenciesArray($extensionData, $key) {
		$dependenciesArr = array();
		if (FALSE === isset($extensionData['EM_CONF']['constraints'][$key])) {
			return $dependenciesArr;
		}
		if (FALSE === is_array($extensionData['EM_CONF']['constraints'][$key])) {
			return $dependenciesArr;
		}
		foreach ($extensionData['EM_CONF']['constraints'][$key] as $extKey => $version) {
			if (FALSE === is_string($extKey)) {
				throw new \RuntimeException('Invalid dependency definition! Dependencies must be an array indexed by extension key');
			}
			$dependenciesArr[] = array(
				'kind' => $key,
				'extensionKey' => $extKey,
				'versionRange' => $version,
			);
		}
		return $dependenciesArr;
	}

	/**
	 * @param array $extensionData
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	protected function valueOrDefault($extensionData, $key, $default = NULL) {
		return TRUE === isset($extensionData['EM_CONF'][$key]) ? $extensionData['EM_CONF'][$key] : $default;
	}

	/**
	 * @param string $extensionKey
	 * @param array $extensionData
	 * @param string $username
	 * @param string $password
	 * @param string $comment
	 * @return array
	 */
	public function createSoapData($extensionKey, $extensionData, $username, $password, $comment) {

		// Create dependency / conflict information:
		$dependenciesArr = $this->createDependenciesArray($extensionData, ExtensionUploadPacker::KIND_DEPENDENCY);
		$dependenciesArr = array_merge($dependenciesArr, $this->createDependenciesArray($extensionData, ExtensionUploadPacker::KIND_CONFLICT));
		$dependenciesArr = array_merge($dependenciesArr, $this->createDependenciesArray($extensionData, ExtensionUploadPacker::KIND_SUGGEST));

		// Compile data for SOAP call:
		$extension = array(
			'extensionKey' => $extensionKey,
			'version' => $this->valueOrDefault($extensionData, 'version'),
			'metaData' => array(
				'title' => $this->valueOrDefault($extensionData, 'title'),
				'description' => $this->valueOrDefault($extensionData, 'description'),
				'category' => $this->valueOrDefault($extensionData, 'category'),
				'state' => $this->valueOrDefault($extensionData, 'state'),
				'authorName' => $this->valueOrDefault($extensionData, 'author'),
				'authorEmail' => $this->valueOrDefault($extensionData, 'author_email'),
				'authorCompany' => $this->valueOrDefault($extensionData, 'author_company'),
			),
			'technicalData' => array(
				'dependencies' => $dependenciesArr,
				'loadOrder' => $this->valueOrDefault($extensionData, 'loadOrder'),
				'uploadFolder' => (boolean) $this->valueOrDefault($extensionData, 'uploadFolder'),
				'createDirs' => $this->valueOrDefault($extensionData, 'createDirs'),
				'shy' => $this->valueOrDefault($extensionData, 'shy', FALSE),
				'modules' => $this->valueOrDefault($extensionData, 'module'),
				'modifyTables' => $this->valueOrDefault($extensionData, 'modify_tables'),
				'priority' => $this->valueOrDefault($extensionData, 'priority'),
				'clearCacheOnLoad' => (boolean) $this->valueOrDefault($extensionData, 'clearCacheOnLoad'),
				'lockType' => $this->valueOrDefault($extensionData, 'lockType'),
				'doNotLoadInFEe' => $this->valueOrDefault($extensionData, 'doNotLoadInFE'),
				'docPath' => $this->valueOrDefault($extensionData, 'docPath'),
			),
			'infoData' => array(
				'codeLines' => intval($extensionData['misc']['codelines']),
				'codeBytes' => intval($extensionData['misc']['codebytes']),
				'codingGuidelinesCompliance' => $this->valueOrDefault($extensionData, 'CGLcompliance'),
				'codingGuidelinesComplianceNotes' => $this->valueOrDefault($extensionData, 'CGLcompliance_note'),
				'uploadComment' => $comment,
				'techInfo' => $extensionData['techInfo'],
			),
		);

		$files = array();
		foreach ($extensionData['FILES'] as $filename => $infoArr) {
			$files[] = array(
				'name' => $infoArr['name'],
				'size' => intval($infoArr['size']),
				'modificationTime' => intval($infoArr['mtime']),
				'isExecutable' => intval($infoArr['is_executable']),
				'content' => $infoArr['content'],
				'contentMD5' => $infoArr['content_md5'],
			);
		}
		$compier = new SoapDataCompiler();
		return $compier->createSoapData($username, $password, array(
			'extensionData' => $extension,
			'filesData' => $files
		));
	}

	/**
	 * @param string $directory
	 * @return array
	 */
	protected function createFileDataArray($directory) {

		// Initialize output array:
		$uploadArray = array();
		$uploadArray['extKey'] = rtrim(pathinfo($directory, PATHINFO_FILENAME), '/');
		$uploadArray['misc']['codelines'] = 0;
		$uploadArray['misc']['codebytes'] = 0;

		$uploadArray['techInfo'] = 'All good, baby';

		$uploadArray['FILES'] = array();
		$directoryLength = strlen(rtrim($directory, '/')) + 1;

		$gitIgnoreFile = $directory . '/.gitignore';
		$skipFiles = [];
		if (file_exists($gitIgnoreFile)) {
            $lines = file($gitIgnoreFile);
            foreach ($lines as $line) {
                $line = trim($line);
                // Since we only support the root .gitignore as skip list, all paths *are* relative. Strip absolute.
                $line = ltrim($line, '/');
                if ($line === '') continue;                 # empty line
                if (substr($line, 0, 1) == '#') continue;   # a comment
                if (substr($line, 0, 1) == '!') {           # negated glob
                    $line = substr($line, 1);
                    $files = array_diff(glob("$directory/*"), glob("$directory/$line"));
                } else {                                    # normal glob
                    $files = glob("$directory/$line");
                }
                $skipFiles = array_merge($skipFiles, $files);
            }
        }

		foreach ($this->recursivelyScanFolderForFiles($directory, $skipFiles) as $filename) {
            $relativeFilename = substr($filename, $directoryLength);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $uploadArray['FILES'][$relativeFilename] = array(
                'name' => $relativeFilename,
                'size' => filesize($filename    ),
                'mtime' => filemtime($filename),
                'is_executable' => is_executable($filename),
                'content' => file_get_contents($filename)
            );
            if (TRUE === in_array($extension, array('php', 'inc'))) {
                $uploadArray['FILES'][$relativeFilename]['codelines'] = count(explode(PHP_EOL, $uploadArray['FILES'][$relativeFilename]['content']));
                $uploadArray['misc']['codelines'] += $uploadArray['FILES'][$relativeFilename]['codelines'];
                $uploadArray['misc']['codebytes'] += $uploadArray['FILES'][$relativeFilename]['size'];
            }
            $uploadArray['FILES'][$relativeFilename]['content_md5'] = md5($uploadArray['FILES'][$relativeFilename]['content']);
		}

		return $uploadArray;
	}

    /**
     * @param string $directory
     * @param array $skipFiles
     * @return array
     */
	protected function recursivelyScanFolderForFiles($directory, array $skipFiles) {
	    $iterator = new \DirectoryIterator($directory);
	    $files = [];
	    foreach ($iterator as $fileOrFolder) {
	        if (!$this->isFilePermitted($fileOrFolder, $directory)) {
	            continue;
            }
            if (in_array($fileOrFolder->getPathname(), $skipFiles)) {
                echo 'Skipped ' . $fileOrFolder->getPathname() . PHP_EOL;
	            continue;
            }
            if ($fileOrFolder->isDir()) {
	            $files = array_merge($files, $this->recursivelyScanFolderForFiles($fileOrFolder->getPathname(), $skipFiles));
            } else {
                $files[] = $fileOrFolder->getPathname();
            }
        }
        return $files;
    }

	/**
	 * @param \SplFileInfo $file
	 * @param string $inPath
	 * @return boolean
	 */
	protected function isFilePermitted(\SplFileInfo $file, $inPath) {
		$name = $file->getFilename();
		if (TRUE === $this->isDotFileAndNotPermitted($name)) {
			return FALSE;
		}
		$consideredPathLength = strlen($inPath);
		foreach (explode('/', trim(substr($file->getPathname(), $consideredPathLength), '/')) as $segment) {
			if (TRUE === $this->isDotFileAndNotPermitted($segment)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * @param string $filename
	 * @return boolean
	 */
	protected function isDotFileAndNotPermitted($filename) {
		return (FALSE === empty($filename) && '.' === $filename{0} && FALSE === in_array($filename, $this->permittedDotFiles));
	}

}
