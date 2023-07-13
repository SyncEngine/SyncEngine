<?php

namespace App\Task;

use App\Component\ExecutionContext;
use App\Model\TaskModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Converter extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'modifier';
		$this->name        = 'Converter';
		$this->description = 'Convert files to usable data';

		parent::__construct();
	}

	function getFields(): array
	{
		return [
			'from' => [
				'type'    => 'select',
				'choices' => [
					"csv"  => "csv",
					"xml"  => "xml",
					"json" => "json",
				],
			],
			'to'   => [
				'type'    => 'select',
				'choices' => [
					"csv"  => "csv",
					"xml"  => "xml",
					"json" => "json",
				],
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		//@TODO this should be available as endstep to set correct response headers
		switch ( $config['from'] ) {
			case "csv":
				$results   = [];
				$csvToRead = fopen( $config['filename'], 'r' );
				while ( ! feof( $csvToRead ) ) {
					$results[] = fgetcsv( $csvToRead, 1000, ',' );
					// TODO: Select delimiter.
				}
				fclose( $csvToRead );
			break;
			case "json":
				$results = $data;
			break;
			case "xml":
				$results = $this->simplexml_load_string( $data );
			break;
		}

		switch ( $config['to'] ) {
			case "xml":
				$results = $this->toXML( $data );
			break;
		}

		return $results;
	}

	public function toXML( $data, int $status = 200, array $headers = [] ): Response
	{
		$encoders    = [ new XmlEncoder(), new JsonEncoder() ];
		$normalizers = [ new ObjectNormalizer() ];
		$serializer  = new Serializer( $normalizers, $encoders );
		//$serializer = new SerializerInterface();
		var_dump( $serializer->serialize( $data, XmlEncoder::FORMAT, [
				XmlEncoder::ENCODING => 'UTF-8',
			] ) );
		die();
	}
}
