<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\ExtensionUploadPacker;
use NamelessCoder\TYPO3RepositoryClient\Uploader;
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

	public function testCreateSoapDataCreatesExpectedOutput() {
		$directory = vfsStream::url('temp');
		$packer = new ExtensionUploadPacker();
		$result = $packer->pack($directory, 'username', 'password', 'comment');
		$expected = array (
			'accountData' => array('username' => 'username', 'password' => 'password'),
			'extensionData' => array (
				'extensionKey' => 'temp',
				'version' => '1.2.3',
				'metaData' => array (
					'title' => 'Dummy title',
					'description' => 'Dummy description',
					'category' => 'misc',
					'state' => 'beta',
					'authorName' => 'Author Name',
					'authorEmail' => 'author@domain.com',
					'authorCompany' => '',
				),
				'technicalData' => array (
					'dependencies' => array (
						array (
							'kind' => 'depends',
							'extensionKey' => 'typo3',
							'versionRange' => '6.1.0-6.2.99',
						),
						array (
							'kind' => 'depends',
							'extensionKey' => 'cms',
							'versionRange' => '',
						)
					),
					'loadOrder' => '',
					'uploadFolder' => FALSE,
					'createDirs' => '',
					'shy' => 0,
					'modules' => '',
					'modifyTables' => '',
					'priority' => '',
					'clearCacheOnLoad' => FALSE,
					'lockType' => '',
					'doNotLoadInFEe' => NULL,
					'docPath' => NULL,
				),
				'infoData' => array (
					'codeLines' => 40,
					'codeBytes' => 825,
					'codingGuidelinesCompliance' => '',
					'codingGuidelinesComplianceNotes' => '',
					'uploadComment' => 'comment',
					'techInfo' => 'All good, baby',
				),
			),
			'filesData' => array (
				array (
					'name' => '/ext_emconf.php',
					'size' => 825,
					'modificationTime' => self::$mtime,
					'isExecutable' => 0,
					'content' => self::$fixtureString,
					'contentMD5' => '96ac5dcefad9a409f516bf046dbcb9ef',
				),
			),
		);
		$this->assertEquals($expected, $result);
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

	/**
	 * @dataProvider getExtensionDataAndExpectedDependencyOutput
	 * $param string $kindOfDependency
	 * @param array $extensionData
	 * @param array $expectedOutout
	 * @param string $expectedException
	 */
	public function testCreateDependenciesArray($kindOfDependency, $extensionData, $expectedOutout, $expectedException) {
		$uploader = new ExtensionUploadPacker();
		$method = new \ReflectionMethod($uploader, 'createDependenciesArray');
		$method->setAccessible(TRUE);
		if (NULL !== $expectedException) {
			$this->setExpectedException($expectedException);
		}
		$output = $method->invoke($uploader, $extensionData, $kindOfDependency);
		$this->assertEquals($expectedOutout, $output);
	}

	/**
	 * @return array
	 */
	public function getExtensionDataAndExpectedDependencyOutput() {
		return array(
			// correct usage and input:
			array(
				ExtensionUploadPacker::KIND_DEPENDENCY,
				array(
					'EM_CONF' => array(
						'constraints' => array(
							ExtensionUploadPacker::KIND_DEPENDENCY => array(
								'foobar' => '0.0.0-1.0.0',
								'foobar2' => '1.0.0-2.0.0',
							)
						)
					)
				),
				array(
					array('kind' => 'depends', 'extensionKey' => 'foobar', 'versionRange' => '0.0.0-1.0.0'),
					array('kind' => 'depends', 'extensionKey' => 'foobar2', 'versionRange' => '1.0.0-2.0.0'),
				),
				NULL
			),
			// no deps: empty output, no error
			array(
				ExtensionUploadPacker::KIND_DEPENDENCY,
				array('EM_CONF' => array()),
				array(),
				NULL
			),
			// deps setting not an array, empty output, no error
			array(
				ExtensionUploadPacker::KIND_DEPENDENCY,
				array('EM_CONF' => array('constraints' => array(ExtensionUploadPacker::KIND_DEPENDENCY => 'iamastring'))),
				array(),
				NULL
			),
			// deps numerically indexed - error!
			array(
				ExtensionUploadPacker::KIND_DEPENDENCY,
				array(
					'EM_CONF' => array(
						'constraints' => array(
							ExtensionUploadPacker::KIND_DEPENDENCY => array(0 => array('0.0.0-1.0.0'))
						)
					)
				),
				array(),
				'RuntimeException'
			),
		);
	}

	/**
	 * @dataProvider getIsFilePermittedTestValues
	 * @param \SplFileInfo $file
	 * @param string $inPath
	 * @param boolean $expectedPermitted
	 */
	public function testIsFilePermitted(\SplFileInfo $file, $inPath, $expectedPermitted) {
		$instance = new ExtensionUploadPacker();
		$method = new \ReflectionMethod($instance, 'isFilePermitted');
		$method->setAccessible(TRUE);
		$result = $method->invokeArgs($instance, array($file, $inPath));
		$this->assertEquals($expectedPermitted, $result);
	}

	/**
	 * @return array
	 */
	public function getIsFilePermittedTestValues() {
		return array(
			array(new \SplFileInfo('/path/file'), '/path', TRUE),
			array(new \SplFileInfo('/path/.file'), '/path', FALSE),
			array(new \SplFileInfo('/path/.htaccess'), '/path', TRUE),
			array(new \SplFileInfo('/path/.htpasswd'), '/path', TRUE),
			array(new \SplFileInfo('/.git/file'), '/.git', TRUE),
			array(new \SplFileInfo('/.git/.dotfile'), '/.git', FALSE),
			array(new \SplFileInfo('/.git/.htaccess'), '/.git', TRUE),
			array(new \SplFileInfo('/.git/.htpasswd'), '/.git', TRUE),
			array(new \SplFileInfo('/path/.git/file'), '/path', FALSE),
		);
	}

}
