<?php
namespace NamelessCoder\TYPO3RepositoryClient;

/**
 * Class Versioner
 */
class Versioner {

	const PARAMETER_VERSION = 'version';
	const PARAMETER_STABILITY = 'state';
	const FILENAME_EXTENSIONCONFIGURATION = 'ext_emconf.php';
	const FILENAME_COMPOSER = 'composer.json';
	const STABILITY_STABLE = 'stable';
	const STABILITY_BETA = 'beta';
	const STABILITY_ALPHA = 'alpha';
	const STABILITY_EXPERIMENTAL = 'experimental';
	const STABILITY_OBSOLETE = 'obsolete';

	/**
	 * Reads the current version and stability from
	 * composer and extension configuration. Returns
	 * an array [$version, $stability]
	 *
	 * @param string $directory
	 * @return array
	 */
	public function read($directory) {
		$directory = realpath($directory);
		$filename = $this->getExtensionConfigurationFilename($directory);
		$configuration = $this->readExtensionConfigurationFile($filename);
		return array($configuration[self::PARAMETER_VERSION], $configuration[self::PARAMETER_STABILITY]);
	}

	/**
	 * @param string $directory
	 * @param string $version
	 * @param string $stability
	 * @throws \RuntimeException
	 * @return boolean
	 */
	public function write($directory, $version, $stability = self::STABILITY_STABLE) {
		$extensionConfigurationFilename = $this->getExtensionConfigurationFilename($directory);
		$composerFilename = $this->getComposerFilename($directory);
		if (FALSE === $this->writeComposerFile($composerFilename, $version)) {
			throw new \RuntimeException('Could not write ' . $composerFilename . ' - please check permissions');
		}
		if (FALSE === $this->writeExtensionConfigurationFile($extensionConfigurationFilename, $version, $stability)) {
			throw new \RuntimeException('Could not write ' . $extensionConfigurationFilename . ' - please check permissions');
		}
		return TRUE;
	}

	/**
	 * @param string $directory
	 * @return string
	 */
	protected function getComposerFilename($directory) {
		return rtrim($directory, '/') . '/' . self::FILENAME_COMPOSER;
	}

	/**
	 * @param string $directory
	 * @return string
	 */
	protected function getExtensionConfigurationFilename($directory) {
		return rtrim($directory, '/') . '/' . self::FILENAME_EXTENSIONCONFIGURATION;
	}

	/**
	 * @param string $filename
	 * @return array
	 */
	protected function readComposerFile($filename) {
		if (FALSE === file_exists($filename)) {
			throw new \RuntimeException('Expected composer file ' . $filename . ' does not exist');
		}
		return json_decode(file_get_contents($filename), JSON_OBJECT_AS_ARRAY);
	}

	/**
	 * @param string $filename
	 * @return array
	 */
	protected function readExtensionConfigurationFile($filename) {
		if (FALSE === file_exists($filename)) {
			throw new \RuntimeException('Extension configuration file ' . $filename . ' does not exist');
		}
		$_EXTKEY = 'dummy';
		include $filename;
		return $EM_CONF['dummy'];
	}

	/**
	 * @param string $filename
	 * @return boolean
	 */
	protected function writeComposerFile($filename, $version) {
		$configuration = $this->readComposerFile($filename);
		if (empty($configuration[self::PARAMETER_VERSION])) {
			// No version was set in composer.json, so do not enforce it now
			return true;
		}
		$configuration[self::PARAMETER_VERSION] = $version;
		return file_put_contents($filename, json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}

	/**
	 * @param string $filename
	 * @param string $version
	 * @param string $stability
	 * @return boolean
	 */
	protected function writeExtensionConfigurationFile($filename, $version, $stability) {
		$configuration = $this->readExtensionConfigurationFile($filename);
		$configuration[self::PARAMETER_VERSION] = $version;
		$configuration[self::PARAMETER_STABILITY] = $stability;
		$contents = '<' . '?php' . PHP_EOL . '$EM_CONF[$_EXTKEY] = ' . var_export($configuration, TRUE) . ';' . PHP_EOL;
		return file_put_contents($filename, $contents);
	}

}
