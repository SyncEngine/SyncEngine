<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Output\NullOutput;
define('STDIN',fopen("php://stdin","r"));

class MessengerManager
{
	public function __construct(private KernelInterface $kernel) {}
	public function callCommand()
	{
		$application = new Application($this->kernel);
		$application->setAutoExit(false);

		//@todo correct start method to run async
		$input = new ArrayInput([
			'command' => 'messenger:consume',
			//'command' => 'messenger:stop-workers',
			'--time-limit' => 3600,
		]);


		$application->run($input, new NullOutput());
		return "started";
	}
}