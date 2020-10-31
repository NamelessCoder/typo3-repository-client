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
class VersionerTest extends TestCase
{
    /**
     * @var array
     */
    protected static $fixture = [
        'title' => 'Dummy title',
        'description' => 'Dummy description',
        'category' => 'misc',
        'shy' => 0,
        'version' => '1.2.3',
        'dependencies' => 'cms,extbase,fluid',
        'conflicts' => '',
        'priority' => '',
        'loadOrder' => '',
        'module' => '',
        'state' => 'beta',
        'uploadfolder' => 0,
        'createDirs' => '',
        'modify_tables' => '',
        'clearcacheonload' => 1,
        'lockType' => '',
        'author' => 'Author Name',
        'author_email' => 'author@domain.com',
        'author_company' => '',
        'CGLcompliance' => '',
        'CGLcompliance_note' => '',
        'constraints' => [
            'depends' => [
                'typo3' => '6.1.0-6.2.99',
                'cms' => ''
            ],
            'conflicts' => [],
            'suggests' => []
        ],
        '_md5_values_when_last_written' => ''
    ];

    /**
     * @var string
     */
    protected static $fixtureString;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureString =
            '<' . '?php' . PHP_EOL . '$EM_CONF[$_EXTKEY] = ' . var_export(self::$fixture, true) . ';' . PHP_EOL;
        $emConf = new vfsStreamFile(Versioner::FILENAME_EXTENSION_CONFIGURATION);
        $emConf->setContent(self::$fixtureString);

