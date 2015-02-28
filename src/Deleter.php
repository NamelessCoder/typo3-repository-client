<?php
namespace NamelessCoder\TYPO3RepositoryClient;

/**
 * Class Deleter
 */
class Deleter {

	/**
	 * @param string $extensionKey
	 * @param string $version
	 * @param string $username
	 * @param string $password
	 * @return array
	 */
	public function deleteExtensionVersion($extensionKey, $version, $username, $password) {
		$compiler = new SoapDataCompiler();
		$payload = $compiler->createSoapData($username, $password, array(
			'extensionKey' => $extensionKey,
			'version' => $version
		));
		return $this->getConnection()->call(
			Connection::FUNCTION_DELETEVERSION,
			$payload,
			$username,
			$password
		);
	}

	/**
	 * @return Connection
	 */
	protected function getConnection() {
		return new Connection();
	}

}
