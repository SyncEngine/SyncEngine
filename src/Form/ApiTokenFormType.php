<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyncEngine\Entity\ApiToken;
use SyncEngine\Entity\User;
use SyncEngine\Form\Type\JsonType;

class ApiTokenFormType extends AbstractType
{
	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$user = $options['user'] ?? null;
		$grantedScopes = $user instanceof User ? $user->getGrantedScopes( false ) : [];

		// Build choices array with labels for the scopes field.
		$choices = $this->buildScopeChoices( $grantedScopes );

		$builder
			->add( 'token', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
			->add( 'description', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
			->add( 'expires', DateTimeType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'widget'   => 'single_text',
			] )
			->add( 'config', JsonType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'attr' => [
					'data-controller' => 'react',
					'data-type'       => 'fields',
					'data-args'       => json_encode( [
						'fields' => [
							'_scopes' => [
								'label'       => 'Scopes',
								'icon'        => 'scope',
								'description' => 'Scopes define what this API token can access. Each scope grants a specific permission.',
								'fields' => [
									'scopes' => [
										'type'        => 'checkbox',
										'vertical'    => true,
										'inline'      => 'fixed',
										'choices'     => $choices,
									]
								]
							],
							'restrictions' => [
								'label'       => 'Restrictions',
								'icon'        => 'restrict',
								'description' => 'Restrictions limit an API key\'s usage and improves security',
								'nested'      => [
									'ip' => [
										'label'       => 'Restrict by IP addresses',
										'type'        => 'text',
										'placeholder' => '0.0.0.0, 0.0.0.0',
									],
									'host' => [
										'label'       => 'Restrict by host domains',
										'description' => 'The "origin" header can be spoofed, so this is more of a convenience check than a security measure.',
										'type'        => 'text',
										'placeholder' => 'domain.com, sub.domain.ext, *.wildcard.com',
									],
								]
							],
						],
					] ),
				]
			] );
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [
			'data_class' => ApiToken::class,
			'user'       => null,
		] );
	}

	/**
	 * Build choices array from granted scopes.
	 *
	 * Converts scope strings into {value, label} pairs with human-readable labels.
	 */
	private function buildScopeChoices( array $grantedScopes ): array
	{
		$choices = [];

		foreach ( $grantedScopes as $scope ) {
			if ( $scope === '*' ) {
				$choices[] = [ 'value' => '*', 'label' => 'Full Access (*)' ];
				continue;
			}

			$label = $this->formatScopeLabel( $scope );
			$choices[] = [ 'value' => $scope, 'label' => $label ];
		}

		return $choices;
	}

	/**
	 * Format a scope string into a human-readable label.
	 *
	 * 'automation:read' -> 'Automation: Read'
	 * 'automation:run'  -> 'Automation: Run'
	 */
	private function formatScopeLabel( string $scope ): string
	{
		$parts = explode( ':', $scope, 2 );
		if ( count( $parts ) !== 2 ) {
			return $scope;
		}

		$entity = str_replace( '-', ' ', $parts[0] );
		$action = str_replace( '-', ' ', $parts[1] );

		return ucfirst( $entity ) . ': ' . ucfirst( $action );
	}
}
