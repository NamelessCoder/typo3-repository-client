<?php
namespace NamelessCoder\TYPO3RepositoryClient;

/**
 * Class SoapDataCompiler
 */
class SoapDataCompiler {

	/**
	 * @param string $username
	 * @param string $password
	 * @param array $data
	 * @return array
	 */
	public function createSoapData($username, $password, array $data) {
		// Compile data for SOAP call:
		$account = array(
			'accountData' => array(
				'username' => $username,
				'password' => $password
			)
		);
		$data = array_merge($account, $data);
		return $data;
	}

}
