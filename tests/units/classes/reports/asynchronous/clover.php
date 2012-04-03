<?php

namespace mageekguy\atoum\tests\units\reports\asynchronous;

use
	mageekguy\atoum,
	mageekguy\atoum\report,
	mageekguy\atoum\score,
	ageekguy\atoum\asserter\exception,
	mageekguy\atoum\reports\asynchronous as reports
;

require_once __DIR__ . '/../../../runner.php';

class clover extends atoum\test
{
	public function testClass()
	{
		$this
			->testedClass->isSubclassOf('mageekguy\atoum\reports\asynchronous')
		;
	}

	public function testClassConstants()
	{
		$this
			->string(reports\clover::defaultTitle)->isEqualTo('atoum code coverage')
			->string(reports\clover::defaultPackage)->isEqualTo('atoumCodeCoverage')
			->string(reports\clover::lineTypeMethod)->isEqualTo('method')
			->string(reports\clover::lineTypeStatement)->isEqualTo('stmt')
			->string(reports\clover::lineTypeConditional)->isEqualTo('cond')
		;
	}

	public function test__construct()
	{
		$report = new reports\clover();

		$this
			->array($report->getFields(atoum\runner::runStart))->isEmpty()
			->object($report->getAdapter())->isInstanceOf('mageekguy\atoum\adapter')
		;

		$adapter = new atoum\test\adapter();
		$adapter->extension_loaded = true;

		$this
			->if($report = new reports\clover($adapter))
			->then
				->array($report->getFields())->isEmpty()
				->object($report->getAdapter())->isIdenticalTo($adapter)
				->adapter($adapter)->call('extension_loaded')->withArguments('libxml')->once()
		;

		$adapter->extension_loaded = false;

		$this
			->exception(function() use ($adapter) {
					$report = new reports\clover($adapter);
				}
			)
			->isInstanceOf('mageekguy\atoum\exceptions\runtime')
			->hasMessage('libxml PHP extension is mandatory for clover report')
		;
	}

	public function testSetAdapter()
	{
		$report = new reports\clover();

		$this
			->object($report->setAdapter($adapter = new atoum\adapter()))->isIdenticalTo($report)
			->object($report->getAdapter())->isIdenticalTo($adapter)
		;
	}

	public function testHandleEvent()
	{
		$this
			->if($report = new reports\clover())
			->then
				->variable($report->getTitle())->isEqualTo('atoum code coverage')
				->variable($report->getPackage())->isEqualTo('atoumCodeCoverage')
				->castToString($report)->isEmpty()
				->string($report->handleEvent(atoum\runner::runStop, new atoum\runner())->getTitle())->isEqualTo(reports\clover::defaultTitle)
				->castToString($report)->isNotEmpty()
		;

		$report = new reports\clover();

		$this
			->string($report->setTitle($title = uniqid())->handleEvent(atoum\runner::runStop, new atoum\runner())->getTitle())
			->isEqualTo($title);

		$report = new reports\clover();

		$writer = new \mock\mageekguy\atoum\writers\file();
		$writer->getMockController()->write = function($something) use ($writer) { return $writer; };

		$this
			->when(function() use ($report, $writer) { $report->addWriter($writer)->handleEvent(atoum\runner::runStop, new \mageekguy\atoum\runner()); })
				->mock($writer)->call('writeAsynchronousReport')->withArguments($report)->once()
		;
	}

	public function testSetTitle()
	{
		$report = new reports\clover();

		$this
			->object($report->setTitle($title = uniqid()))->isIdenticalTo($report)
			->string($report->getTitle())->isEqualTo($title)
		;
	}

	public function testSetPackage()
	{
		$report = new reports\clover();

		$this
			->object($report->setPackage($package = uniqid()))->isIdenticalTo($report)
			->string($report->getPackage())->isEqualTo($package)
		;
	}

	public function testBuild()
	{
		$adapter = new atoum\test\adapter();
		$adapter->time = $generated = time();
		$adapter->uniqid = $clover = uniqid();

		$observable = new \mock\mageekguy\atoum\runner();
		$observable->getScore = new score\coverage();

		$report = new reports\clover($adapter);

		$this
			->if($report->handleEvent(atoum\runner::runStop, $observable))
			->then
				->castToString($report)->isEqualTo(<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="$generated" clover="$clover">
  <project timestamp="$generated" name="atoum code coverage">
	<package name="atoumCodeCoverage">
	  <metrics complexity="0" elements="0" coveredelements="0" conditionals="0" coveredconditionals="0" statements="0" coveredstatements="0" methods="0" coveredmethods="0" testduration="0" testfailures="0" testpasses="0" testruns="0" classes="1" loc="0" ncloc="0" files="0"/>
	</package>
	<metrics complexity="0" elements="0" coveredelements="0" conditionals="0" coveredconditionals="0" statements="0" coveredstatements="0" methods="0" coveredmethods="0" testduration="0" testfailures="0" testpasses="0" testruns="0" classes="1" loc="0" ncloc="0" files="0" packages="1"/>
  </project>
</coverage>

XML
				)
		;
	}

	public function testMakeRootElement()
	{
		$adapter = new atoum\test\adapter();
		$adapter->time = $generated = time();
		$adapter->uniqid = $clover = uniqid();

		$report = new reports\clover($adapter);

		$this
			->object($element = $report->makeRootElement(new \DOMDocument(), new score\coverage()))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('coverage')
			->string($element->getAttribute('generated'))->isEqualTo($generated)
			->string($element->getAttribute('clover'))->isEqualTo($clover)
		;
	}

