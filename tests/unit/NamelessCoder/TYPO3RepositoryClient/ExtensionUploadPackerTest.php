<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\ExtensionUploadPacker;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Class ExtensionUploadPackerTest
 */
class ExtensionUploadPackerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var array
	 */
	protected static $fixture = array(
		'title' => 'Dummy title', 'description' => 'Dummy description',
		'category' => 'misc', 'shy' => 0, 'version' => '1.2.3', 'dependencies' => 'cms,extbase,fluid',
		'conflicts' => '', 'priority' => '', 'loadOrder' => '', 'module' => '', 'state' => 'beta',
		'uploadfolder' => 0, 'createDirs' => '', 'modify_tables' => '', 'clearcacheonload' => 1,
		'lockType' => '', 'author' => 'Author Name', 'author_email' => 'author@domain.com',
		'author_company' => '', 'CGLcompliance' => '', 'CGLcompliance_note' => '',
		'constraints' => array('depends' => array('typo3' => '6.1.0-6.2.99', 'cms' => ''), 'conflicts' => array(), 'suggests' => array()),
		'_md5_values_when_last_written' => ''
	);

	/**
	 * @var string
	 */
	protected static $fixtureString = NULL;

	/**
	 * @var integer
	 */
	protected static $mtime = NULL;

	public static function setUpBeforeClass() {
		self::$mtime = time();
		self::$fixtureString = '<' . '?php
			$EM_CONF[$_EXTKEY] = ' . var_export(self::$fixture, TRUE) . ';
		';
		$emConf = new vfsStreamFile('ext_emconf.php');
		$emConf->setContent(self::$fixtureString);
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('temp', 0777));
		vfsStreamWrapper::getRoot()->addChild($emConf);
	}

	public function testReadExtensionConfigurationFileThrowsExceptionIfFileDoesNotExist() {
		$directory = vfsStream::url('doesnotexist');
		$extensionKey = 'temp';
		$packer = new ExtensionUploadPacker();
		$method = new \ReflectionMethod($packer, 'readExtensionConfigurationFile');
		$method->setAccessible(TRUE);
		$this->setExpectedException('RuntimeException');
		$configuration = $method->invoke($packer, $directory, $extensionKey);
	}

	public function testPack() {
		$directory = vfsStream::url('temp');
		$extensionKey = 'temp';
		$mock = $this->getMock(
			'NamelessCoder\\TYPO3RepositoryClient\\ExtensionUploadPacker',
			array('createFileDataArray', 'createSoapData')
		);
		$method = new \ReflectionMethod($mock, 'readExtensionConfigurationFile');
		$method->setAccessible(TRUE);
		$configuration = $method->invoke($mock, $directory, $extensionKey);
		$mock->expects($this->once())->method('createFileDataArray')
			->with($directory)->will($this->returnValue(array('foo' => 'bar')));
		$mock->expects($this->once())->method('createSoapData')
			->with($extensionKey, array('foo' => 'bar', 'EM_CONF' => $configuration), 'usernamefoo', 'passwordfoo', 'commentfoo')
			->will($this->returnValue('test'));
		$result = $mock->pack($directory, 'usernamefoo', 'passwordfoo', 'commentfoo');
		$this->assertEquals('test', $result);
	}

	public function testCreateFileDataArray() {
		$directory = vfsStream::url('temp/');
		$packer = new ExtensionUploadPacker();
		$method = new \ReflectionMethod($packer, 'createFileDataArray');
		$method->setAccessible(TRUE);
		$result = $method->invoke($packer, $directory);
		$this->assertEquals(array('extKey' => 'temp', 'misc' => array('codelines' => 40, 'codebytes' => 825),
			'techInfo' => 'All good, baby', 'FILES' => array('ext_emconf.php' => array('name' => 'ext_emconf.php',
			'size' => 825, 'mtime' => self::$mtime, 'is_executable' => FALSE, 'content' => self::$fixtureString,
			'content_md5' => '96ac5dcefad9a409f516bf046dbcb9ef', 'codelines' => 40)
		)), $result);
	}

	/**
	 * @dataProvider getSettingsAndValues
	 * @param array $settings
	 * @param string $settingName
	 * @param mixed $defaultValue
	 * @param mixed $expectedValue
	 */
	public function testGetValueOrDefault($settings, $settingName, $defaultValue, $expectedValue) {
		$packer = new ExtensionUploadPacker();
		$method = new \ReflectionMethod($packer, 'valueOrDefault');
		$method->setAccessible(TRUE);
		$result = $method->invoke($packer, array('EM_CONF' => $settings), $settingName, $defaultValue);
		$this->assertEquals($expectedValue, $result);
	}

	/**
	 * @return array
	 */
	public function getSettingsAndValues() {
		return array(
			array(array('foo' => 'bar'), 'foo', 'baz', 'bar'),
			array(array('foo' => 'bar'), 'foo2', 'baz', 'baz'),
		);
	}

}
