<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Messenger\MessengerManager;

class ProcessManagerFormType extends AbstractType
{
	public function __construct(
		private readonly MessengerManager $manager,
		private readonly TranslatorInterface $translator,
	) {}

	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$builder
			->add('SYNCENGINE_MESSENGER_MANAGER', ChoiceType::class, [
				'label' => $this->translator->trans( 'Manager' ),
				'required' => true,
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => [
					'SyncEngine' => MessengerManager::MANAGER_INTERNAL, // Do not translate, this is the project brand.
					'Cron' => MessengerManager::MANAGER_CRON, // Do not translate, this is a system name.
					$this->translator->trans( 'External' ) => MessengerManager::MANAGER_EXTERNAL,
				],
			]);

		if ( $this->manager->isExternal() ) {
			return;
		}

		$builder
			->add('SYNCENGINE_MESSENGER_WORKER_LIMIT', NumberType::class, [
				'label' => $this->translator->trans( 'Worker limit' ),
				'required' => false,
				'attr' => [
					'placeholder' => $this->translator->trans( 'Max workers allowed running at the same time.' ),
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_MESSENGER_WORKER_QUEUE_LIMIT', NumberType::class, [
				'label' => $this->translator->trans( 'Worker Queue limit' ),
				'required' => false,
				'attr' => [
					'placeholder' => $this->translator->trans( 'Max queued items a single worker will handle.' ),
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_MESSENGER_WORKER_TIME_LIMIT', NumberType::class, [
				'label' => $this->translator->trans( 'Worker Time limit' ),
				'required' => false,
				'attr' => [
					'placeholder' => $this->translator->trans( 'Max seconds a single worker will run.' ),
				],
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
			])
			->add('SYNCENGINE_MESSENGER_WORKER_MEMORY_LIMIT', NumberType::class, [
				'label' => $this->translator->trans( 'Worker Memory limit' ),
				'required' => false,
				'attr' => [
					'placeholder' => $this->translator->trans( 'Max memory (MB) a single worker is allowed to use.' ),
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
