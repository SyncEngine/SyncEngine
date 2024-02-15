<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\Files;

class Ftp extends WebserviceModel
{
	use Files;

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'ftp';
		$this->name        = $this->trans( 'FTP',[],"ftp+intl-icu");
		$this->description = $this->trans( 'description',[],"ftp+intl-icu");
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => $this->trans( 'Host',[],"ftp+intl-icu" ),
				'type'  => 'text',
			],
			'port'     => [
				'label'   => $this->trans( 'Port',[],"ftp+intl-icu" ),
				'type'    => 'number',
				'default' => 21,
			],
			'username' => [
				'label' => $this->trans( 'Username',[],"ftp+intl-icu" ),
				'type'  => 'secret',
			],
			'password' => [
				'label' => $this->trans( 'Password',[],"ftp+intl-icu" ),
				'type'  => 'secret',
			],
			'passive'  => [
				'label' => $this->trans( 'Passive',[],"ftp+intl-icu" ),
				'type'  => 'checkbox',
			],
		];
	}

	public function getFields( array $defaults = [] ): array
	{
		return $this->getRequestFields( $defaults );
	}

	public function getRequestFields( array $defaults = [] ): array
	{
		return [
			'path' => [
				'label'       => $this->trans( 'Path',[],"ftp+intl-icu" ),
				'type'        => 'text',
				'placeholder' => './',
			],
		];
	}

	public function getRetrieveFields( array $defaults = [] ): array
	{
		return array_merge( parent::getRetrieveFields( $defaults ), [
			'action'   => [
				'label'   => $this->trans( 'Select_retrieve',[],"ftp+intl-icu" ),
				'type'    => 'select',
				'choices' => [
					'get'  => $this->trans( 'File_contents',[],"ftp+intl-icu" ),
					'list' => $this->trans( 'Dir_filenames',[],"ftp+intl-icu" ),
				],
			],
			'filename' => [
				'label'      => $this->trans( 'Filename',[],"ftp+intl-icu" ),
				'type'       => 'text',
				'fields'     => [
					'format' => $this->getFormatDecodeField(),
				],
				'conditions' => [
					'action' => 'get',
				],
			],
		] );
	}

	public function getSendFields( array $defaults = [] ): array
	{
		return array_merge( parent::getSendFields( $defaults ), [
			'action'   => [
				'label'   => $this->trans( 'Select_action_send',[],"ftp+intl-icu" ),
				'type'    => 'select',
				'choices' => [
					'put'    => $this->trans( 'Upload_file_content',[],"ftp+intl-icu" ),
					'delete' => $this->trans( 'Delete_file',[],"ftp+intl-icu" ),
					'mkdir'  => $this->trans( 'Create_dir',[],"ftp+intl-icu" ),
					'rmdir'  => $this->trans( 'Delete_dir',[],"ftp+intl-icu" ),
				],
			],
			'filename' => [
				'label'  => $this->trans( 'Filename',[],"ftp+intl-icu" ),
				'type'   => 'text',
				'fields' => [
					'format'   => $this->getFormatEncodeField(),
					'override' => [
						'label' => $this->trans( 'Overwrite_if_exist',[],"ftp+intl-icu" ),
						'type'  => 'boolean',
					],
				],
				'conditions' => [
					'action' => [ 'put', 'delete' ],
				],
			],
			'dirname' => [
				'label'      => $this->trans( 'Dir_name',[],"ftp+intl-icu" ),
				'type'       => 'text',
				'conditions' => [
					'action' => [ 'mkdir', 'rmdir' ],
				],
			],
		] );
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	/**
	 * @throws \Exception
	 *
	 * @param  array  $config
	 *
	 * @todo Maybe use https://github.com/Nicolab/php-ftp-client
	 * @return \FTP\Connection|null
	 */
	public function getClient( array $config ): ?object
	{
		$host = $this->getRequestUrl( $config );
		try{
			$client = @ftp_connect( $host, $config['port'] ?? 21 );
			if (false === $client) {
				throw new \Exception($this->trans( 'Cant_connect_to',['host' => $host],"ftp+intl-icu" ));
			}
			$login = @ftp_login( $client, $config['username'] ?? '', $config['password'] ?? '' );
			if ( ! $login ) {
				throw new \Exception( $this->trans( 'Cant_login_to',['host' => $host],"ftp+intl-icu" ));
			}
		}catch (Exception $e) {
			$this->trans( 'Error',['message' => $e->getMessage()],"ftp+intl-icu" );
		}

		ftp_pasv( $client, ! empty( $config['passive'] ) );

		return $client;
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$client = $this->getClient( $config );

		switch ( $config['action'] ?? '' ) {
			case 'file':
			case 'get':
				return $this->getFile( $config, $client );

			case 'list':
			case 'dir':
				return $this->getDirectory( $config, $client );
		}

		throw new \Exception( $this->trans( 'No_action',[],"ftp+intl-icu" ) );
	}

	public function send( array $config, $data ): Result
	{
		//@todo make the file/directory names variable with data
		switch ( $config['action'] ) {
			case 'file':
			case 'put':
				return $this->sendFile( $config, $data );

			case 'delete':
				return $this->deleteFile( $config );

			case 'mkdir':
				return $this->createDirectory( $config );

			case 'rmdir':
				return $this->deleteDirectory( $config );

		}

		throw new \Exception( $this->trans( 'No_action',[],"ftp+intl-icu" ) );
	}

	public function getFile( $config, $client ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( 'No_filename',[],"ftp+intl-icu" ) );
		}

		$file    = $this->getFullPath( $config['filename'], $config['path'] ?? '' );
		$tmpFile = $this->createTmpFile( $config['filename'] );

		$success = $this->_get( $client, $file, $tmpFile );

		if ( ! $success ) {
			if ( empty( $config['passive']) and $config['_class'] === "Ftp" ) {
				$message = $this->trans( 'Cant_fetch_file_try_passive', [ 'host' => $config['host']],"ftp+intl-icu" );
			} else {
				$message = $this->trans( 'Cant_fetch_file', [ 'host' => $config['host']],"ftp+intl-icu" );
			}
			throw new \Exception( $message );
		}

		// Get file path/name.
		$tmpFileName = $this->getResourcePath( $tmpFile );

		// Get file contents.
		$result = file_get_contents( $tmpFileName );

		try {
			if ( ! empty( $config['format'] ) ) {
				if ( $result ) {
					$result = $this->decodeFormat( $config['format'], $result, $config );
				} else {
					// Try to decode from file.
					$result = $this->decodeFormat( $config['format'], $tmpFileName, $config );
				}
			}
		} catch ( \Throwable $e ) {
			$this->removeTmpFile( $tmpFileName );
			fclose( $tmpFile );
			throw $e;
		}

		$this->removeTmpFile( $tmpFileName );
		fclose( $tmpFile );

		return new Result( $result, $this->trans( 'Successfully_retrieved', [ 'name' => $file ],"ftp+intl-icu"  ) );
	}

	public function sendFile( array $config, $data ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( "No_filename",[],"ftp+intl-icu" ) );
		}

		$client      = $this->getClient( $config );
		$response    = [];
		$content     = $this->encodeFormat( $config['format'] ?? '', $data );
		$remote_file = $this->getFullPath( $config['filename'], $config['path'] ?? '' );

		if ( empty( $config['override'] ) ) {
			$filepath = dirname( $remote_file );
			$originalFilename = basename( $remote_file );

			$directory = $this->getDirectory( $filepath ?? '.', $client );
			$filename  = $this->createUniqueFilename( $originalFilename, $directory->getData() );

			if ( $filename !== $originalFilename ) {
				$response[] = $this->trans( 'File_renamed', [ 'oldName' => $originalFilename, 'newName' => $filename ], "ftp+intl-icu" );

				$remote_file = $this->getFullPath( $filename, $filepath );
			}
		}


		$local_file = $this->createTmpFile();
		fwrite( $local_file, $content );
		rewind( $local_file );

		$success = $this->_put( $client, $remote_file, $local_file );

		$this->removeTmpFile( $local_file );

		if ( ! $success ) {
			throw new \Exception( $this->trans( 'Cant_write_server', [], "ftp+intl-icu" ) );
		}

		if ( ! empty( $response ) ) {
			$response[] = $this->trans( 'Successfully_uploaded', [ 'name' => $remote_file ],"ftp+intl-icu" );
		} else {
			$response = $this->trans( 'Successfully_uploaded', [ 'name' => $remote_file ],"ftp+intl-icu" );
		}

		return new Result( true, $response );
	}

	public function deleteFile( $config ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new \Exception( $this->trans( 'No_filename',[],"ftp+intl-icu" ) );
		}

		$file    = $this->getFullPath( $config['filename'], $config['path'] ?? '' );
		$success = $this->_delete( $this->getClient( $config ), $file );

		if ( ! $success ) {
			throw new \Exception( $this->trans( 'Cant_delete_server',['type'=>'file'],"ftp+intl-icu" ) );
		}

		return new Result( true, $this->trans( 'Successfully_deleted', [ 'name' => $file ],"ftp+intl-icu" ) );
	}

	public function getDirectory( $config, $client = null ): Result
	{
		$path = $this->getFullPath( "", $config['path'] ?? '' );
		$files = $this->_nlist( $client, $path ?? '.' );

		if ( ! is_array( $files ) ) {
			if ( empty( $config['passive'] ) ) {
				$message = $this->trans( 'Cant_read_dir_try_passive', [ 'host' => $config['host']], "ftp+intl-icu" );
			} else {
				$message = $this->trans( 'Cant_read_dir', [ 'host' => $config['host']], "ftp+intl-icu" );
			}
			throw new \Exception( $message );
		}

		return new Result( $files, $this->trans( 'Successfully_retrieved', [ 'name' => $config['path']],"ftp+intl-icu" ) );
	}

	public function createDirectory( $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new \Exception( $this->trans( 'No_dir',[],"ftp+intl-icu" ) );
		}

		$dir = $this->getFullPath( $config['dirname'], $config['path'] ?? '' );

		$success = $this->_mkdir( $this->getClient( $config ), $dir );

		if ( ! $success ) {
			throw new \Exception( $this->trans( 'Cant_create_dir', [ 'dir' => $dir ],"ftp+intl-icu" ) );
		}

		return new Result( true, $this->trans( 'Successfully_created_dir', [ 'dir' => $dir ],"ftp+intl-icu" ) );
	}

	public function deleteDirectory( $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new \Exception( $this->trans( 'No_dir',[],"ftp+intl-icu" ) );
		}

		$dir  = $this->getFullPath( $config['dirname'], $config['path'] ?? '' );

		$success = $this->_rmdir( $this->getClient( $config ), $dir );

		if ( ! $success ) {
			throw new \Exception( $this->trans( 'Cant_delete_server',['type'=>'dir'],"ftp+intl-icu" ) );
		}

		return new Result( true, $this->trans( 'Successfully_deleted', [ 'name' => $dir ],"ftp+intl-icu" ) );
	}

	public function _get( $client, $filename, $tmpFile )
	{
		try {
			$file = ftp_fget( $client, $tmpFile, $filename );
		} catch ( \Exception $e ) {
			return false;
		}

		return $file;
	}

	public function _put( $client, $filename, $local_file,  )
	{
		return ftp_fput( $client, $filename, $local_file, FTP_BINARY );
	}

	public function _delete( $client, $filename )
	{
		try {
			ftp_delete( $client, $filename );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	public function _nlist( $client, $directory = '.' ): array|false
	{
		return ftp_nlist( $client, $directory );
	}

	public function _mkdir( $client, $directory )
	{
		try {
			ftp_mkdir( $client, $directory );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	public function _rmdir( $client, $directory )
	{
		try {
			ftp_rmdir( $client, $directory );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}
}
