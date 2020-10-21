<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\Uploader;
use PHPUnit\Framework\TestCase;

/**
 * Class UploaderTest
 */
class UploaderTest extends TestCase
{
    public function testUpload()
    {
        $original = new Uploader();

        $getConnection = new \ReflectionMethod($original, 'getConnection');
        $getConnection->setAccessible(true);

        $getExtensionUploadPacker = new \ReflectionMethod($original, 'getExtensionUploadPacker');
        $getExtensionUploadPacker->setAccessible(true);

        $connection = $getConnection->invoke($original);
        $packer = $getExtensionUploadPacker->invoke($original);

        $mockConnection = $this->getMockBuilder(get_class($connection))->setMethods(['call'])->getMock();
        $mockPacker = $this->getMockBuilder(get_class($packer))->setMethods(['pack'])->getMock();

        $mockConnection->expects(self::once())
            ->method('call')
            ->will(self::returnValue('foobarbaz'));

        $mockPacker->expects(self::once())
            ->method('pack')
            ->will(self::returnValue([]));

        $mock = $this->getMockBuilder(Uploader::class)
            ->setMethods(['getConnection', 'getExtensionUploadPacker'])
            ->getMock();

        $mock->expects(self::once())
            ->method('getConnection')
            ->will(self::returnValue($mockConnection));

        $mock->expects(self::once())
            ->method('getExtensionUploadPacker')
            ->will(self::returnValue($mockPacker));

        $result = $mock->upload('foo', 'bar', 'baz');

        self::assertEquals('foobarbaz', $result);
    }
}
