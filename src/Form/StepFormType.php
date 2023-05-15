<?php

namespace App\Form;

use App\Entity\Step;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StepFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
			->add('name', TextType::class)
			->add('description', TextType::class)
            ->add('config', JsonType::class, [
	            'attr' => [
		            'data-controller' => 'config',
		            'data-type'       => 'step',
		            'data-args'       => json_encode( [
						'taskTypes' => [
							( new \App\Controller\Task\Mapper() )->getArgs(),
							( new \App\Controller\Task\Splitter() )->getArgs(),
							( new \App\Controller\Task\Merger() )->getArgs(),
						]
		            ] ),
	            ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Step::class,
        ]);
    }
}
