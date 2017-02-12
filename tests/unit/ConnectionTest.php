<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\Connection;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectionTest
 */
class ConnectionTest extends TestCase {

	public function testGetAuthenticationHeaderReturnsSoapHeader() {
		$connection = new Connection();
		$method = new \ReflectionMethod($connection, 'getAuthenticationHeader');
		$method->setAccessible(TRUE);
		$result = $method->invoke($connection, 'usernamefoobar', 'passwordfoobar');
		$this->assertInstanceOf('SoapHeader', $result);
	}

	public function testGetSoapClientForWsdlReturnsSoapClient() {
		$connection = new Connection();
		$method = new \ReflectionMethod($connection, 'getSoapClientForWsdl');
		$method->setAccessible(TRUE);
		$result = $method->invoke($connection, Connection::WSDL_URL);
		$this->assertInstanceOf('SoapClient', $result);
	}

	/**
	 * @dataProvider getCallArguments
	 * @param string $function
	 * @param mixed $output
	 * @param string $expectedExceptionMessage
	 */
	public function testCall($function, $output, $expectedExceptionMessage, $expectedExceptionType) {
		$parameters = array('foo' => 'bar');
		$settings = array('exceptions' => TRUE, 'trace' => TRUE);
		$client = $this->getMockBuilder('SoapClient')->setMethods(array('__soapCall'))->disableOriginalConstructor()->getMock();
		$client->expects($this->once())->method('__soapCall')->with($function, $parameters, $settings)->will($this->returnValue($output));
		$connection = $this->getMockBuilder('NamelessCoder\\TYPO3RepositoryClient\\Connection')->setMethods(array('getSoapClientForWsdl'))->getMock();
		$connection->expects($this->once())->method('getSoapClientForWsdl')->with(Connection::WSDL_URL)->will($this->returnValue($client));
		if (NULL !== $expectedExceptionType) {
			$this->expectException($expectedExceptionType, $expectedExceptionMessage);
		}
		$connection->call($function, $parameters, 'usernamefoobar', 'passwordfoobar');
	}

	/**
	 * @return array
	 */
	public function getCallArguments() {
		return array(
			array('test', array(Connection::SOAP_RETURN_CODE => Connection::SOAP_CODE_SUCCESS), NULL, NULL),
			array('test', array(), 'TER command "test" failed without a return code', 'RuntimeException'),
			array('test', array(Connection::SOAP_RETURN_CODE => 123), 'TER command "test" failed; code was 123', 'RuntimeException'),
			array('test', new \SoapFault('Server', 'Probe error'), 'Probe error', 'SoapFault')
		);
	}

}