	public function testMakeProjectElement()
	{
		$adapter = new atoum\test\adapter();
		$adapter->time = $timestamp = time();

		$report = new \mock\mageekguy\atoum\reports\asynchronous\clover();
		$report->setAdapter($adapter);

		$this
			->object($element = $report->makeProjectElement($document = new \DOMDocument(), $score = new score\coverage()))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('project')
			->string($element->getAttribute('timestamp'))->isEqualTo($timestamp)
			->string($element->getAttribute('name'))->isEqualTo('atoum code coverage')
			->mock($report)
				->call('getTitle')->once()
				->call('makeProjectMetricsElement')->once()->withArguments($document, 0)
				->call('makePackageElement')->once()->withArguments($document, $score)
		;
	}

	public function testMakeProjectMetricsElement()
	{
		$report = new \mock\mageekguy\atoum\reports\asynchronous\clover();

		$this
			->object($element = $report->makeProjectMetricsElement($document = new \DOMDocument(), $files = rand(0, 10)))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('metrics')
			->string($element->getAttribute('packages'))->isEqualTo('1')
			->mock($report)
				->call('makePackageMetricsElement')->once()->withArguments($document, $files)
		;
	}

	public function testMakePackageElement()
	{
		$report = new \mock\mageekguy\atoum\reports\asynchronous\clover();

		$this
			->object($element = $report->makePackageElement($document = new \DOMDocument(), $score = new score\coverage()))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('package')
			->string($element->getAttribute('name'))->isEqualTo('atoumCodeCoverage')
			->mock($report)
				->call('getPackage')->once()
				->call('makePackageMetricsElement')->once()->withArguments($document, 0)
				->call('makeFileElement')->never();
		;
	}

	public function testMakePackageMetricsElement()
	{
		$report = new \mock\mageekguy\atoum\reports\asynchronous\clover();

		$this
			->object($element = $report->makePackageMetricsElement($document = new \DOMDocument(), $files = rand(0, 10)))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('metrics')
			->string($element->getAttribute('files'))->isEqualTo($files)
			->mock($report)
				->call('makeFileMetricsElement')->once()->withArguments($document, 0, 0, 0, 0, 1)
		;
	}

	public function testMakeFileElement()
	{
		$report = new \mock\mageekguy\atoum\reports\asynchronous\clover();

		$this
			->object($element = $report->makeFileElement($document = new \DOMDocument(), $filename = '/foo/bar.php', $class = 'bar', array()))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('file')
			->string($element->getAttribute('name'))->isEqualTo('bar.php')
			->string($element->getAttribute('path'))->isEqualTo($filename)
			->mock($report)
				->call('makeLineElement')->never()
				->call('makeClassElement')->once()->withArguments($document, $class, array())
				->call('makeFileMetricsElement')->once()->withArguments($document, 0, 0, 0, 0, 0)
		;
	}

	public function testMakeFileMetricsElement()
	{
		$report = new \mock\mageekguy\atoum\reports\asynchronous\clover();

		$this
			->object($element = $report->makeFileMetricsElement($document = new \DOMDocument(), $loc = rand(0, 10), $cloc = rand(0, 10), $methods = rand(0, 10), $cmethods = rand(0, 10), $classes = rand(0, 10)))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('metrics')
			->string($element->getAttribute('classes'))->isEqualTo($classes)
			->string($element->getAttribute('loc'))->isEqualTo($loc)
			->string($element->getAttribute('ncloc'))->isEqualTo($loc)
			->mock($report)
				->call('makeClassMetricsElement')->once()->withArguments($document, $loc, $cloc, $methods, $cmethods)
		;
	}

	public function testMakeClassElement()
	{
		$report = new \mock\mageekguy\atoum\reports\asynchronous\clover();

		$this
			->object($element = $report->makeClassElement($document = new \DOMDocument(), $class = uniqid(), array()))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('class')
			->string($element->getAttribute('name'))->isEqualTo($class)
			->mock($report)
				->call('makeClassMetricsElement')->once()->withArguments($document, 0, 0, 0, 0, 0)
		;
	}

	public function testMakeClassMetricsElement()
	{
		$report = new reports\clover();

		$this
			->object($element = $report->makeClassMetricsElement($document = new \DOMDocument(), $loc = rand(0, 10), $cloc = rand(0, 10), $methods = rand(0, 10), $cmethods = rand(0, 10)))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('metrics')
			->string($element->getAttribute('complexity'))->isEqualTo(0)
			->string($element->getAttribute('elements'))->isEqualTo($loc)
			->string($element->getAttribute('coveredelements'))->isEqualTo($cloc)
			->string($element->getAttribute('conditionals'))->isEqualTo(0)
			->string($element->getAttribute('coveredconditionals'))->isEqualTo(0)
			->string($element->getAttribute('statements'))->isEqualTo($loc)
			->string($element->getAttribute('coveredstatements'))->isEqualTo($cloc)
			->string($element->getAttribute('methods'))->isEqualTo($methods)
			->string($element->getAttribute('coveredmethods'))->isEqualTo($cmethods)
			->string($element->getAttribute('testduration'))->isEqualTo(0)
			->string($element->getAttribute('testfailures'))->isEqualTo(0)
			->string($element->getAttribute('testpasses'))->isEqualTo(0)
			->string($element->getAttribute('testruns'))->isEqualTo(0)
		;
	}

	public function testMakeLineElement()
	{
		$report = new reports\clover();

		$this
			->object($element = $report->makeLineElement($document = new \DOMDocument(), $linenum = rand(0, 10)))->isInstanceOf('\DOMElement')
			->string($element->tagName)->isEqualTo('line')
			->string($element->getAttribute('num'))->isEqualTo($linenum)
		;
	}
}

?>
