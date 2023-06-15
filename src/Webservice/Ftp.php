<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use Symfony\Component\Finder\Finder;

class Ftp extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type = 'ftp';
		$this->name = 'FTP';
		$this->description = 'Connect to an FTP server to upload and/or download files';
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type' => 'text',
			],
			'username' => [
				'label' => 'Username',
				'type' => 'text',
			],
			'password' => [
				'label' => 'Password',
				'type' => 'password',
			],
			'port' => [
				'label' => 'Port',
				'type' => 'text',
			],
		];
	}

	public function getFields(): array
	{
		$fields = [
			'filename' => [
				'label' => 'Filename',
				'type' => 'text',
			],
			'path' => [
				'label' => 'Path',
				'type' => 'text',
			],
			'override' => [
				'label' => 'Overwrite if file exists',
				'type' => 'boolean',
				'conditional' => [] //@ToDo task is sender
			],
		];

		return array_merge( $this->getFormatFields(), $fields );
	}

	public function getRequestUrl( array $config ): string {
		// TODO: Implement getRequestUrl() method.
	}

	public function retrieve( array $config )
	{
		try {
			$finder = new Finder();
			$finder->in("ftp://".$config['username' ].":".$config['password' ]."@".$config['host' ].$config['path']);
			$finder->name($config['filename']);

			if (!$finder->hasResults()) {
				var_dump("File not found");
				// @todo error.
			}

			$filePath = "";
			foreach ($finder as $found) {
				$filePath = $found->getPathname();
			}
			$handle = fopen($filePath, "r");
			$content = fread($handle, filesize($filePath));
			fclose($handle);

			return $this->fromFormat( $config['format'], $content );


		} catch ( TransportExceptionInterface $e ) {
			// @todo error.
		}
	}

	public function send( array $config, $data )
	{
		$ftp_conn = ftp_connect($config['host' ]) or die("Could not connect to ".$config['host' ]);
		$login = ftp_login($ftp_conn, $config['username' ], $config['password' ]);

		$filecontent = $this->toFormat( $config['format'], $data );


		if(empty($config['override']))
		{
			$finder = new Finder();
			$finder->in("ftp://".$config['username' ].":".$config['password' ]."@".$config['host' ].$config['path']);

			$existing_files = [];
			if ($finder->hasResults()) {
				foreach ($finder as $found) {
					array_push($existing_files, $found->getFilename());
				}
			}

			$ext = pathinfo($config['filename'], PATHINFO_EXTENSION);
			$file_basename = basename($config['filename'],".".$ext);

			$x = 1;
			while($x <= 999) {
				if(!in_array($file_basename.$x.".".$ext, $existing_files))
				{
					$filename = $file_basename.$x.".".$ext;
					break;
				}
				$x++;
			}
		}else{
			$filename = $config['filename'];
		}



		$local_file=fopen('php://temp', 'r+');
		fwrite($local_file, $filecontent);
		rewind($local_file);

		ftp_chdir($ftp_conn, $config['path']);
		if($login) $upload_result=ftp_fput($ftp_conn, $filename, $local_file, FTP_BINARY);

		if(!$login or !$upload_result)
		{
			echo('FTP error: The file could not be written to the FTP server.');
		}

		ftp_close($ftp_conn);
	}
}
