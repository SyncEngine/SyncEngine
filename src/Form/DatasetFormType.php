<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyncEngine\Entity\Dataset;
use SyncEngine\Form\Type\JsonType;
use SyncEngine\Model\DatasetModel;

class DatasetFormType extends AbstractType
{
	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$builder
			->add('name', TextType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
			->add('description', TextType::class, [
				'required' => false,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			] )
			/*->add('type', ChoiceType::class, [
				'required' => false,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => DatasetModel::getTypes(),
			] )*/
			->add( 'config', JsonType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'attr' => [
					'data-controller' => 'react',
					'data-type'       => 'config',
					'data-args'       => json_encode( [
						'fields' => DatasetModel::create()->getFields(),
						'tags'   => [],
					] ),
				]
			] )
			->add( 'data', JsonType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'attr' => [
					'data-controller' => 'react',
					'data-type'       => 'dataset',
				]
			] );
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [
			'data_class' => Dataset::class,
		] );
	}
}
