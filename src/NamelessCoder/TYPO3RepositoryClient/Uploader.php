<?php
namespace NamelessCoder\TYPO3RepositoryClient;

/**
 * Class Uploader
 */
class Uploader {

	/**
	 * @param string $directory
	 * @param string $username
	 * @param string $password
	 * @return array
	 */
	public function upload($directory, $username, $password) {
		$connection = new Connection();
		$packer = new ExtensionUploadPacker();
		$data = $packer->createSoapData();
		$output = $this->call(Connection::FUNCTION_UPLOAD, $data, $username, $password);
		return $output;
	}

}
