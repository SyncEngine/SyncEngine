<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\EmailFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Email extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = TextColumnType::TYPE;
		$this->name        = $this->trans( 'Email' );
		$this->description = $this->trans( 'Email column' );
	}

	public function getFields(): array
	{
		return [
			'mode' => [
				'label'   => $this->trans( 'Validation mode' ),
				'type'    => 'select',
				'help'    => [
					$this->trans( 'Strict`: Ensures the email address follows the RFC standards very closely. For example, it does not allow IP addresses in the domain part.' ),
					$this->trans( 'HTML5: Follows the HTML5 input rules, which are slightly more permissive than the strict RFC standards.' ),
					$this->trans( 'HTML5 allow-no-tld`: Similar to HTML5, but it also allows email addresses without a TLD (e.g., user@localhost).' ),
				],
				'choices' => [
					EmailFormatter::VALIDATION_MODE_STRICT             => $this->trans( 'Strict' ),
					EmailFormatter::VALIDATION_MODE_HTML5              => $this->trans( 'HTML5' ),
					EmailFormatter::VALIDATION_MODE_HTML5_ALLOW_NO_TLD => $this->trans( 'HTML5 allow-no-tld' ),
				],
			],
			'fallback' => [
				'label'   => $this->trans( 'Fallback' ),
				'help'    => $this->trans( 'Fallback value if it is an invalid email address.' ),
				'type'    => 'text',
			],
		];
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context = [];
		if ( ! empty( $config['mode'] ) ) {
			$context[ EmailFormatter::VALIDATOR_MODE ] = $config['mode'];
		}

		return new EmailFormatter( $context );
	}
}
