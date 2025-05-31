<?php

namespace SyncEngine\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyncEngine\Service\Preferences;

class PreferencesFormType extends AbstractType
{
	public function __construct(
		private readonly Preferences $preferences,
	) {}

	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$localeChoices = [];

		foreach ( Locales::getNames() as $locale => $name ) {
			$label = $name . ' (' . $locale . ')';
			$localeChoices[ $label ] = $locale;
		}

		$builder
			->add('locale', ChoiceType::class, [
				'row_attr' => [
					'class' => 'form-floating mb-3',
				],
				'choices' => $localeChoices,
			]);
	}

	public function configureOptions( OptionsResolver $resolver ): void
	{
		$resolver->setDefaults( [
			'data' => [
				'locale' => $this->preferences->get('locale'),
			],
		] );
	}
}
