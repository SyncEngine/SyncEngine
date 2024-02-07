<?php

namespace SyncEngine\Service\Serializer;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader as Readers;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use SyncEngine\Service\Interface\CodecInterface;

class ExcelEncoder extends \Ang3\Component\Serializer\Encoder\ExcelEncoder implements CodecInterface
{
	/**
	 * Formats constants.
	 */
	public const XLS  = 'xls';
	public const XLSX = 'xlsx';

	/**
	 * Context constants.
	 */
	public const AS_COLLECTION_KEY                = CsvEncoder::AS_COLLECTION_KEY;
	public const FLATTENED_HEADERS_SEPARATOR_KEY  = 'flattened_separator_key';
	public const HEADERS_IN_BOLD_KEY              = 'headers_in_bold';
	public const HEADERS_HORIZONTAL_ALIGNMENT_KEY = 'headers_horizontal_alignment';
	public const COLUMNS_AUTOSIZE_KEY             = 'columns_autosize';
	public const COLUMNS_MAXSIZE_KEY              = 'columns_maxsize';

	/**
	 * Value returned in the array entry if a cell doesn't exist
	 */
	public const NULL_VALUE = 'null_value';

	/**
	 * Should formulas be calculated?
	 */
	public const CALCULATE_FORMULAS = 'calculate_formulas';

	/**
	 * Should formatting be applied to cell values?
	 */
	public const FORMAT_DATA = 'format_data';

	/**
	 * False - Return a simple array of rows and columns indexed by number counting from zero
	 * True - Return rows and columns indexed by their actual row and column IDs
	 */
	public const RETURN_CELL_REF = 'return_cell_ref';

	/**
	 * False - Return values for rows/columns even if they are defined as hidden.
	 * True - Don't return values for rows/columns that are defined as hidden.
	 */
	public const IGNORE_HIDDEN = 'ignore_hidden';

	/**
	 * @static
	 */
	private static array $formats = [
		self::XLS,
		self::XLSX,
	];

	private array $defaultContext = [
		self::AS_COLLECTION_KEY                => true,
		self::FLATTENED_HEADERS_SEPARATOR_KEY  => '.',
		self::HEADERS_IN_BOLD_KEY              => true,
		self::HEADERS_HORIZONTAL_ALIGNMENT_KEY => 'center',
		self::COLUMNS_AUTOSIZE_KEY             => true,
		self::COLUMNS_MAXSIZE_KEY              => 50,
		self::NULL_VALUE                       => null,
		self::CALCULATE_FORMULAS               => true,
		self::FORMAT_DATA                      => false,
		self::RETURN_CELL_REF                  => false,
		self::IGNORE_HIDDEN                    => false,
	];

	private Filesystem $filesystem;

	public function __construct( array $defaultContext = [] )
	{
		$this->defaultContext = array_merge( $this->defaultContext, $defaultContext );
		$this->filesystem     = new Filesystem();
	}

