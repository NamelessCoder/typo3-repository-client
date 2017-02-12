<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\Deleter;
use PHPUnit\Framework\TestCase;

/**
 * Class DeleterTest
 */
class DeleterTest extends TestCase {

	public function testDelete() {
		$original = new Deleter();
		$getConnection = new \ReflectionMethod($original, 'getConnection');
		$getConnection->setAccessible(TRUE);
		$connection = $getConnection->invoke($original);
		$mockConnection = $this->getMockBuilder(get_class($connection))->setMethods(array('call'))->getMock();
		$mockConnection->expects($this->once())->method('call')->will($this->returnValue('foobarbaz'));
		$mock = $this->getMockBuilder('NamelessCoder\\TYPO3RepositoryClient\\Deleter')->setMethods(array('getConnection'))->getMock();
		$mock->expects($this->once())->method('getConnection')->will($this->returnValue($mockConnection));
		$result = $mock->deleteExtensionVersion('foo', '1.2.3', 'user', 'pass');
		$this->assertEquals('foobarbaz', $result);
	}

}
