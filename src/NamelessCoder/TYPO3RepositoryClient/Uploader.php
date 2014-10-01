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
	 * @param string $comment
	 * @return array
	 */
	public function upload($directory, $username, $password, $comment = NULL) {
		$connection = new Connection();
		$packer = new ExtensionUploadPacker();
		$data = $packer->pack($directory, $username, $password, $comment);
		$output = $connection->call(Connection::FUNCTION_UPLOAD, $data, $username, $password);
		return $output;
	}

}
