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
			$files = $this->findFtpFiles( $config, $config['filename'] );
			$filePath = $files[0] ?? null;

			if ( ! $filePath ) {
				return [];
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

		$filename = $config['filename'];
		if ( empty( $config['override'] ) ) {
			$filename = $this->uniqueFilename( $filename, $this->findFtpFiles( $config ) );
		}


		// Create tmp file for stream.
		$local_file=fopen('php://temp', 'r+');
		fwrite($local_file, $filecontent);
		rewind($local_file);

		// Go to directory.
		ftp_chdir($ftp_conn, $config['path']);

		if( $login ) {
			// Stream file to FTP.
			$upload_result = ftp_fput($ftp_conn, $filename, $local_file, FTP_BINARY);
		}

		if( ! $login || ! $upload_result ) {
			echo('FTP error: The file could not be written to the FTP server.');
		}

		ftp_close($ftp_conn);

		return $data;
	}

	public function uniqueFilename( $filename, $existing ): string
	{
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$file_basename = basename($filename,".".$ext);

		$x = 1;
		while($x <= 999) {
			$newFilename = $file_basename . $x . "." . $ext;

			if( ! in_array($newFilename, $existing) ) {
				$filename = $newFilename;
				break;
			}
			$x++;
		}

		return $filename;
	}

	protected function findFtpFiles( $config, $filename = null ): array
	{
		$finder = new Finder();
		$finder->files();
		$finder->in("ftp://".$config['username' ].":".$config['password' ]."@".$config['host'].$config['path']);

		if ( $filename ) {
			$finder->name( $filename );
		}

		if ( ! $finder->hasResults() ) {
			// @todo error.
			return [];
		}

		$files = [];
		if ( $finder->hasResults() ) {
			foreach ( $finder as $found ) {
				$files[] = $found->getFilename();
			}
		}

		return $files;
	}
}
