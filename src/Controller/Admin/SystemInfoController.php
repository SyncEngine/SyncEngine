<?php

namespace SyncEngine\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use SyncEngine\Controller\Admin\Abstract\AbstractAdminController;
use SyncEngine\Kernel;
use SyncEngine\Service\Requirements;

#[IsGranted( 'ROLE_ADMIN' )]
#[Route( '/system' )]
class SystemInfoController extends AbstractAdminController
{
	#[Route( '/info', name: 'system_info' )]
	public function renderSystemInfo( Request $request, ?Connection $connection, Requirements $requirements ): Response
	{
		$requirementsData      = $requirements->load();
		$phpVersion            = $requirements->getPhpVersion();
		$extensionRequirements = $requirements->getExtensions();
		$iniRequirements       = $requirements->getIniSettings();

		$core   = [
			[
				'text'  => 'SyncEngine Core',
				'badge' => Kernel::VERSION,
			],
		];
		$server = [
			[
				'text'  => 'Server OS',
				'badge' => php_uname(),
			],
			[
				'text'  => 'Server Version',
				'badge' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
			],
			[
				'text'  => 'Server Host',
				'badge' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
			],
			[
				'text'  => 'Server IP',
				'badge' => gethostbyname( gethostname() ),
			],
		];

		$hasPhpVersionRequirement = ! empty( $requirementsData['php']['version'] );

		$php = [
			[
				'text'  => 'PHP Version' . ( $hasPhpVersionRequirement ? $this->formatRequirement( $phpVersion['minimum'], $phpVersion['recommended'], $phpVersion['maximum'] ) : '' ),
				'badge' => $hasPhpVersionRequirement ? [
					'text'    => $phpVersion['current'],
					'variant' => $this->statusVariant( $phpVersion['status'] ),
				] : $phpVersion['current'],
			],
		];

		$hasUploadMaxFilesize = false;

		foreach ( $iniRequirements as $ini ) {
			if ( 'upload_max_filesize' === $ini['key'] ) {
				$hasUploadMaxFilesize = true;
			}

			$php[] = [
				'text'  => $this->getIniLabel( $ini['key'] ) . $this->formatRequirement( $ini['minimum'], $ini['recommended'], $ini['maximum'] ),
				'badge' => [
					'text'    => $ini['current'],
					'variant' => $this->statusVariant( $ini['status'] ),
				],
			];
		}

		if ( empty( $iniRequirements ) ) {
			$php[] = [
				'text'  => 'PHP Memory Limit',
				'badge' => ini_get( 'memory_limit' ),
			];
			$php[] = [
				'text'  => 'PHP Max Execution time',
				'badge' => ini_get( 'max_execution_time' ),
			];
		}

		if ( ! $hasUploadMaxFilesize ) {
			$php[] = [
				'text'  => 'PHP Max Upload Size',
				'badge' => ini_get( 'upload_max_filesize' ),
			];
		}

		$extensions         = [];
		$loadedCount        = 0;
		$missingRequired    = 0;
		$missingRecommended = 0;

		foreach ( $extensionRequirements as $extension ) {
			if ( $extension['loaded'] ) {
				++$loadedCount;
				$badgeText = $extension['loadedVia'] !== $extension['name']
					? 'Loaded via ' . $extension['loadedVia']
					: 'Loaded';
			} else {
				$badgeText = 'Missing';
				if ( $extension['required'] ) {
					++$missingRequired;
					$badgeText .= ' (required)';
				} elseif ( $extension['recommended'] ) {
					++$missingRecommended;
					$badgeText .= ' (recommended)';
				}
			}

			$extensions[] = [
				'text'  => $extension['label'],
				'badge' => [
					'text'    => $badgeText,
					'variant' => $this->statusVariant( $extension['status'] ),
				],
			];
		}

		$database = [];
		if ( $connection?->isConnected() ) {
			$platform = $connection->getDatabasePlatform()::class;

			try {
				$version = $connection->executeQuery(
					str_contains( strtolower( $platform ), 'sqlite' ) ? 'SELECT sqlite_version()' : 'SELECT @@version'
				)->fetchOne();
			} catch ( \Exception $e ) {
				$version = 'Unknown';
				if ( $this->getParameter( 'kernel.debug' ) ) {
					$version .= ' (' . $e->getMessage() . ')';
				}
			}

			$database = [
				[
					'text'  => 'Database Platform',
					'badge' => $platform,
				],
				[
					'text'  => 'Database Version',
					'badge' => $version,
				],
			];
			try {
				$params   = $connection->getParams();
				if ( isset( $params['driver'] ) ) {
					$database[] = [
						'text'  => 'Database Driver',
						'badge' => $params['driver'],
					];
				}
				if ( isset( $params['charset'] ) ) {
					$database[] = [
						'text'  => 'Database Charset',
						'badge' => $params['charset'],
					];
				}
				if ( isset( $params['host'] ) ) {
					$database[] = [
						'text'  => 'Database Host',
						'badge' => $params['host'],
					];
				}
			} catch ( \Throwable $e ) {
				// Do nothing.
			}
			$database[] = [
				'text'  => 'Database Name',
				'badge' => $connection->getDatabase(),
			];
		}

		$cards = [
			[
				'header' => 'Core',
				'list'   => $core,
			],
			[
				'header' => 'Server',
				'list'   => $server,
			],
			[
				'header' => 'PHP',
				'list'   => $php,
			],
		];

		if ( $extensions ) {
			$cards[] = [
				'header' => 'PHP Extensions',
				'badge'  => [
					'text'    => $loadedCount . '/' . count( $extensions ) . ' loaded',
					'variant' => $missingRequired ? 'danger' : ( $missingRecommended ? 'warning' : 'success' ),
				],
				'list'   => $extensions,
			];
		}

		$cards[] = [
			'header' => 'Database',
			'list'   => $database,
		];

		return $this->render(
			'admin/system/index.html.twig',
			[
				'backlink'    => $this->generateUrl( 'syncengine_system_index' ),
				'header'      => $this->trans( 'Info' ),
				'icon'        => 'system-info',
				'cards'       => $cards,
				'breadcrumbs' => [
					[
						'link'  => $this->generateUrl( 'syncengine_system_index' ),
						'title' => $this->trans( 'System' ),
					],
					[
						'title'   => $this->trans( 'Info' ),
						'current' => true,
					],
				],
			]
		);
	}

	private function statusVariant( string $status ): string
	{
		return match ( $status ) {
			'ok'      => 'success',
			'warning' => 'warning',
			'error'   => 'danger',
			default   => 'secondary',
		};
	}

	private function formatRequirement( mixed $minimum, mixed $recommended, mixed $maximum = null ): string
	{
		$parts = [];
		$add   = static function ( string $label, mixed $value ) use ( &$parts ): void {
			if ( null === $value || '' === $value ) {
				return;
			}
			if ( is_bool( $value ) ) {
				$value = $value ? 'On' : 'Off';
			}
			$parts[] = $label . ' ' . $value;
		};

		$add( 'min', $minimum );
		$add( 'recommended', $recommended );
		$add( 'max', $maximum );

		return $parts ? ' (' . implode( ', ', $parts ) . ')' : '';
	}

	private function getIniLabel( string $key ): string
	{
		return match ( $key ) {
			'memory_limit'        => 'PHP Memory Limit',
			'max_execution_time'  => 'PHP Max Execution time',
			'upload_max_filesize' => 'PHP Max Upload Size',
			default               => 'PHP ' . ucwords( str_replace( '_', ' ', $key ) ),
		};
	}
}
