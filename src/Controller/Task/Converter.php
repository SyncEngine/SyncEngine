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

		switch ($config['from']){
			case "csv":
				$results = [];
				$csvToRead = fopen($config['filename'], 'r');
				while (! feof($csvToRead)) {
					$results[] = fgetcsv($csvToRead, 1000, ',');
					// TODO: Select delimiter.
				}
				fclose($csvToRead);
				break;
			case "json":
				$results =  $data;
				break;
			case "xml":
				$results =  simplexml_load_string($data);
				break;
		}
		return $results;


    }
}
