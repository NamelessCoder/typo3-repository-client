<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class SoapDataCompilerTest
 */
class SoapDataCompilerTest extends TestCase {

	/**
	 * @param string $username
	 * @param string $password
	 * @param array $data
	 * @param array $expectation
	 * @dataProvider getCreateSoapDataTestValues
	 */
	public function testCreateSoapData($username, $password, $data, $expectation) {

	}

	/**
	 * @return array
	 */
	public function getCreateSoapDataTestValues() {
		return array(
			array(
				'user',
				'pass',
				array('foo' => 'bar'),
				array('foo' => 'bar', 'accountData' => array('username' => 'user', 'password' => 'pass'))
			),
			array(
				'user',
				'pass',
				array('foo' => 'bar', 'accountData' => array('username' => 'user2', 'password' => 'pass2')),
				array('foo' => 'bar', 'accountData' => array('username' => 'user2', 'password' => 'pass2'))
			),
		);
	}

}
