<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\Uploader;

/**
 * Class UploaderTest
 */
class UploaderTest extends \PHPUnit_Framework_TestCase {

	public function testUpload() {
		$original = new Uploader();
		$getConnection = new \ReflectionMethod($original, 'getConnection');
		$getExtensionUploadPacker = new \ReflectionMethod($original, 'getExtensionUploadPacker');
		$getConnection->setAccessible(TRUE);
		$getExtensionUploadPacker->setAccessible(TRUE);
		$connection = $getConnection->invoke($original);
		$packer = $getExtensionUploadPacker->invoke($original);
		$mockConnection = $this->getMock(get_class($connection), array('call'));
		$mockPacker = $this->getMock(get_class($packer), array('pack'));
		$mockConnection->expects($this->once())->method('call')->will($this->returnValue('foobarbaz'));
		$mockPacker->expects($this->once())->method('pack')->will($this->returnValue(array()));
		$mock = $this->getMock('NamelessCoder\\TYPO3RepositoryClient\\Uploader', array('getConnection', 'getExtensionUploadPacker'));
		$mock->expects($this->once())->method('getConnection')->will($this->returnValue($mockConnection));
		$mock->expects($this->once())->method('getExtensionUploadPacker')->will($this->returnValue($mockPacker));
		$result = $mock->upload('foo', 'bar', 'baz');
		$this->assertEquals('foobarbaz', $result);
	}

}
