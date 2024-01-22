<?php

namespace SyncEngine\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class Mailer
{
	public function __construct(
		protected readonly LoggerInterface $syncengineLogger,
		protected MailerInterface $mailer,
		#[Autowire( 'syncengine.mailer.sender' )]
		protected readonly string $sender,
		#[Autowire( 'syncengine.mailer.email_admin' )]
		protected readonly string $logEmailAddress
	) {}

	public function logger(): LoggerInterface
	{
		return $this->syncengineLogger;
	}

	public function sendEmail( string $message, $toEmail = null ): bool
	{
		$to = ! empty( $toEmail ) ? $toEmail : $this->logEmailAddress;

		$email = ( new Email() )->from( new Address( $this->sender, 'SyncEngine' ) )
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