        $composer = new vfsStreamFile(Versioner::FILENAME_COMPOSER);
        $composer->setContent(json_encode(self::$fixture, JSON_UNESCAPED_SLASHES));

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('temp', 0777));
        vfsStreamWrapper::getRoot()->addChild($emConf);
        vfsStreamWrapper::getRoot()->addChild($composer);
    }

    public function testRead()
    {
        $return = [
            Versioner::PARAMETER_VERSION => '1.2.3',
            Versioner::PARAMETER_STABILITY => Versioner::STABILITY_STABLE
        ];
        $versioner = $this->getMockBuilder(Versioner::class)
            ->setMethods(['getExtensionConfigurationFilename', 'readExtensionConfigurationFile'])
            ->getMock();

        $versioner->expects(self::once())
            ->method('getExtensionConfigurationFilename');

        $versioner->expects(self::once())
            ->method('readExtensionConfigurationFile')
            ->will(self::returnValue($return));

        $result = $versioner->read('.');

        self::assertEquals(['1.2.3', Versioner::STABILITY_STABLE], $result);
    }

    /**
     * @param bool $composerUnwritable
     * @param bool $extensionConfigurationUnwritable
     * @dataProvider getWriteTestValues
     */
    public function testWrite($composerUnwritable, $extensionConfigurationUnwritable)
    {
        $versioner = $this->getMockBuilder(Versioner::class)
            ->setMethods(
                [
                    'getExtensionConfigurationFilename',
                    'getComposerFilename',
                    'writeComposerFile',
                    'writeExtensionConfigurationFile'
                ]
            )->getMock();
        $versioner->expects(self::once())->method('getExtensionConfigurationFilename');
        $versioner->expects(self::once())->method('getComposerFilename');

        if (true === $composerUnwritable) {
            $this->expectException('RuntimeException');
            $versioner->expects(self::once())
                ->method('writeComposerFile')
                ->will(self::returnValue(false));
        } else {
            $versioner->expects(self::once())
                ->method('writeComposerFile')
                ->will(self::returnValue(true));

            if (true === $extensionConfigurationUnwritable) {
                $versioner->expects(self::once())
                    ->method('writeExtensionConfigurationFile')
                    ->will(self::returnValue(true));
            } else {
                $this->expectException('RuntimeException');
                $versioner->expects(self::once())
                    ->method('writeExtensionConfigurationFile')
                    ->will(self::returnValue(false));
            }
        }
        $result = $versioner->write('.', '1.2.3', 'stable');

        if (false === $composerUnwritable && false === $extensionConfigurationUnwritable) {
            self::assertTrue($result);
        }
    }

    /**
     * @return array
     */
    public function getWriteTestValues(): array
    {
        return [
            [false, false],
            [true, false],
            [false, true],
            [true, true],
        ];
    }

    /**
     * @dataProvider getGetComposerFilenameTestValues
     * @param string $directory
     * @param string $expected
     * @throws \ReflectionException
     */
    public function testGetComposerFilename($directory, $expected)
    {
        $versioner = new Versioner();

        $method = new \ReflectionMethod($versioner, 'getComposerFilename');
        $method->setAccessible(true);

        $result = $method->invokeArgs($versioner, [$directory]);

        self::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getGetComposerFilenameTestValues(): array
    {
        return [
            ['/foo/bar', '/foo/bar/composer.json'],
            ['/foo/bar/', '/foo/bar/composer.json']
        ];
    }

    /**
     * @dataProvider getGetExtensionConfigurationFilenameTestValues
     * @param string $directory
     * @param string $expected
     */
    public function testExtensionConfigurationFilename($directory, $expected)
    {
        $versioner = new Versioner();

        $method = new \ReflectionMethod($versioner, 'getExtensionConfigurationFilename');
        $method->setAccessible(true);

        $result = $method->invokeArgs($versioner, [$directory]);

        self::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getGetExtensionConfigurationFilenameTestValues(): array
    {
        return [
            ['/foo/bar', '/foo/bar/ext_emconf.php'],
            ['/foo/bar/', '/foo/bar/ext_emconf.php']
        ];
    }

    /**
     * @param string $filename
     * @param string|null $expectedData
     * @param bool $expectsException
     * @throws \ReflectionException
     * @dataProvider getReadComposerFileTestValues
     */
    public function testReadComposerFile($filename, $expectedData, $expectsException)
    {
        $versioner = new Versioner();

        $method = new \ReflectionMethod($versioner, 'readComposerFile');
        $method->setAccessible(true);

        if (true === $expectsException) {
            $this->expectException('RuntimeException');
        }

        $result = $method->invokeArgs($versioner, [$filename]);

        self::assertEquals($expectedData, $result);
    }

    /**
     * @return array
     */
    public function getReadComposerFileTestValues(): array
    {
        return [
            [vfsStream::url('temp/' . Versioner::FILENAME_COMPOSER), self::$fixture, false],
            [vfsStream::url('temp-does-not-exist/' . Versioner::FILENAME_COMPOSER), null, true],
        ];
    }

    /**
     * @param string $filename
     * @param $expectedData
     * @param bool $expectsException
     * @throws \ReflectionException
     * @dataProvider getReadExtensionConfigurationFileTestValues
     */
    public function testReadExtensionConfigurationFile($filename, $expectedData, $expectsException)
    {
        $versioner = new Versioner();

        $method = new \ReflectionMethod($versioner, 'readExtensionConfigurationFile');
        $method->setAccessible(true);

        if (true === $expectsException) {
            $this->expectException('RuntimeException');
        }

        $result = $method->invokeArgs($versioner, [$filename]);

        self::assertEquals($expectedData, $result);
    }

    /**
     * @return array
     */
    public function getReadExtensionConfigurationFileTestValues(): array
    {
        return [
            [vfsStream::url('temp/' . Versioner::FILENAME_EXTENSION_CONFIGURATION), self::$fixture, false],
            [vfsStream::url('temp-does-not-exist/' . Versioner::FILENAME_EXTENSION_CONFIGURATION), null, true],
        ];
    }

    /**
     * @param string $filename
     * @param string $version
     * @param bool $expectsException
     * @dataProvider getWriteComposerFileTestValues
     */
    public function testWriteComposerFile($filename, $version, $expectsException)
    {
        $expectedData = json_encode(
            array_merge(self::$fixture, ['version' => $version]),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        $versioner = new Versioner();

        $method = new \ReflectionMethod($versioner, 'writeComposerFile');
        $method->setAccessible(true);

        if (true === $expectsException) {
            $this->expectException('RuntimeException');
        }

        $method->invokeArgs($versioner, [$filename, $version]);

        self::assertStringEqualsFile($filename, $expectedData);
    }

    /**
     * @return array
     */
    public function getWriteComposerFileTestValues(): array
    {
        return [
            [vfsStream::url('temp/' . Versioner::FILENAME_COMPOSER), '1.2.3', false],
            [vfsStream::url('temp/' . Versioner::FILENAME_COMPOSER), '3.2.1', false],
            [vfsStream::url('temp-does-not-exist/' . Versioner::FILENAME_COMPOSER), '1.2.3', true],
        ];
    }

    public function testWriteComposerFileReturnsWithoutWritingFileIfFileDoesNotContainVersion()
    {
        $versioner = new Versioner();

        $method = new \ReflectionMethod($versioner, 'writeComposerFile');
        $method->setAccessible(true);

        $fixture = self::$fixture;
        unset($fixture['version']);
        $noVersionFile = Versioner::FILENAME_COMPOSER . '.noversion.json';

        $newComposerFile = new vfsStreamFile($noVersionFile);
        $newComposerFile->setContent(json_encode($fixture, JSON_UNESCAPED_SLASHES));

        vfsStreamWrapper::getRoot()->addChild($newComposerFile);
        $vfsUrl = vfsStream::url('temp/' . $noVersionFile);

        $result = $method->invokeArgs($versioner, [$vfsUrl, '1.2.3']);

        self::assertTrue($result);
        if (method_exists(self::class, 'assertStringNotContainsString')) {
            self::assertStringNotContainsString('1.2.3', file_get_contents($vfsUrl));
        } else {
            self::assertNotContains('1.2.3', file_get_contents($vfsUrl));
        }
    }

    /**
     * @param string $filename
     * @param string $version
     * @param string $stability
     * @param bool $expectsException
     * @dataProvider getWriteExtensionConfigurationFileTestValues
     */
    public function testWriteExtensionConfigurationFile($filename, $version, $stability, $expectsException)
    {
        $fixture = self::$fixture;
        $fixture['version'] = $version;
        $fixture['state'] = $stability;

        $expectedData = '<' . '?php' . PHP_EOL . '$EM_CONF[$_EXTKEY] = ' . var_export($fixture, true) . ';' . PHP_EOL;

        $versioner = new Versioner();

        $method = new \ReflectionMethod($versioner, 'writeExtensionConfigurationFile');
        $method->setAccessible(true);

        if (true === $expectsException) {
            $this->expectException('RuntimeException');
        }

        $method->invokeArgs($versioner, [$filename, $version, $stability]);

        self::assertStringEqualsFile($filename, $expectedData);
    }

    /**
     * @return array
     */
    public function getWriteExtensionConfigurationFileTestValues(): array
    {
        return [
            [
                vfsStream::url('temp/' . Versioner::FILENAME_EXTENSION_CONFIGURATION),
                '1.2.3',
                Versioner::STABILITY_STABLE,
                false
            ],
            [
                vfsStream::url('temp/' . Versioner::FILENAME_EXTENSION_CONFIGURATION),
                '3.2.1',
                Versioner::STABILITY_BETA,
                false
            ],
            [
                vfsStream::url('temp-does-not-exist/' . Versioner::FILENAME_EXTENSION_CONFIGURATION),
                '1.2.3',
                Versioner::STABILITY_STABLE,
                true
            ],
        ];
    }
}
