<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VarTrumper\Tests\Caster;

use VarTrumper\Caster\ExceptionCaster;
use VarTrumper\Caster\FrameStub;
use VarTrumper\Cloner\VarCloner;
use VarTrumper\Dumper\HtmlDumper;
use VarTrumper\Test\VarDumperTestTrait;

class ExceptionCasterTest extends \PHPUnit_Framework_TestCase
{
    use VarDumperTestTrait;

    private function getTestException($msg, &$ref = null)
    {
        return new \Exception(''.$msg);
    }

    protected function tearDown()
    {
        ExceptionCaster::$srcContext = 1;
        ExceptionCaster::$traceArgs = true;
    }

    public function testDefaultSettings()
    {
        $ref = array('foo');
        $e = $this->getTestException('foo', $ref);

        $expectedDump = <<<'EODUMP'
Exception {
  #message: "foo"
  #code: 0
  #file: "%sExceptionCasterTest.php"
  #line: 26
  -trace: {
    %sExceptionCasterTest.php:26: {
      : {
      :     return new \Exception(''.$msg);
      : }
    }
    %sExceptionCasterTest.php:%d: {
      : $ref = array('foo');
      : $e = $this->getTestException('foo', $ref);
      : 
      arguments: {
        $msg: "foo"
        &$ref: array:1 [ …1]
      }
    }
%A
EODUMP;

        $this->assertDumpMatchesFormat($expectedDump, $e);
        $this->assertSame(array('foo'), $ref);
    }

    public function testSeek()
    {
        $e = $this->getTestException(2);

        $expectedDump = <<<'EODUMP'
{
  %sExceptionCasterTest.php:26: {
    : {
    :     return new \Exception(''.$msg);
    : }
  }
  %sExceptionCasterTest.php:%d: {
    : {
    :     $e = $this->getTestException(2);
    : 
    arguments: {
      $msg: 2
    }
  }
%A
EODUMP;

        $this->assertStringMatchesFormat($expectedDump, $this->getDump($e, 'trace'));
    }

    public function testNoArgs()
    {
        $e = $this->getTestException(1);
        ExceptionCaster::$traceArgs = false;

        $expectedDump = <<<'EODUMP'
Exception {
  #message: "1"
  #code: 0
  #file: "%sExceptionCasterTest.php"
  #line: 26
  -trace: {
    %sExceptionCasterTest.php:26: {
      : {
      :     return new \Exception(''.$msg);
      : }
    }
    %sExceptionCasterTest.php:%d: {
      : {
      :     $e = $this->getTestException(1);
      :     ExceptionCaster::$traceArgs = false;
    }
%A
EODUMP;

        $this->assertDumpMatchesFormat($expectedDump, $e);
    }

    public function testNoSrcContext()
    {
        $e = $this->getTestException(1);
        ExceptionCaster::$srcContext = -1;

        $expectedDump = <<<'EODUMP'
Exception {
  #message: "1"
  #code: 0
  #file: "%sExceptionCasterTest.php"
  #line: 26
  -trace: {
    %sExceptionCasterTest.php: 26
    %sExceptionCasterTest.php: %d
%A
EODUMP;

        $this->assertDumpMatchesFormat($expectedDump, $e);
    }

    public function testHtmlDump()
    {
        $e = $this->getTestException(1);
        ExceptionCaster::$srcContext = -1;

        $cloner = new VarCloner();
        $cloner->setMaxItems(1);
        $dumper = new HtmlDumper();
        $dumper->setDumpHeader('<foo></foo>');
        $dumper->setDumpBoundaries('<bar>', '</bar>');
        $dump = $dumper->dump($cloner->cloneVar($e)->withRefHandles(false), true);

        $expectedDump = <<<'EODUMP'
<foo></foo><bar><span class=sf-dump-note>Exception</span> {<samp>
  #<span class=sf-dump-protected title="Protected property">message</span>: "<span class=sf-dump-str>1</span>"
  #<span class=sf-dump-protected title="Protected property">code</span>: <span class=sf-dump-num>0</span>
  #<span class=sf-dump-protected title="Protected property">file</span>: "<span class=sf-dump-str title="%sExceptionCasterTest.php
%d characters"><span class=sf-dump-ellipsis>%sTests</span>%eCaster%eExceptionCasterTest.php</span>"
  #<span class=sf-dump-protected title="Protected property">line</span>: <span class=sf-dump-num>26</span>
  -<span class=sf-dump-private title="Private property defined in class:&#10;`Exception`">trace</span>: {<samp>
    <span class=sf-dump-meta title="%sExceptionCasterTest.php
Stack level %d."><span class=sf-dump-ellipsis>%sVarDumper%eTests</span>%eCaster%eExceptionCasterTest.php</span>: <span class=sf-dump-num>26</span>
     &hellip;12
  </samp>}
</samp>}
</bar>
EODUMP;

        $this->assertStringMatchesFormat($expectedDump, $dump);
    }

    /**
     * @requires function Twig_Template::getSourceContext
     */
    public function testFrameWithTwig()
    {
        require_once dirname(__DIR__).'/Fixtures/Twig.php';

        $f = array(
            new FrameStub(array(
                'file' => dirname(__DIR__).'/Fixtures/Twig.php',
                'line' => 21,
                'class' => '__TwigTemplate_VarDumperFixture_u75a09',
            )),
            new FrameStub(array(
                'file' => dirname(__DIR__).'/Fixtures/Twig.php',
                'line' => 21,
                'class' => '__TwigTemplate_VarDumperFixture_u75a09',
                'object' => new \__TwigTemplate_VarDumperFixture_u75a09(null, false),
            )),
        );

        $expectedDump = <<<'EODUMP'
array:2 [
  0 => {
    class: "__TwigTemplate_VarDumperFixture_u75a09"
    src: {
      bar.twig:2: {
        : foo bar
        :   twig source
        : 
      }
    }
  }
  1 => {
    class: "__TwigTemplate_VarDumperFixture_u75a09"
    object: __TwigTemplate_VarDumperFixture_u75a09 {
    %A
    }
    src: {
      foo.twig:2: {
        : foo bar
        :   twig source
        : 
      }
    }
  }
]

EODUMP;

        $this->assertDumpMatchesFormat($expectedDump, $f);
    }
}