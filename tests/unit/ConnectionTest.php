<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;
use NamelessCoder\TYPO3RepositoryClient\Connection;

/**
 * Class ConnectionTest
 */
class ConnectionTest extends \PHPUnit_Framework_TestCase {

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
		$client = $this->getMock('SoapClient', array('__soapCall'), array(), '', FALSE);
		$client->expects($this->once())->method('__soapCall')->with($function, $parameters, $settings)->will($this->returnValue($output));
		$connection = $this->getMock('NamelessCoder\\TYPO3RepositoryClient\\Connection', array('getSoapClientForWsdl'));
		$connection->expects($this->once())->method('getSoapClientForWsdl')->with(Connection::WSDL_URL)->will($this->returnValue($client));
		if (NULL !== $expectedExceptionType) {
			$this->setExpectedException($expectedExceptionType, $expectedExceptionMessage);
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
