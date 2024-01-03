<?php

namespace SyncEngine\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class Mailer
{
	public function __construct(
		protected readonly LoggerInterface $syncengineLogger,
		protected MailerInterface $mailer,
		string $logEmailAddress
	) {
		$this->logEmailAddress = $logEmailAddress;
	}

	public function logger(): LoggerInterface
	{
		return $this->syncengineLogger;
	}

	public function sendEmail(string $message, $toEmail = null): bool
	{
		$to = !empty($toEmail) ? $toEmail : $this->logEmailAddress;

		$email = ( new Email() )
			->from( 'test@syncengine.io' )
			->to( $to )
			->subject( 'SyncEngine notification' )
			->text( $message )
			->html( $message );
		try {
			$this->mailer->send( $email );
		} catch ( TransportExceptionInterface $e ) {
			$this->logger()->error( 'Unable to send email.', [ $e ] );
			return false;
		}

		return true;
	}
}
