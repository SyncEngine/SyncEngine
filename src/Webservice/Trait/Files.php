<?php

namespace SyncEngine\Webservice\Trait;

trait Files
{
	public function getFullPath( string $name, string $path = '.', $sep = '/' ): string
	{
		$path = trim( $path, './' . DIRECTORY_SEPARATOR . $sep );
		$path = $sep . $path;

		if ( $name ) {
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
			throw new \Exception( $this->trans( 'Invalid resource' ) );
		}

		return stream_get_meta_data( $resource );
	}
}
