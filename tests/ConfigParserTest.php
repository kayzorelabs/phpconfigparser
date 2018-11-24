<?php

namespace KayzoreLabs\ToolKit\ConfigParser\Tests;

use InvalidArgumentException;
use KayzoreLabs\ToolKit\ConfigParser\ConfigParser;
use KayzoreLabs\ToolKit\ConfigParser\NoSectionsConfigParser;
use PHPUnit\Framework\TestCase;

class ConfigParserTest extends TestCase
{
    protected $filenames;
    protected $fixturesDir;
    protected $outputFilename;
    /**
     * @var ConfigParser
     */
    protected $cfg;
    /**
     * @var NoSectionsConfigParser
     */
    protected $cfgNoSct;

    protected function getFilename()
    {
        return $this->filenames[0];
    }

    protected function setUp()
    {
        $this->cfg = new ConfigParser();
        $this->cfgNoSct = new NoSectionsConfigParser();
        $this->fixturesDir = __DIR__.'/fixtures';
        $this->filenames = array($this->fixturesDir.'/source.cfg');
        $this->outputFilename = tempnam(sys_get_temp_dir(), str_replace('\\', '_',__CLASS__).'_');
    }

    protected function tearDown()
    {
        file_exists($this->outputFilename) && unlink($this->outputFilename);
    }

    /**
     * @expectedException \KayzoreLabs\ToolKit\ConfigParser\Exception\DuplicateSectionException
     */
    public function testAddDuplicateSection()
    {
        $section = 'github.com';

        $this->cfg->read($this->getFilename());

        $this->cfg->addSection($section);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddNonStringSection()
    {
        $section = array();

        $this->cfg->read($this->getFilename());

        $this->cfg->addSection($section);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddDefaultSection()
    {
        $section = 'DeFaulT';

        $this->cfg->read($this->getFilename());
        $this->cfg->addSection($section);
    }

    public function testHasSection()
    {
        $this->cfg->read($this->getFilename());

        $this->assertFalse($this->cfg->hasSection('non-existing-section'));
        $this->assertFalse($this->cfg->hasSection('default'));
        $this->assertTrue($this->cfg->hasSection('github.com'));
    }

    public function testHasOption()
    {
        $this->cfg->read($this->getFilename());

        $this->assertTrue($this->cfg->hasOption('github.com', 'User'));
        $this->assertFalse($this->cfg->hasOption('non-existing-section', 'User'));
        $this->assertFalse($this->cfg->hasOption('github.com', 'non-existing-option'));
        $this->assertTrue($this->cfg->hasOption(null, 'ForwardX11'));
        $this->assertTrue($this->cfg->hasOption('', 'ForwardX11'));
        $this->assertFalse($this->cfg->hasOption('', 'User'));
    }

    public function testSupportedIniFileStructure()
    {
        $this->cfg->read($this->fixturesDir.'/supported_ini_file_structure.cfg');

        $section = 'Simple Values';
        $this->assertTrue($this->cfg->hasSection($section));
        $this->assertEquals($this->cfg->get($section, 'key'), 'value');
        $this->assertEquals($this->cfg->get($section, 'spaces in keys'), 'allowed');
        $this->assertEquals($this->cfg->get($section, 'spaces in values'), 'allowed as well');
        $this->assertEquals($this->cfg->get($section, 'spaces around the delimiter'), 'obviously');
        $this->assertEquals($this->cfg->get($section, 'you can also use'), 'to delimit keys from values');

        $section = 'All Values Are Strings';
        $this->assertTrue($this->cfg->hasSection($section));
        $this->assertEquals($this->cfg->get($section, 'values like this'), '1000000');
        $this->assertEquals($this->cfg->get($section, 'or this'), '3.14159265359');
        $this->assertEquals($this->cfg->get($section, 'are they treated as numbers?'), 'no');
        $this->assertEquals($this->cfg->get($section, 'integers, floats and booleans are held as'), 'strings');
        $this->assertEquals($this->cfg->get($section, 'can use the API to get converted values directly'), 'true');

        $section = 'Multiline Values';
        $this->assertTrue($this->cfg->hasSection($section));
        $this->assertEquals($this->cfg->get($section, 'chorus'), "I'm a lumberjack, and I'm okay");

        $section = 'No Values';
        $this->assertTrue($this->cfg->hasSection($section));
        $this->assertFalse($this->cfg->hasOption($section, 'key_without_value'));
        $this->assertEquals($this->cfg->get($section, 'empty string value here'), '');

        $section = 'You can use comments';
        $this->assertTrue($this->cfg->hasSection($section));

        $section = 'html in value';
        $this->assertEquals($this->cfg->get($section, 'subtitle'), 'test &amp');

        $this->assertTrue($this->cfg->save());
    }

    public function testSupportedIniFileWithoutSection()
    {
        $this->cfgNoSct->read($this->fixturesDir.'/no_section_ini_file.cfg');
        $this->assertEquals($this->cfgNoSct->get('key'), 'value');
        $this->assertEquals($this->cfgNoSct->get('spaces in keys'), 'allowed');
        $this->assertEquals($this->cfgNoSct->get('spaces in values'), 'allowed as well');
        $this->assertEquals($this->cfgNoSct->get('spaces around the delimiter'), 'obviously');
        $this->assertEquals($this->cfgNoSct->get('you can also use'), 'to delimit keys from values');
        $this->assertEquals($this->cfgNoSct->get('values like this'), '1000000');
        $this->assertEquals($this->cfgNoSct->get('or this'), '3.14159265359');
        $this->assertEquals($this->cfgNoSct->get('are they treated as numbers?'), 'no');
        $this->assertEquals($this->cfgNoSct->get('integers, floats and booleans are held as'), 'strings');
        $this->assertEquals($this->cfgNoSct->get('can use the API to get converted values directly'), 'true');
        $this->assertEquals($this->cfgNoSct->get('chorus'), "I'm a lumberjack, and I'm okay");
        $this->assertFalse($this->cfgNoSct->hasOption('key_without_value'));
        $this->assertEquals($this->cfgNoSct->get('empty string value here'), '');
        $this->assertEquals($this->cfgNoSct->get('subtitle'), 'test &amp');

        $this->assertTrue($this->cfgNoSct->save());
    }
}
