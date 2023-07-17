<?php

namespace App\Form;

use App\Entity\Dataset;
use App\Form\Type\JsonType;
use App\Model\DatasetModel;
use App\Service\ModuleService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatasetFormType extends AbstractType
{
	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$modules = ModuleService::getModules();
		foreach ( $modules as $key => $module ) {
			$modules[ $module->getName() ] = $module->getName();
			unset( $modules[ $key ] );
		}

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
			->add('type', ChoiceType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => [
					'Generic / Other' => '',
					'Entities' => 'entities',
					'Mapper' => 'mapper',
				],
			] )
			->add('module', ChoiceType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => $modules,
			] )
			->add( 'config', JsonType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'attr' => [
					'data-controller' => 'react',
					'data-type'       => 'config',
					'data-args'       => json_encode( [
						'fields' => DatasetModel::getFields(),
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
