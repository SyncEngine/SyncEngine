<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyncEngine\Messenger\MessengerManager;

class ProcessManagerFormType extends AbstractType
{
	public function __construct( private readonly MessengerManager $manager ) {}

	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$builder
			->add('SYNCENGINE_MESSENGER_MANAGER', ChoiceType::class, [
				'label' => 'Manager',
				'required' => true,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => [
					'SyncEngine' => MessengerManager::MANAGER_INTERNAL,
					'Cron' => MessengerManager::MANAGER_CRON,
					'External' => MessengerManager::MANAGER_EXTERNAL,
				],
			]);

		if ( $this->manager->isExternal() ) {
			return;
		}

		$builder
			->add('SYNCENGINE_MESSENGER_WORKER_LIMIT', NumberType::class, [
				'label' => 'Worker limit',
				'required' => false,
				'attr' => [
					'placeholder' => 'Max workers allowed running at the same time.',
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_MESSENGER_WORKER_QUEUE_LIMIT', NumberType::class, [
				'label' => 'Worker Queue limit',
				'required' => false,
				'attr' => [
					'placeholder' => 'Max queued items a single worker will handle.',
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_MESSENGER_WORKER_TIME_LIMIT', NumberType::class, [
				'label' => 'Worker Time limit',
				'required' => false,
				'attr' => [
					'placeholder' => 'Max seconds a single worker will run.',
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_MESSENGER_WORKER_MEMORY_LIMIT', NumberType::class, [
				'label' => 'Worker Memory limit',
				'required' => false,
				'attr' => [
					'placeholder' => 'Max memory (MB) a single worker is allowed to use.',
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			]);
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [] );
	}
}
