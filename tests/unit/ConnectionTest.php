<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\Connection;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectionTest
 */
class ConnectionTest extends TestCase
{
    public function testGetAuthenticationHeaderReturnsSoapHeader()
    {
        $connection = new Connection();

        $method = new \ReflectionMethod($connection, 'getAuthenticationHeader');
        $method->setAccessible(true);

        $result = $method->invoke($connection, 'usernamefoobar', 'passwordfoobar');

        self::assertInstanceOf('SoapHeader', $result);
    }

    public function testGetSoapClientForWsdlReturnsSoapClient()
    {
        $connection = new Connection();

        $method = new \ReflectionMethod($connection, 'getSoapClientForWsdl');
        $method->setAccessible(true);

        $result = $method->invoke($connection, Connection::WSDL_URL);

        self::assertInstanceOf('SoapClient', $result);
    }

    /**
     * @dataProvider getCallArguments
     * @param string $function
     * @param mixed $output
     * @param string $expectedExceptionMessage
     * @param string|null $expectedExceptionType
     */
    public function testCall($function, $output, $expectedExceptionMessage, $expectedExceptionType)
    {
        $parameters = ['foo' => 'bar'];
        $settings = ['exceptions' => true, 'trace' => true];

        $client = $this->getMockBuilder('SoapClient')
            ->setMethods(['__soapCall'])
            ->disableOriginalConstructor()
            ->getMock();

        $client->expects(self::once())
            ->method('__soapCall')
            ->with($function, $parameters, $settings)
            ->will(self::returnValue($output));

        $connection = $this->getMockBuilder(Connection::class)
            ->setMethods(['getSoapClientForWsdl'])
            ->getMock();

        $connection->expects(self::once())
            ->method('getSoapClientForWsdl')
            ->with(Connection::WSDL_URL)
            ->will(self::returnValue($client));

        if (null !== $expectedExceptionType) {
            $this->expectException($expectedExceptionType, $expectedExceptionMessage);
        }
        $connection->call($function, $parameters, 'usernamefoobar', 'passwordfoobar');
    }

    /**
     * @return array
     */
    public function getCallArguments(): array
    {
        return [
            ['test', [Connection::SOAP_RETURN_CODE => Connection::SOAP_CODE_SUCCESS], null, null],
            ['test', [], 'TER command "test" failed without a return code', 'RuntimeException'],
            [
                'test',
                [Connection::SOAP_RETURN_CODE => 123],
                'TER command "test" failed; code was 123',
                'RuntimeException'
            ],
            ['test', new \SoapFault('Server', 'Probe error'), 'Probe error', 'SoapFault']
        ];
    }
}
