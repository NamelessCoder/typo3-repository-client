<?php
namespace NamelessCoder\TYPO3RepositoryClient\Tests\Unit;

use NamelessCoder\TYPO3RepositoryClient\ExtensionUploadPacker;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

/**
 * Class ExtensionUploadPackerTest
 */
class ExtensionUploadPackerTest extends TestCase
{
    /**
     * @var array
     */
    protected static $fixture = [
        'title' => 'Dummy title',
        'description' => 'Dummy description',
        'category' => 'misc',
        'shy' => 0,
        'version' => '1.2.3-invalid',
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
            'suggests' => [
                'news' => ''
            ]
        ],
        '_md5_values_when_last_written' => ''
    ];

    /**
     * @var string
     */
    protected static $fixtureString;

    /**
     * @var integer
     */
    protected static $mtime;

    public static function setUpBeforeClass(): void
    {
        self::$mtime = time();
        self::$fixtureString = '<' . '?php
			$EM_CONF[$_EXTKEY] = ' . var_export(self::$fixture, true) . ';
		';
        $emConf = new vfsStreamFile('ext_emconf.php');
        $emConf->setContent(self::$fixtureString);
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('temp', 0777));
        vfsStreamWrapper::getRoot()->addChild($emConf);
    }

    /**
     * @dataProvider getValidateVersionInvalidTestValues
     * @param mixed $version
     */
    public function testThrowsRuntimeExceptionOnInvalidVersionNumberInConfiguration($version)
    {
        $plugin = new ExtensionUploadPacker();

        $method = new \ReflectionMethod($plugin, 'validateVersionNumber');
        $method->setAccessible(true);

        $this->expectException('RuntimeException');

        $method->invokeArgs($plugin, [$version]);
    }

    /**
     * @return array
     */
    public function getValidateVersionInvalidTestValues()
    {
        return [
            ['foobar'],
            ['f.o.b'],
            ['-1.0.0'],
            ['1.0.0-dev'],
            ['test-tag'],
            ['accidental.dotcount.match']
        ];
    }

    /**
     * @dataProvider getValidateVersionValidTestValues
     * @param mixed $version
     */
    public function testValidateVersionNumber($version)
    {
        $plugin = new ExtensionUploadPacker();
        $method = new \ReflectionMethod($plugin, 'validateVersionNumber');
        $method->setAccessible(true);
        $result = $method->invokeArgs($plugin, [$version]);
        self::assertNull($result);
    }

    /**
     * @return array
     */
    public function getValidateVersionValidTestValues()
    {
        return [
            ['0.0.1'],
            ['1.2.3'],
            ['3.2.1']
        ];
    }

    public function testCreateSoapDataCreatesExpectedOutput()
    {
        $directory = vfsStream::url('temp');

        /** @var ExtensionUploadPacker|\PHPUnit_Framework_MockObject_MockObject $packer */
        $packer = $this->getMockBuilder(ExtensionUploadPacker::class)
            ->setMethods(['validateVersionNumber'])
            ->getMock();

        $packer->expects(self::once())->method('validateVersionNumber');

        $result = $packer->pack($directory, 'username', 'password', 'comment');

        $expected = [
            'accountData' => [
                'username' => 'username',
                'password' => 'password'
            ],
            'extensionData' => [
                'extensionKey' => 'temp',
                'version' => '1.2.3-invalid',
                'metaData' => [
                    'title' => 'Dummy title',
                    'description' => 'Dummy description',
                    'category' => 'misc',
                    'state' => 'beta',
                    'authorName' => 'Author Name',
                    'authorEmail' => 'author@domain.com',
                    'authorCompany' => '',
                ],
                'technicalData' => [
                    'dependencies' => [
                        [
                            'kind' => 'depends',
                            'extensionKey' => 'typo3',
                            'versionRange' => '6.1.0-6.2.99',
                        ],
                        [
                            'kind' => 'depends',
                            'extensionKey' => 'cms',
                            'versionRange' => '',
                        ],
                        [
                            'kind' => 'suggests',
                            'extensionKey' => 'news',
                            'versionRange' => '',
                        ]
                    ],
                    'loadOrder' => '',
                    'uploadFolder' => false,
                    'createDirs' => '',
                    'shy' => 0,
                    'modules' => '',
                    'modifyTables' => '',
                    'priority' => '',
                    'clearCacheOnLoad' => false,
                    'lockType' => '',
                    'doNotLoadInFEe' => null,
                    'docPath' => null,
                ],
                'infoData' => [
                    'codeLines' => 41,
                    'codeBytes' => 853,
                    'codingGuidelinesCompliance' => '',
                    'codingGuidelinesComplianceNotes' => '',
                    'uploadComment' => 'comment',
                    'techInfo' => 'All good, baby',
                ],
            ],
            'filesData' => [
                [
                    'name' => 'ext_emconf.php',
                    'size' => 853,
                    'modificationTime' => self::$mtime,
                    'isExecutable' => 0,
                    'content' => self::$fixtureString,
                    'contentMD5' => 'f87088992115285f0932e1f765548085',
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function testReadExtensionConfigurationFileThrowsExceptionIfFileDoesNotExist()
    {
        $directory = vfsStream::url('doesnotexist');
        $extensionKey = 'temp';
        $packer = new ExtensionUploadPacker();

        $method = new \ReflectionMethod($packer, 'readExtensionConfigurationFile');
        $method->setAccessible(true);

        $this->expectException('RuntimeException');

        $method->invoke($packer, $directory, $extensionKey);
    }

    public function testReadExtensionConfigurationFileThrowsExceptionIfVersionInFileIsInvalid()
    {
        $directory = vfsStream::url('ext_emconf.php');
        $extensionKey = 'temp';
        $packer = new ExtensionUploadPacker();

        $method = new \ReflectionMethod($packer, 'readExtensionConfigurationFile');
        $method->setAccessible(true);

        $this->expectException('RuntimeException');

        $method->invoke($packer, $directory, $extensionKey);
    }

    public function testPack()
    {
        $directory = vfsStream::url('temp');
        $extensionKey = 'temp';

        /** @var ExtensionUploadPacker|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder(ExtensionUploadPacker::class)
            ->setMethods(['createFileDataArray', 'createSoapData', 'validateVersionNumber'])
            ->getMock();

        $method = new \ReflectionMethod($mock, 'readExtensionConfigurationFile');
        $method->setAccessible(true);

        $configuration = $method->invoke($mock, $directory, $extensionKey);

        $mock->expects(self::once())->method('createFileDataArray')
            ->with($directory)
            ->will(self::returnValue(['foo' => 'bar']));

        $mock->expects(self::once())
            ->method('createSoapData')
            ->with(
                $extensionKey,
                ['foo' => 'bar', 'EM_CONF' => $configuration],
                'usernamefoo',
                'passwordfoo',
                'commentfoo'
            )
            ->will(self::returnValue('test'));
        $result = $mock->pack($directory, 'usernamefoo', 'passwordfoo', 'commentfoo');
        self::assertEquals('test', $result);
    }

    public function testCreateFileDataArray()
    {
        $directory = vfsStream::url('temp/');

        $packer = new ExtensionUploadPacker();

        $method = new \ReflectionMethod($packer, 'createFileDataArray');
        $method->setAccessible(true);

        $result = $method->invoke($packer, $directory);

        self::assertEquals(
            [
                'extKey' => 'temp',
                'misc' => [
                    'codelines' => 41,
                    'codebytes' => 853
                ],
                'techInfo' => 'All good, baby',
                'FILES' => [
                    'ext_emconf.php' => [
                        'name' => 'ext_emconf.php',
                        'size' => 853,
                        'mtime' => self::$mtime,
                        'is_executable' => false,
                        'content' => self::$fixtureString,
                        'content_md5' => 'f87088992115285f0932e1f765548085',
                        'codelines' => 41
                    ]
                ]
            ],
            $result
        );
    }

    /**
     * @dataProvider getSettingsAndValues
     * @param array $settings
     * @param string $settingName
     * @param mixed $defaultValue
     * @param mixed $expectedValue
     */
    public function testGetValueOrDefault($settings, $settingName, $defaultValue, $expectedValue)
    {
        $packer = new ExtensionUploadPacker();

        $method = new \ReflectionMethod($packer, 'valueOrDefault');
        $method->setAccessible(true);

        $result = $method->invoke($packer, ['EM_CONF' => $settings], $settingName, $defaultValue);

        self::assertEquals($expectedValue, $result);
    }

    /**
     * @return array
     */
    public function getSettingsAndValues(): array
    {
        return [
            [['foo' => 'bar'], 'foo', 'baz', 'bar'],
            [['foo' => 'bar'], 'foo2', 'baz', 'baz'],
        ];
    }

    /**
     * @dataProvider getExtensionDataAndExpectedDependencyOutput
     * @param string $kindOfDependency
     * @param array $extensionData
     * @param array $expectedOutout
     * @param string $expectedException
     */
    public function testCreateDependenciesArray($kindOfDependency, $extensionData, $expectedOutout, $expectedException)
    {
        $uploader = new ExtensionUploadPacker();

        $method = new \ReflectionMethod($uploader, 'createDependenciesArray');
        $method->setAccessible(true);

        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $output = $method->invoke($uploader, $extensionData, $kindOfDependency);

        self::assertEquals($expectedOutout, $output);
    }

    /**
     * @return array
     */
    public function getExtensionDataAndExpectedDependencyOutput(): array
    {
        return [
            // correct usage and input:
            [
                ExtensionUploadPacker::KIND_DEPENDENCY,
                [
                    'EM_CONF' => [
                        'constraints' => [
                            ExtensionUploadPacker::KIND_DEPENDENCY => [
                                'foobar' => '0.0.0-1.0.0',
                                'foobar2' => '1.0.0-2.0.0',
                            ]
                        ]
                    ]
                ],
                [
                    ['kind' => 'depends', 'extensionKey' => 'foobar', 'versionRange' => '0.0.0-1.0.0'],
                    ['kind' => 'depends', 'extensionKey' => 'foobar2', 'versionRange' => '1.0.0-2.0.0'],
                ],
                null
            ],
            // no deps: empty output, no error
            [
                ExtensionUploadPacker::KIND_DEPENDENCY,
                ['EM_CONF' => []],
                [],
                null
            ],
            // deps setting not an array, empty output, no error
            [
                ExtensionUploadPacker::KIND_DEPENDENCY,
                ['EM_CONF' => ['constraints' => [ExtensionUploadPacker::KIND_DEPENDENCY => 'iamastring']]],
                [],
                null
            ],
            // deps numerically indexed - error!
            [
                ExtensionUploadPacker::KIND_DEPENDENCY,
                [
                    'EM_CONF' => [
                        'constraints' => [
                            ExtensionUploadPacker::KIND_DEPENDENCY => [0 => ['0.0.0-1.0.0']]
                        ]
                    ]
                ],
                [],
                'RuntimeException'
            ],
        ];
    }

    /**
     * @dataProvider getIsFilePermittedTestValues
     * @param \SplFileInfo $file
     * @param string $inPath
     * @param boolean $expectedPermitted
     */
    public function testIsFilePermitted(\SplFileInfo $file, $inPath, $expectedPermitted)
    {
        $instance = new ExtensionUploadPacker();

        $method = new \ReflectionMethod($instance, 'isFilePermitted');
        $method->setAccessible(true);

        $result = $method->invokeArgs($instance, [$file, $inPath]);

        self::assertEquals($expectedPermitted, $result);
    }

    /**
     * @return array
     */
    public function getIsFilePermittedTestValues(): array
    {
        return [
            [new \SplFileInfo('/path/file'), '/path', true],
            [new \SplFileInfo('/path/.file'), '/path', false],
            [new \SplFileInfo('/path/.htaccess'), '/path', true],
            [new \SplFileInfo('/path/.htpasswd'), '/path', true],
            [new \SplFileInfo('/.git/file'), '/.git', true],
            [new \SplFileInfo('/.git/.dotfile'), '/.git', false],
            [new \SplFileInfo('/.git/.htaccess'), '/.git', true],
            [new \SplFileInfo('/.git/.htpasswd'), '/.git', true],
            [new \SplFileInfo('/path/.git/file'), '/path', false],
        ];
    }
}
