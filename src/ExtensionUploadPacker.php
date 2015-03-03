<?php
namespace NamelessCoder\TYPO3RepositoryClient;

/**
 * Class ExtensionUploadPacker
 */
class ExtensionUploadPacker {

	const KIND_DEPENDENCY = 'depends';
	const KIND_CONFLICT = 'conflicts';
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
		$data = $this->createFileDataArray($directory);
		$data['EM_CONF'] = $this->readExtensionConfigurationFile($directory, $extensionKey);
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
		return $EM_CONF[$_EXTKEY];
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
		$directoryIterator = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new \RecursiveIteratorIterator($directoryIterator);
		foreach ($iterator as $file) {
			/** @var \SplFileInfo $file */
			if (TRUE === $this->isFilePermitted($file, $directory)) {
				$filename = $file->getPathname();
				$relativeFilename = substr($filename, $directoryLength);
				$extension = pathinfo($filename, PATHINFO_EXTENSION);
				$uploadArray['FILES'][$relativeFilename] = array(
					'name' => $relativeFilename,
					'size' => filesize($file),
					'mtime' => filemtime($file),
					'is_executable' => is_executable($file),
					'content' => file_get_contents($file)
				);
				if (TRUE === in_array($extension, array('php', 'inc'))) {
					$uploadArray['FILES'][$relativeFilename]['codelines'] = count(explode(PHP_EOL, $uploadArray['FILES'][$relativeFilename]['content']));
					$uploadArray['misc']['codelines'] += $uploadArray['FILES'][$relativeFilename]['codelines'];
					$uploadArray['misc']['codebytes'] += $uploadArray['FILES'][$relativeFilename]['size'];
				}
				$uploadArray['FILES'][$relativeFilename]['content_md5'] = md5($uploadArray['FILES'][$relativeFilename]['content']);
			}
		}

		return $uploadArray;
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
