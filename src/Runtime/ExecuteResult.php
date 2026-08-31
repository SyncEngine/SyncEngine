<?php

namespace SyncEngine\Runtime;

readonly class ExecuteResult
{
	private function __construct(
		private bool $success,
		private string $message = '',
		private ?ExecuteData $data = null,
		private array $errors = [],
	) {}

	public static function success( string $message, ?ExecuteData $data = null ): self
	{
		return new self( true, message: $message, data: $data );
	}

	public static function error( string $message, array $errors = [] ): self
	{
		return new self( false, message: $message, errors: $errors );
	}

	public function isSuccess(): bool
	{
		return $this->success;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getData(): ?ExecuteData
	{
		return $this->data;
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	public function hasData(): bool
	{
		return null !== $this->data;
	}

	public function hasErrors(): bool
	{
		return ! empty( $this->errors );
	}

	public function normalize(): array
	{
		$result = [
			'success' => $this->isSuccess(),
			'message' => $this->getMessage(),
		];

		if ( $this->hasErrors() ) {
			$result['errors'] = $this->getErrors();
		}

		if ( $this->hasData() ) {
			$result['data'] = $this->getData()->normalize();
		}

		return $result;
	}
}

