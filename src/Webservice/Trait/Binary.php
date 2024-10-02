<?php

namespace SyncEngine\Webservice\Trait;

use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\HttpClient\ResponseInterface;
use SyncEngine\Service\ValueObject\File;
use SyncEngine\Webservice\Helper\Result;

trait Binary
{
	public function parseBinaryResponse( $config, ResponseInterface $response ): Result
	{
		$path = $config['endpoint'];

		$headers = $response->getHeaders();

		$mimetype = $headers['Content-Type'][0] ?? $headers['content-type'][0] ?? '';

		$base64 = base64_encode( $response->getContent( false ) );

		$info = pathinfo( $path );

		$name = $info['filename'];
		$extension = $info['extension'] ?? '';

		if ( ! $mimetype ) {
			$mimetype = MimeTypes::getDefault()->guessMimeType( $path );
		}

		$code = $response->getStatusCode();

		$file = new File( name: $name, extension: $extension, mimetype: $mimetype, base64: $base64, url: $path );

		return new Result( $file, [ 'code' => $code, 'headers' => $headers ] );
	}
}
