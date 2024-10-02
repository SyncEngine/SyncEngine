<?php

namespace SyncEngine\Webservice;

use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\ClientFiles;
use SyncEngine\Webservice\Type\LocalWebserviceType;

class LocalFilesystem extends WebserviceModel
{
	use ClientFiles;

	public function __construct()
	{
		parent::__construct();

		$this->type        = LocalWebserviceType::TYPE;
		$this->name        = $this->trans( 'Local File system' );
		$this->description = $this->trans( 'Use the local filesystem defined by your environment' );
	}

	public function getAuthFields(): array
	{
		return [
			'root' => [
				'label' => $this->trans( 'Root path' ),
				'type'  => 'text',
			],
		];
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['root'] ?? '';
	}

	/**
	 * @param $config
	 *
	 * @return object
	 */
	public function getClient( array $config )
	{
		$root = $this->getRequestUrl( $config );

		return new class( $root ) {
			public function __construct( private string $root ) {}

			public function root()
			{
				return $this->root;
			}

			public function getRootPath( string $fileOrDir )
			{
				return $this->root . $fileOrDir;
			}

			public function get( $filename )
			{
				return file_get_contents( $this->getRootPath( $filename ) );
			}

			public function put( $filename, $content )
			{
				( new Filesystem() )->dumpFile( $this->getRootPath( $filename ), $content );

				return true;
			}

			public function nlist( $directory )
			{
				return scandir( $this->getRootPath( $directory ) );
			}

			public function scandir( $directory = '.', $type = null ): array
			{
				$path    = $this->getRootPath( $directory );
				$results = scandir( $path );

				$files = [];
				foreach ( $results as $result ) {
					if ( '.' === $result || '..' === $result ) {
						continue;
					}

					$fullPath = $path . DIRECTORY_SEPARATOR . $result;

					if ( $type == 'dir' && ! is_dir( $fullPath ) ) {
						continue;
					}
					if ( $type == 'file' && ! is_file( $fullPath ) ) {
						continue;
					}

					$files[] = $fullPath;
				}

				return $files;
			}

			public function mkdir( $directory )
			{
				( new Filesystem() )->mkdir( $this->getRootPath( $directory ) );

				return true;
			}

			public function remove( $fileOrDir )
			{
				( new Filesystem() )->remove( $this->getRootPath( $fileOrDir ) );

				return true;
			}

			public function rename( $old, $new, $override = false )
			{
				( new Filesystem() )->rename( $old, $new, $override );

				return true;
			}
		};
	}

	public function connect( array $config ): Result
	{
		try {
			$root = $this->getRequestUrl( $config );

			$fs = new Filesystem();
			if ( ! $fs->exists( $root ) ) {
				$fs->mkdir( $root );
			}

			return new Result(
				true, true, [
					    'Message' => $this->trans(
						    'Successfully accessed {root}',
						    [ 'root' => $this->getRequestUrl( $config ) ]
					    ),
					    'Config'  => $config,
				    ]
			);
		} catch ( \Exception $e ) {
			return new Result(
				false, false, [
					     'Error'  => [
						     'Message' => $this->trans(
							     'Could not access {root}',
							     [ 'root' => $this->getRequestUrl( $config ) ]
						     ),
						     'Error'   => $e->getMessage(),
					     ],
					     'Config' => $config,
				     ]
			);
		}
	}

	public function _get( $client, $filename, $resource )
	{
		$this->writeTmpFile( $client->get( $filename ), $resource );

		return true;
	}

	public function _put( $client, $filename, $resource )
	{
		if ( is_resource( $resource ) ) {
			$resource = $this->readTmpFile( $resource );
		}

		return $client->put( $filename, $resource );
	}

	public function _delete( $client, $filename )
	{
		return $client->remove( $filename );
	}

	public function _nlist( $client, $directory = '.' )
	{
		return $client->nlist( $directory );
	}

	public function _list( $client, $directory = '.', $type = null )
	{
		return $client->scandir( $directory, $type );
	}

	public function _mkdir( $client, $directory )
	{
		return $client->mkdir( $directory );
	}

	public function _rmdir( $client, $directory )
	{
		return $client->remove( $directory );
	}

	public function _rename( $client, $from, $to, $override = false )
	{
		return $client->rename( $from, $to, $override );
	}
}
