<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\Versioner;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

/**
 * Class VersionerTest
 */
class VersionerTest extends TestCase {

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

	public static function setUpBeforeClass() {
		self::$fixtureString = '<' . '?php' . PHP_EOL . '$EM_CONF[$_EXTKEY] = ' . var_export(self::$fixture, TRUE) . ';' . PHP_EOL;
		$emConf = new vfsStreamFile(Versioner::FILENAME_EXTENSIONCONFIGURATION);
		$emConf->setContent(self::$fixtureString);
		$composer = new vfsStreamFile(Versioner::FILENAME_COMPOSER);
		$composer->setContent(json_encode(self::$fixture, JSON_UNESCAPED_SLASHES));
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('temp', 0777));
		vfsStreamWrapper::getRoot()->addChild($emConf);
		vfsStreamWrapper::getRoot()->addChild($composer);
	}

	public function testRead() {
		$return = array(
			Versioner::PARAMETER_VERSION => '1.2.3',
			Versioner::PARAMETER_STABILITY => Versioner::STABILITY_STABLE
		);
		$versioner = $this->getMockBuilder(
			'NamelessCoder\\TYPO3RepositoryClient\\Versioner'
        )->setMethods(
			array('getExtensionConfigurationFilename', 'readExtensionConfigurationFile')
		)->getMock();
		$versioner->expects($this->once())->method('getExtensionConfigurationFilename');
		$versioner->expects($this->once())->method('readExtensionConfigurationFile')->will($this->returnValue($return));
		$result = $versioner->read('.');
		$this->assertEquals(array('1.2.3', Versioner::STABILITY_STABLE), $result);
	}

	/**
	 * @param boolean $composerUnwritable
	 * @param boolean $extensionConfigurationUnwritable
	 * @dataProvider getWriteTestValues
	 */
	public function testWrite($composerUnwritable, $extensionConfigurationUnwritable) {
		$versioner = $this->getMockBuilder(
			'NamelessCoder\\TYPO3RepositoryClient\\Versioner'
        )->setMethods(
			array('getExtensionConfigurationFilename', 'getComposerFilename', 'writeComposerFile', 'writeExtensionConfigurationFile')
		)->getMock();
		$versioner->expects($this->once())->method('getExtensionConfigurationFilename');
		$versioner->expects($this->once())->method('getComposerFilename');
		if (TRUE === $composerUnwritable) {
			$this->expectException('RuntimeException');
			$versioner->expects($this->once())->method('writeComposerFile')->will($this->returnValue(FALSE));
		} else {
			$versioner->expects($this->once())->method('writeComposerFile')->will($this->returnValue(TRUE));
			if (TRUE === $extensionConfigurationUnwritable) {
				$versioner->expects($this->once())->method('writeExtensionConfigurationFile')->will($this->returnValue(TRUE));
			} else {
				$this->expectException('RuntimeException');
				$versioner->expects($this->once())->method('writeExtensionConfigurationFile')->will($this->returnValue(FALSE));
			}
		}
		$result = $versioner->write('.', '1.2.3', 'stable');
		if (FALSE === $composerUnwritable && FALSE === $extensionConfigurationUnwritable) {
			$this->assertTrue($result);
		}
	}

	/**
	 * @return array
	 */
	public function getWriteTestValues() {
		return array(
			array(FALSE, FALSE),
			array(TRUE, FALSE),
			array(FALSE, TRUE),
			array(TRUE, TRUE),
		);
	}

	/**
	 * @dataProvider getGetComposerFilenameTestValues
	 * @param string $directory
	 * @param string $expected
	 */
	public function testGetComposerFilename($directory, $expected) {
		$versioner = new Versioner();
		$method = new \ReflectionMethod($versioner, 'getComposerFilename');
		$method->setAccessible(TRUE);
		$result = $method->invokeArgs($versioner, array($directory));
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getGetComposerFilenameTestValues() {
		return array(
			array('/foo/bar', '/foo/bar/composer.json'),
			array('/foo/bar/', '/foo/bar/composer.json')
		);
	}

	/**
	 * @dataProvider getGetExtensionConfigurationFilenameTestValues
	 * @param string $directory
	 * @param string $expected
	 */
	public function testExtensionConfigurationFilename($directory, $expected) {
		$versioner = new Versioner();
		$method = new \ReflectionMethod($versioner, 'getExtensionConfigurationFilename');
		$method->setAccessible(TRUE);
		$result = $method->invokeArgs($versioner, array($directory));
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getGetExtensionConfigurationFilenameTestValues() {
		return array(
			array('/foo/bar', '/foo/bar/ext_emconf.php'),
			array('/foo/bar/', '/foo/bar/ext_emconf.php')
		);
	}

	/**
	 * @param string $filename
	 * @param string $data
	 * @param boolean $expectsException
	 * @dataProvider getReadComposerFileTestValues
	 */
	public function testReadComposerFile($filename, $expectedData, $expectsException) {
		$versioner = new Versioner();
		$method = new \ReflectionMethod($versioner, 'readComposerFile');
		$method->setAccessible(TRUE);
		if (TRUE === $expectsException) {
			$this->expectException('RuntimeException');
		}
		$result = $method->invokeArgs($versioner, array($filename));
		$this->assertEquals($expectedData, $result);
	}

	/**
	 * @return array
	 */
	public function getReadComposerFileTestValues() {
		return array(
			array(vfsStream::url('temp/' . Versioner::FILENAME_COMPOSER), self::$fixture, FALSE),
			array(vfsStream::url('temp-does-not-exist/' . Versioner::FILENAME_COMPOSER), NULL, TRUE),
		);
	}

	/**
	 * @param string $filename
	 * @param string $data
	 * @param boolean $expectsException
	 * @dataProvider getReadExtensionConfigurationFileTestValues
	 */
	public function testReadExtensionConfigurationFile($filename, $expectedData, $expectsException) {
		$versioner = new Versioner();
		$method = new \ReflectionMethod($versioner, 'readExtensionConfigurationFile');
		$method->setAccessible(TRUE);
		if (TRUE === $expectsException) {
			$this->expectException('RuntimeException');
		}
		$result = $method->invokeArgs($versioner, array($filename));
		$this->assertEquals($expectedData, $result);
	}

	/**
	 * @return array
	 */
	public function getReadExtensionConfigurationFileTestValues() {
		return array(
			array(vfsStream::url('temp/' . Versioner::FILENAME_EXTENSIONCONFIGURATION), self::$fixture, FALSE),
			array(vfsStream::url('temp-does-not-exist/' . Versioner::FILENAME_EXTENSIONCONFIGURATION), NULL, TRUE),
		);
	}

	/**
	 * @param string $filename
	 * @param string $version
	 * @param boolean $expectsException
	 * @dataProvider getWriteComposerFileTestValues
	 */
	public function testWriteComposerFile($filename, $version, $expectsException) {
		$expectedData = json_encode(array_merge(self::$fixture, array('version' => $version)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$versioner = new Versioner();
		$method = new \ReflectionMethod($versioner, 'writeComposerFile');
		$method->setAccessible(TRUE);
		if (TRUE === $expectsException) {
			$this->expectException('RuntimeException');
		}
		$result = $method->invokeArgs($versioner, array($filename, $version));
		$this->assertStringEqualsFile($filename, $expectedData);
	}

	/**
	 * @return array
	 */
	public function getWriteComposerFileTestValues() {
		return array(
			array(vfsStream::url('temp/' . Versioner::FILENAME_COMPOSER), '1.2.3', FALSE),
			array(vfsStream::url('temp/' . Versioner::FILENAME_COMPOSER), '3.2.1', FALSE),
			array(vfsStream::url('temp-does-not-exist/' . Versioner::FILENAME_COMPOSER), '1.2.3', TRUE),
		);
	}

	public function testWriteComposerFileReturnsWithoutWritingFileIfFileDoesNotContainVersion() {
		$versioner = new Versioner();
		$method = new \ReflectionMethod($versioner, 'writeComposerFile');
		$method->setAccessible(TRUE);
		$fixture = self::$fixture;
		unset($fixture['version']);
		$noVersionFile = Versioner::FILENAME_COMPOSER . '.noversion.json';
		$newComposerFile = new vfsStreamFile($noVersionFile);
		$newComposerFile->setContent(json_encode($fixture, JSON_UNESCAPED_SLASHES));
		vfsStreamWrapper::getRoot()->addChild($newComposerFile);
		$vfsUrl = vfsStream::url('temp/' . $noVersionFile);
		$result = $method->invokeArgs($versioner, array($vfsUrl, '1.2.3'));
		$this->assertTrue($result);
		$this->assertNotContains('1.2.3', file_get_contents($vfsUrl));
	}

	/**
	 * @param string $filename
	 * @param string $version
	 * @param string $stability
	 * @param boolean $expectsException
	 * @dataProvider getWriteExtensionConfigurationFileTestValues
	 */
	public function testWriteExtensionConfigurationFile($filename, $version, $stability, $expectsException) {
		$fixture = self::$fixture;
		$fixture['version'] = $version;
		$fixture['state'] = $stability;
		$expectedData = '<' . '?php' . PHP_EOL . '$EM_CONF[$_EXTKEY] = ' . var_export($fixture, TRUE) . ';' . PHP_EOL;
		$versioner = new Versioner();
		$method = new \ReflectionMethod($versioner, 'writeExtensionConfigurationFile');
		$method->setAccessible(TRUE);
		if (TRUE === $expectsException) {
			$this->expectException('RuntimeException');
		}
		$result = $method->invokeArgs($versioner, array($filename, $version, $stability));
		$this->assertStringEqualsFile($filename, $expectedData);
	}

	/**
	 * @return array
	 */
	public function getWriteExtensionConfigurationFileTestValues() {
		return array(
			array(vfsStream::url('temp/' . Versioner::FILENAME_EXTENSIONCONFIGURATION), '1.2.3', Versioner::STABILITY_STABLE, FALSE),
			array(vfsStream::url('temp/' . Versioner::FILENAME_EXTENSIONCONFIGURATION), '3.2.1', Versioner::STABILITY_BETA, FALSE),
			array(vfsStream::url('temp-does-not-exist/' . Versioner::FILENAME_EXTENSIONCONFIGURATION), '1.2.3', Versioner::STABILITY_STABLE, TRUE),
		);
	}

}