	/**
	 * {@inheritdoc}.
	 *
	 * @throws NotEncodableValueException When data are not valid
	 * @throws InvalidArgumentException   When the format or data not supported
	 * @throws RuntimeException           When data reading failed
	 * @throws PhpSpreadsheetException    On data failure
	 */
	public function decode( $data, $format, array $context = [] ): mixed
	{
		if ( ! \is_scalar( $data ) ) {
			throw new NotEncodableValueException( sprintf( 'Expected data of type scalar, %s given', \gettype( $data ) ) );
		}

		/*$adapter = new FilesystemAdapter();
		$cache = new \Symfony\Component\Cache\Psr16Cache( $adapter );

		\PhpOffice\PhpSpreadsheet\Settings::setCache( $cache );*/

		if ( is_file( $data ) ) {
			$tmpFile = $data;
		} else {
			$tmpFile = (string) tempnam( sys_get_temp_dir(), $format );
			$this->filesystem->dumpFile( $tmpFile, $data );
		}

		$context = $this->normalizeContext( $context );

		switch ( $format ) {
			// Excel 2007
			case self::XLSX:
				$reader = new Readers\Xlsx();
			break;

			// Excel 2003
			case self::XLS:
				$reader = new Readers\Xls();
			break;

			default:
				$fileType = IOFactory::identify( $tmpFile );
				if ( ! $fileType ) {
					throw new InvalidArgumentException( sprintf( 'The format "%s" is not supported', $format ) );
				}
				$reader = IOFactory::createReader( $fileType );
			break;
		}

		try {
			$spreadsheet = $reader->load( $tmpFile );
			$this->filesystem->remove( $tmpFile );
		} catch ( \Exception $e ) {
			throw new RuntimeException( sprintf( 'Excel decoding failed - %s', $e->getMessage() ), 0, $e );
		}

		$loadedSheetNames = $spreadsheet->getSheetNames();
		$data             = [];

		foreach ( $loadedSheetNames as $sheetIndex => $loadedSheetName ) {
			$worksheet = $spreadsheet->getSheet( $sheetIndex );
			$sheetData = $worksheet->toArray( $context[ self::NULL_VALUE ], (bool) $context[ self::CALCULATE_FORMULAS ], (bool) $context[ self::FORMAT_DATA ], (bool) $context[ self::RETURN_CELL_REF ], (bool) $context[ self::IGNORE_HIDDEN ], );

			if ( 0 === \count( $sheetData ) ) {
				continue;
			}

			if ( false === $context[ self::AS_COLLECTION_KEY ] ) {
				$data[ $loadedSheetName ] = $sheetData;

				continue;
			}

			$labelledRows = [];
			$headers      = null;

			foreach ( $sheetData as $rowIndex => $cells ) {
				$rowIndex = (int) $rowIndex;

				if ( null === $headers ) {
					$headers = [];

					foreach ( $cells as $key => $value ) {
						if ( null === $value || '' === $value ) {
							continue;
						}

						$headers[ $key ] = $value;
						unset( $sheetData[ $rowIndex ][ $key ] );
					}

					continue;
				}

				foreach ( $cells as $key => $value ) {
					if ( \array_key_exists( $key, $headers ) ) {
						$labelledRows[ $rowIndex - 1 ][ (string) $headers[ $key ] ] = $value;
					} else {
						$labelledRows[ $rowIndex - 1 ][''][ $key ] = $value;
					}

					unset( $sheetData[ $rowIndex ][ $key ] );
				}

				unset( $sheetData[ $rowIndex ] );
			}

			$data[ $loadedSheetName ] = $labelledRows;
		}

		return $data;
	}

	/**
	 * @internal
	 */
	private function normalizeContext( array $context = [] ): array
	{
		return [
			self::AS_COLLECTION_KEY                => (bool) $this->getContextValue( $context, self::AS_COLLECTION_KEY ),
			self::FLATTENED_HEADERS_SEPARATOR_KEY  => (string) $this->getContextValue( $context, self::FLATTENED_HEADERS_SEPARATOR_KEY ),
			self::HEADERS_IN_BOLD_KEY              => (bool) $this->getContextValue( $context, self::HEADERS_IN_BOLD_KEY ),
			self::HEADERS_HORIZONTAL_ALIGNMENT_KEY => (string) $this->getContextValue( $context, self::HEADERS_HORIZONTAL_ALIGNMENT_KEY ),
			self::COLUMNS_AUTOSIZE_KEY             => (bool) $this->getContextValue( $context, self::COLUMNS_AUTOSIZE_KEY ),
			self::COLUMNS_MAXSIZE_KEY              => (int) $this->getContextValue( $context, self::COLUMNS_MAXSIZE_KEY ),
			self::NULL_VALUE                       => (int) $this->getContextValue( $context, self::NULL_VALUE ),
			self::CALCULATE_FORMULAS               => (int) $this->getContextValue( $context, self::CALCULATE_FORMULAS ),
			self::FORMAT_DATA                      => (int) $this->getContextValue( $context, self::FORMAT_DATA ),
			self::RETURN_CELL_REF                  => (int) $this->getContextValue( $context, self::RETURN_CELL_REF ),
			self::IGNORE_HIDDEN                    => (int) $this->getContextValue( $context, self::IGNORE_HIDDEN ),
		];
	}

	/**
	 * @internal
	 */
	private function getContextValue( array $context, int|string $key ): bool|int|float|string|null
	{
		return $context[ $key ] ?? $this->defaultContext[ $key ];
	}
}
