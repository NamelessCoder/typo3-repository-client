<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\Uploader;
use PHPUnit\Framework\TestCase;

/**
 * Class UploaderTest
 */
class UploaderTest extends TestCase {

	public function testUpload() {
		$original = new Uploader();
		$getConnection = new \ReflectionMethod($original, 'getConnection');
		$getExtensionUploadPacker = new \ReflectionMethod($original, 'getExtensionUploadPacker');
		$getConnection->setAccessible(TRUE);
		$getExtensionUploadPacker->setAccessible(TRUE);
		$connection = $getConnection->invoke($original);
		$packer = $getExtensionUploadPacker->invoke($original);
		$mockConnection = $this->getMockBuilder(get_class($connection))->setMethods(array('call'))->getMock();
		$mockPacker = $this->getMockBuilder(get_class($packer))->setMethods(array('pack'))->getMock();
		$mockConnection->expects($this->once())->method('call')->will($this->returnValue('foobarbaz'));
		$mockPacker->expects($this->once())->method('pack')->will($this->returnValue(array()));
		$mock = $this->getMockBuilder('NamelessCoder\\TYPO3RepositoryClient\\Uploader')->setMethods(array('getConnection', 'getExtensionUploadPacker'))->getMock();
		$mock->expects($this->once())->method('getConnection')->will($this->returnValue($mockConnection));
		$mock->expects($this->once())->method('getExtensionUploadPacker')->will($this->returnValue($mockPacker));
		$result = $mock->upload('foo', 'bar', 'baz');
		$this->assertEquals('foobarbaz', $result);
	}

}
