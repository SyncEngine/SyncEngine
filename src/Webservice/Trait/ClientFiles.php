<?php

namespace SyncEngine\Webservice\Trait;

use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Exception\InvalidParameterException;
use SyncEngine\Webservice\Exception\ResultException;
use SyncEngine\Webservice\Helper\Result;

trait ClientFiles
{
	abstract public function getClient( array $config );

	public function getFields( array $defaults = [] ): array
	{
		return $this->getRequestFields( $defaults );
	}

	public function getRequestFields( array $defaults = [] ): array
	{
		return [
			'path' => [
				'label'       => $this->trans( 'Path' ),
				'type'        => 'text',
				'placeholder' => './',
			],
		];
	}

	public function getRetrieveFields( array $defaults = [] ): array
	{
		return array_merge(
			parent::getRetrieveFields( $defaults ),
			[
				'action'   => [
					'label'   => $this->trans( 'Select what you want to retrieve' ),
					'type'    => 'select',
					'choices' => [
						'get'       => $this->trans( 'File contents' ),
						'listAll'   => $this->trans( 'List directories and filenames' ),
						'listFiles' => $this->trans( 'List filenames' ),
						'listDirs'  => $this->trans( 'List directories' ),
					],
				],
				'filename' => [
					'label'      => $this->trans( 'Filename' ),
					'type'       => 'text',
					'fields'     => [
						'format' => $this->getFormatDecodeField(),
					],
					'conditions' => [
						'action' => 'get',
					],
				],
			],
		);
	}

	public function getSendFields( array $defaults = [] ): array
	{
		return array_merge(
			parent::getSendFields( $defaults ),
			[
				'action'   => [
					'label'   => $this->trans( 'Select what action you want to send' ),
					'type'    => 'select',
					'choices' => [
						'put'    => $this->trans( 'Upload file contents' ),
						'delete' => $this->trans( 'Delete file' ),
						'mkdir'  => $this->trans( 'Create directory' ),
						'rmdir'  => $this->trans( 'Delete directory' ),
						'rename' => $this->trans( 'Rename or move a file or directory' ),
					],
				],
				'filename' => [
					'label'      => $this->trans( 'Filename' ),
					'type'       => 'text',
					'fields'     => [
						'format'   => $this->getFormatEncodeField(),
						'override' => [
							'label' => $this->trans( 'Overwrite if file exists' ),
							'type'  => 'boolean',
						],
					],
					'conditions' => [
						'action' => [ 'put', 'delete' ],
					],
				],
				'dirname'  => [
					'label'      => $this->trans( 'Directory name' ),
					'type'       => 'text',
					'conditions' => [
						'action' => [ 'mkdir', 'rmdir' ],
					],
				],
				'rename'   => [
					'conditions' => [
						'action' => [ 'rename' ],
					],
					'nested'     => [
						'from'     => [
							'label' => $this->trans( 'Old name' ),
							'type'  => 'text',
						],
						'to'       => [
							'label' => $this->trans( 'New name' ),
							'type'  => 'text',
						],
						'override' => [
							'label' => $this->trans( 'Overwrite if new name exists' ),
							'type'  => 'boolean',
						],
					],
				],
			]
		);
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$client = $this->getClient( $config );

		switch ( $config['action'] ?? '' ) {
			case 'file':
			case 'get':
				return $this->fetchFile( $client, $config );

			case 'list':
			case 'listAll':
				return $this->listDirectory( $client, $config );
			case 'listFiles':
				return $this->listDirectory( $client, $config, 'file' );
			case 'listDirs':
				return $this->listDirectory( $client, $config, 'dir' );
		}

		throw new InvalidConfigException( $this->trans( 'No action configured' ) );
	}

	public function send( array $config, $data ): Result
	{
		$client = $this->getClient( $config );

		//@todo make the file/directory names variable with data
		switch ( $config['action'] ) {
			case 'file':
			case 'put':
				return $this->writeFile( $client, $config, $data );

			case 'delete':
				return $this->deleteFile( $client, $config );

			case 'mkdir':
				return $this->createDirectory( $client, $config );

			case 'rmdir':
				return $this->deleteDirectory( $client, $config );

			case 'rename':
				return $this->rename( $client, $config );
		}

		throw new InvalidConfigException( $this->trans( 'No action configured' ) );
	}

	public function fetchFile( $client, $config ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new InvalidConfigException( $this->trans( 'No Filename configured' ) );
		}

		$file    = $this->getFullPath( $config['filename'], $config['path'] ?? '' );
		$tmpFile = $this->createTmpFile( $config['filename'] );

		try {
			$success = $this->_get( $client, $file, $tmpFile );
		} catch ( \Throwable $e ) {
			throw new ResultException( $e, [ 'file' => $file ], $e->getCode() );
		}

