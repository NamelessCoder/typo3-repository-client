<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\Deleter;
use PHPUnit\Framework\TestCase;

/**
 * Class DeleterTest
 */
class DeleterTest extends TestCase
{
    public function testDelete()
    {
        $original = new Deleter();

        $getConnection = new \ReflectionMethod($original, 'getConnection');
        $getConnection->setAccessible(true);

        $connection = $getConnection->invoke($original);

        $mockConnection = $this->getMockBuilder(get_class($connection))
            ->setMethods(['call'])
            ->getMock();

        $mockConnection->expects(self::once())
            ->method('call')
            ->will(self::returnValue('foobarbaz'));

        $mock = $this->getMockBuilder(Deleter::class)
            ->setMethods(['getConnection'])
            ->getMock();

        $mock->expects(self::once())
            ->method('getConnection')
            ->will(self::returnValue($mockConnection));

        $result = $mock->deleteExtensionVersion('foo', '1.2.3', 'user', 'pass');

        self::assertEquals('foobarbaz', $result);
    }
}
