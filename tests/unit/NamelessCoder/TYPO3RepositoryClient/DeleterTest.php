<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\Deleter;

/**
 * Class DeleterTest
 */
class DeleterTest extends \PHPUnit_Framework_TestCase {

	public function testDelete() {
		$original = new Deleter();
		$getConnection = new \ReflectionMethod($original, 'getConnection');
		$getConnection->setAccessible(TRUE);
		$connection = $getConnection->invoke($original);
		$mockConnection = $this->getMock(get_class($connection), array('call'));
		$mockConnection->expects($this->once())->method('call')->will($this->returnValue('foobarbaz'));
		$mock = $this->getMock('NamelessCoder\\TYPO3RepositoryClient\\Deleter', array('getConnection'));
		$mock->expects($this->once())->method('getConnection')->will($this->returnValue($mockConnection));
		$result = $mock->deleteExtensionVersion('foo', '1.2.3', 'user', 'pass');
		$this->assertEquals('foobarbaz', $result);
	}

}
