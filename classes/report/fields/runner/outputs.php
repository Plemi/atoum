<?php

namespace mageekguy\atoum\report\fields\runner;

use \mageekguy\atoum;
use \mageekguy\atoum\report;

class outputs extends report\fields\runner
{
	const titlePrompt = '> ';
	const methodPrompt = '=> ';

	protected $runner = null;

	public function getRunner()
	{
		return $this->runner;
	}

	public function setWithRunner(atoum\runner $runner, $event = null)
	{
		if ($this->runner !== $runner)
		{
			$this->runner = $runner;
		}

		return $this;
	}

	public function toString()
	{
		$string = '';

		if ($this->runner !== null)
		{
			$outputs = $this->runner->getScore()->getOutputs();

			$sizeOfOutputs = sizeof($outputs);

			if ($sizeOfOutputs > 0)
			{
				$string .= self::titlePrompt . sprintf($this->locale->__('There is %d output:', 'There are %d outputs:', $sizeOfOutputs), $sizeOfOutputs) . PHP_EOL;

				foreach ($outputs as $output)
				{
					$string .= self::methodPrompt . $output['class'] . '::' . $output['method'] . '():' . PHP_EOL;

					foreach (explode(PHP_EOL, rtrim($output['value'])) as $line)
					{
						$string .= $line . PHP_EOL;
					}
				}
			}
		}

		return $string;
	}
}

?>