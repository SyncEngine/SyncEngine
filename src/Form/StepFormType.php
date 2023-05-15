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
		$taskTypes = [];
		foreach ( [
			( new \App\Controller\Task\Mapper() ),
			( new \App\Controller\Task\Splitter() ),
			( new \App\Controller\Task\Merger() )
		] as $task ) {
			$taskTypes[ $task->getType() ] = $task->getArgs();
		}

        $builder
			->add('name', TextType::class)
			->add('description', TextType::class)
            ->add('config', JsonType::class, [
	            'attr' => [
		            'data-controller' => 'config',
		            'data-type'       => 'step',
		            'data-args'       => json_encode( [
						'taskTypes' => $taskTypes
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
