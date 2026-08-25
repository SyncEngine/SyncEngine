<?php

namespace SyncEngine\Service;

use Masterminds\HTML5\Parser\UTF8Utils;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class SerializationSanitizer
{
	const SANITIZE_UTF8 = 'encode_utf8';
	const SANITIZE_RESOURCE = 'sanitize_resource';
	const SANITIZE_HTML = 'sanitize_html';

	private ?HtmlSanitizer $htmlSanitizer = null;

	/**
	 * @return $this
	 */
	public function setHtmlSanitizer( HtmlSanitizer $htmlSanitizer ): self
	{
		$this->htmlSanitizer = $htmlSanitizer;

		return $this;
	}

	public function sanitize( mixed $data, array $options = [] ): mixed
	{
		$options = array_merge( [
			self::SANITIZE_UTF8     => true,
			self::SANITIZE_RESOURCE => true,
			self::SANITIZE_HTML     => true,
		], $options );

		if ( is_iterable( $data ) ) {
			foreach ( $data as &$value ) {
				$value = $this->sanitize( $value, $options );
			}
			unset( $value ); // Remove reference to avoid accidental modifications.
		}

		if ( is_resource( $data ) && ! empty( $options[ self::SANITIZE_RESOURCE ] ) ) {
			$data = $this->sanitizeResource( $data );
		}

		if ( is_string( $data ) && ! empty( $options[ self::SANITIZE_UTF8 ] ) ) {
			$data = $this->encodeUtf8( $data );
		}

		if ( is_string( $data ) && ! empty( $options[ self::SANITIZE_HTML ] ) ) {
			$data = $this->sanitizeHtml( $data );
		}

		return $data;
	}

	public function sanitizeResource( $data )
	{
		if ( is_resource( $data ) ) {
			return sprintf(
				'resource(%d) of type (%s)',
				(int) $data,
				get_resource_type( $data )
			);
		}
		return $data;
	}

	public function encodeUtf8( string $data ): string
	{
		if ( mb_detect_encoding( $data, 'UTF-8', true ) !== 'UTF-8') {
			return UTF8Utils::convertToUTF8( $data );
		}
		return $data;
	}

	public function sanitizeHtml( string $html ): string
	{
		if ( ! isset( $this->htmlSanitizer ) ) {
			$this->htmlSanitizer = new HtmlSanitizer( ( new HtmlSanitizerConfig() )->allowSafeElements() );
		}

		return $this->htmlSanitizer->sanitize( $html );
	}
}
