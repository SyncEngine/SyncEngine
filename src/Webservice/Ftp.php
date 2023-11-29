<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use Symfony\Component\Finder\Finder;

class Ftp extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'ftp';
		$this->name        = 'FTP';
		$this->description = 'Connect to an FTP server to upload and/or download files';
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => 'Host',
				'type'  => 'text',
			],
			'username' => [
				'label' => 'Username',
				'type'  => 'text',
			],
			'password' => [
				'label' => 'Password',
				'type'  => 'password',
			],
			'port'     => [
				'label'   => 'Port',
				'type'    => 'number',
				'default' => 21,
			],
			'passive' => [
				'label' => 'Passive',
				'type'  => 'checkbox',
			],
		];
	}

	public function getFields( $defaults = [] ): array
	{
		return $this->getRequestFields( $defaults );
	}

	public function getRequestFields( $defaults = [] ): array
	{
		return [
			'path'     => [
				'label' => 'Path',
				'type'  => 'text',
			],
			'get'      => [
				'label'   => 'Get method',
				'type'    => 'select',
				'choices' => [
					'file' => 'File contents',
					'dir'  => 'Directory filenames',
				],
			],
			'filename' => [
				'label' => 'Filename',
				'type'  => 'text',
				'fields' => [
					'format'   => $this->getFormatField(),
				],
			],
			'override' => [
				'label'       => 'Overwrite if file exists',
				'type'        => 'boolean',
				'conditional' => [], //@ToDo task is sender
			],
		];
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function getFtpConnection( array $config )
	{
		$ftp = ftp_connect( $config['host'], $config['port'] ?? 21 ) or throw new \Exception( 'Cannot connect to ' . $config['host'] );
		$login = ftp_login( $ftp, $config['username'], $config['password'] );

		if ( ! $login ) {
			throw new \Exception( 'Cannot login to ' . $config['host'] );
		}

		ftp_pasv( $ftp, ! empty( $config['passive'] ) );

		return $ftp;
	}

	public function retrieve( array $config )
	{
		// Test connection first.
		$ftp = $this->getFtpConnection( $config );

		switch ( $config['get'] ?? '' ) {
			case 'dir':
				return $this->getFtpDirectory( $config, $ftp );

			case 'file':

				$path = $config['path'] ?? '.';

				if ( empty( $config['filename'] ) ) {
					throw new \Exception( 'No filename configured' );
				}

				$file = $path . '/' . $config['filename'];

				$tmpFile = $this->getTmpFile( $config );
				ftp_fget( $ftp, $tmpFile, $file );

				$fstats  = fstat( $tmpFile );
				$content = fread( $tmpFile, $fstats['size'] );

				fclose( $tmpFile );

				if ( ! empty( $config['format'] ) ) {
					$content = $this->fromFormat( $config['format'], $content );;
				}

				return $content;
		}

		throw new \Exception( 'No get method selected' );
	}

	public function send( array $config, $data )
	{
		$ftp = $this->getFtpConnection( $config );

		$filecontent = $this->toFormat( $config['format'] ?? '', $data );

		$filename = $config['filename'];
		if ( empty( $config['override'] ) ) {
			$filename = $this->uniqueFilename( $filename, $this->findFtpFiles( $config ) );
		}

		// Create tmp file for stream.
		$local_file = $this->getTmpFile();
		fwrite( $local_file, $filecontent );
		rewind( $local_file );

		// Go to directory.
		ftp_chdir( $ftp, $config['path'] );

		// Stream file to FTP.
		$upload_result = ftp_fput( $ftp, $filename, $local_file, FTP_BINARY );

		ftp_close( $ftp );

		if ( ! $upload_result ) {
			throw new \Exception( 'FTP error: The file could not be written to the FTP server.' );
		}

		return $data;
	}

	public function getTmpFile()
	{
		return fopen( 'php://temp', 'r+' );
	}

	public function getFtpDirectory( $config, $ftp = null ): array
	{
		if ( ! $ftp ) {
			$ftp = $this->getFtpConnection( $config );
		}

		$directory_files = ftp_nlist( $ftp, $config['path'] ?? '.' );

		if ( ! is_array( $directory_files ) ) {
			$message = 'Cannot read directory on ' . $config['host'];
			if ( empty( $config['passive'] ) ) {
				$message .= '. ' . 'Please try passive mode.';
			}
			throw new \Exception( $message );
		}

		return $directory_files;
	}

	public function uniqueFilename( $filename, $existing ): string
	{
		$ext           = pathinfo( $filename, PATHINFO_EXTENSION );
		$file_basename = basename( $filename, "." . $ext );

		$x = 1;
		while ( $x <= 999 ) {
			$newFilename = $file_basename . $x . "." . $ext;

			if ( ! in_array( $newFilename, $existing ) ) {
				$filename = $newFilename;
				break;
			}
			$x ++;
		}

		return $filename;
	}
}
