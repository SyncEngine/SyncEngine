<?php

namespace App\Controller\Task;

class Converter extends \App\Controller\TaskController
{
	public $type = 'converter';
	public $name = 'Converter';
	public $description = 'Convert files to usable data';

    function getFields(): array
    {
		return [
			'from' => [
				'type' => 'select',
				'choices' =>
				[
					"csv" => "csv",
					"xml" => "xml",
					"json" => "json"
				],
			],
			'to' => [
				'type' => 'select',
				'choices' =>
					[
						"csv" => "csv",
						"xml" => "xml",
						"json" => "json"
					],
			],
		];
    }

    function execute(array $config, array $data): array
    {
		$results = [];
		switch ($config['from']){
			case "csv":
				$csvToRead = fopen($config['filename'], 'r');
				while (! feof($csvToRead)) {
					$results[] = fgetcsv($csvToRead, 1000, ',');
					// TODO: Select delimiter.
				}
				fclose($csvToRead);
				break;
		}
		return $results;


    }
}
