<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyncEngine\Entity\Connection;
use SyncEngine\Form\Type\JsonType;
use SyncEngine\Model\ConnectionModel;

class ConnectionFormType extends AbstractType
{
	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$model = $options['model'];

		if ( ! isset( $options['model'] ) ) {
			$model = ConnectionModel::create();
		}

		if ( ! $options['model'] instanceof ConnectionModel ) {
			throw new \InvalidArgumentException( 'The "model" option must be an instance of ConnectionModel.' );
		}

		$builder
			->add( 'name', TextType::class, [
				'required' => true,
				'row_attr' => [
					'col-row-group' => 'name-desc',
					'col-class'     => 'col-12 col-lg-6',
					'class'         => 'form-floating mb-3',
				],
			] )
			->add( 'description', TextType::class, [
				'required' => false,
				'row_attr' => [
					'col-row-group' => 'name-desc',
					'col-class'     => 'col-12 col-lg-6',
					'class'         => 'form-floating mb-3',
				],
			] )
			->add( 'config', JsonType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'attr' => [
					'data-controller' => 'react',
					'data-type'       => 'config',
					'data-args'       => json_encode( [
						'fields' => $model->getFields()->normalize(),
						'tags'   => [],
					] ),
				]
			] );
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [
			'data_class' => Connection::class,
			'model'      => null,
		] );
	}
}