		if ( ! $success ) {
			$fields = $this->getAuthFields();
			if ( isset( $fields['passive'] ) && empty( $config['passive'] ) ) {
				$message = $this->trans(
					'Cannot fetch file from {host}, please try passive mode',
					[ 'host' => $config['host'] ]
				);
			} else {
				$message = $this->trans(
					'Cannot fetch file from {host}',
					[ 'host' => $config['host'] ]
				);
			}
			throw new ResultException( $message );
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
			// @todo return ResultException?
			throw $e;
		}

		$this->removeTmpFile( $tmpFileName );
		fclose( $tmpFile );

		return new Result(
			$result,
			$this->trans( 'Successfully retrieved: {name}', [ 'name' => $file ] ),
			[ 'config' => $config ]
		);
	}

	public function writeFile( $client, array $config, $data ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new InvalidConfigException( $this->trans( 'No Filename configured' ) );
		}

		$response = [];
		$content  = $this->encodeFormat( $config['format'] ?? '', $data, $config );
		if ( ! is_string( $content ) ) {
			$content = reset( $content );
		}

		$remote_file = $this->getFullPath( $config['filename'], $config['path'] ?? '' );

		if ( empty( $config['override'] ) ) {
			$filepath         = dirname( $remote_file );
			$originalFilename = basename( $remote_file );

			$directory = $this->_nlist( $client, $filepath ?? '.' );
			$filename  = $this->createUniqueFilename( $originalFilename, $directory );

			if ( $filename !== $originalFilename ) {
				$response[] = $this->trans(
					'File {oldName} existed, renamed it to {newName}',
					[ 'oldName' => $originalFilename, 'newName' => $filename ]
				);

				$remote_file = $this->getFullPath( $filename, $filepath );
			}
		}

		try {
			$success = $this->_put( $client, $remote_file, $content );
		} catch ( \Throwable $e ) {
			throw new ResultException( $e, [ 'file' => $remote_file ], $e->getCode() );
		}

		if ( ! $success ) {
			throw new ResultException(
				$this->trans( 'Could not be write file: {name}', [ 'name' => $remote_file ] )
			);
		}

		if ( ! empty( $response ) ) {
			$response[] = $this->trans( 'Successfully uploaded: {name}', [ 'name' => $remote_file ] );
		} else {
			$response = $this->trans( 'Successfully uploaded: {name}', [ 'name' => $remote_file ] );
		}

		return new Result( true, $response, [ 'config' => $config ] );
	}

	public function deleteFile( $client, $config ): Result
	{
		if ( empty( $config['filename'] ) ) {
			throw new InvalidConfigException( $this->trans( 'No Filename configured' ) );
		}

		$file    = $this->getFullPath( $config['filename'], $config['path'] ?? '' );

		try {
			$success = $this->_delete( $client, $file );
		} catch ( \Throwable $e ) {
			throw new ResultException( $e, [ 'file' => $file ], $e->getCode() );
		}

		if ( ! $success ) {
			throw new ResultException(
				$this->trans( 'Could not delete file {name}', [ 'name' => $file ] )
			);
		}

		return new Result(
			true,
			$this->trans( 'Successfully deleted file: {name}', [ 'name' => $file ] ),
			[ 'config' => $config ]
		);
	}

	public function listDirectory( $client, $config, $type = null ): Result
	{
		$path  = $this->getFullPath( '', $config['path'] ?? '' );

		try {
			$files = $this->_list( $client, $path ?: '.', $type );
		} catch ( \Throwable $e ) {
			throw new ResultException( $e, [ 'path' => $path ], $e->getCode() );
		}

		if ( ! is_array( $files ) ) {
			$message = $this->trans(
				'Cannot read directory from {host}',
				[ 'host' => $config['host'] . $path ]
			);
			throw new ResultException( $message );
		}

		return new Result(
			$files,
			$this->trans( 'Successfully retrieved: {name}', [ 'name' => $path ] ),
			[ 'config' => $config ]
		);
	}

	public function createDirectory( $client, $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new InvalidConfigException( $this->trans( 'No directory configured' ) );
		}

		$dir = $this->getFullPath( $config['dirname'], $config['path'] ?? '' );

		try {
			$success = $this->_mkdir( $client, $dir );
		} catch ( \Throwable $e ) {
			throw new ResultException( $e, [ 'path' => $dir ], $e->getCode() );
		}

		if ( ! $success ) {
			throw new ResultException(
				$this->trans( 'Could not create directory: {path}', [ 'path' => $dir ] )
			);
		}

		return new Result(
			true,
			$this->trans( 'Successfully created directory: {path}', [ 'path' => $dir ] ),
			[ 'config' => $config ]
		);
	}

	public function deleteDirectory( $client, $config ): Result
	{
		if ( empty( $config['dirname'] ) ) {
			throw new InvalidConfigException( $this->trans( 'No directory configured' ) );
		}

		$dir = $this->getFullPath( $config['dirname'], $config['path'] ?? '' );

		try {
			$success = $this->_rmdir( $client, $dir );
		} catch ( \Throwable $e ) {
			throw new ResultException( $e, [ 'path' => $dir ], $e->getCode() );
		}

		if ( ! $success ) {
			throw new ResultException(
				$this->trans( 'Could not delete directory: {path}', [ 'path' => $dir ] )
			);
		}

		return new Result(
			true,
			$this->trans( 'Successfully deleted: {path}', [ 'path' => $dir ] ),
			[ 'config' => $config ]
		);
	}

	public function rename( $client, $config ): Result
	{
		$rename = $config['rename'] ?? [];

		if ( empty( $rename['from'] ) || empty( $rename['to'] ) ) {
			throw new InvalidConfigException( $this->trans( 'Invalid rename configuration' ) );
		}

		$from = $this->getFullPath( $rename['from'], $config['path'] ?? '' );
		$to   = $this->getFullPath( $rename['to'], $config['path'] ?? '' );

		try {
			$success = $this->_rename( $client, $from, $to, ! empty( $rename['override'] ) );
		} catch ( \Throwable $e ) {
			throw new ResultException( $e, [ 'old_name' => $from, 'new_name' => $to ], $e->getCode() );
		}

		if ( ! $success ) {
			throw new ResultException(
				$this->trans( 'Could not rename: {old} to {new}', [ 'old' => $from, 'new' => $to ] )
			);
		}

		return new Result(
			true,
			$this->trans( 'Successfully renamed: {old} to {new}', [ 'old' => $from, 'new' => $to ] ),
			[ 'config' => $config ]
		);
	}

	public function getFullPath( string $name, string $path = '.', $sep = '/' ): string
	{
		$path = trim( $path, './' . DIRECTORY_SEPARATOR . $sep );
		$path = $sep . $path;

		if ( $name && ! str_ends_with( $path, $sep ) ) {
			$path .= $sep;
		}

		return $path . trim( $name, './' . DIRECTORY_SEPARATOR . $sep );
	}

	public function createUniqueFilename( $filename, $existing ): string
	{
		$ext           = pathinfo( $filename, PATHINFO_EXTENSION );
		$file_basename = basename( $filename, '.' . $ext );

		if ( in_array( $filename, $existing ) ) {
			$x = 1;
			$l = count( $existing );
			while ( $x <= $l ) {
				$newFilename = $file_basename . $x . '.' . $ext;

				if ( ! in_array( $newFilename, $existing ) ) {
					$filename = $newFilename;
					break;
				}
				$x ++;
			}
		}

		return $filename;
	}

	/**
	 * @param  string         $content
	 * @param  resource|null  $tmpFile
	 *
	 * @return false|resource
	 */
	public function writeTmpFile( string $content, $tmpFile = null )
	{
		if ( ! $tmpFile ) {
			$tmpFile = $this->createTmpFile();
		}
		fwrite( $tmpFile, $content );
		rewind( $tmpFile );

		return $tmpFile;
	}

	/**
	 * @param  resource  $tmpFile
	 *
	 * @return false|string
	 */
	public function readTmpFile( $tmpFile ): string|false
	{
		return file_get_contents( $this->getResourcePath( $tmpFile ) );
	}

	/**
	 * @param  string  $filename
	 * @param  string  $mode
	 *
	 * @return false|resource
	 */
	public function createTmpFile( string $filename = '', string $mode = 'w+' )
	{
		if ( $filename ) {
			$filename = tempnam( sys_get_temp_dir(), $filename );
		} else {
			$filename = 'php://temp';
		}

		return fopen( $filename, $mode );
	}

	public function removeTmpFile( $filename ): bool
	{
		if ( is_resource( $filename ) ) {
			// Get file name.
			$filename = $this->getResourcePath( $filename );
		}

		if ( is_file( $filename ) ) {
			return unlink( $filename );
		}

		return false;
	}

	public function getResourcePath( $resource ): string
	{
		return $this->getResourceMeta( $resource )['uri'];
	}

	public function getResourceMeta( $resource ): array
	{
		if ( ! is_resource( $resource ) ) {
			throw new InvalidParameterException( $this->trans( 'Invalid resource' ) );
		}

		return stream_get_meta_data( $resource );
	}

	abstract public function _get( $client, $filename, $resource );

	abstract public function _put( $client, $filename, $resource );

	abstract public function _delete( $client, $filename );

	abstract public function _nlist( $client, $directory = '.' );

	abstract public function _mkdir( $client, $directory );

	abstract public function _rmdir( $client, $directory );

	abstract public function _rename( $client, $from, $to, $override = false );

	abstract public function _list( $client, $directory = '.', $type = null );
}
