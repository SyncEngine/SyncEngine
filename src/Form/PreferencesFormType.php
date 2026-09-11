<?php

namespace SyncEngine\Form;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
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
		#[Autowire( '%syncengine.dir.translations%' )]
		private string $transDir = __DIR__ . '/../../translations',
	) {}

	public function buildForm( FormBuilderInterface $builder, array $options ): void
	{
		$localeChoices = [];

		$finder = new Finder();
		$coreTranslations = $finder->in($this->transDir)->files()->name( 'messages*');
		$availableLocales = [];

		foreach ( $coreTranslations as $file ) {
			if ( preg_match( '/^messages(?:\+intl-icu)?\.([^.]+)\./', $file->getFilename(), $matches ) ) {
				$availableLocales[ $matches[1] ] = true;
			}
		}

		foreach ( Locales::getNames() as $locale => $name ) {
			// Fix this if statement to check if the locale is in the core translations.
			// File format is messages.{locale}.{ext} or messages+intl-icu.{locale}.{ext}.
			if ( isset( $availableLocales[ $locale ] ) ) {
				$label = $name . ' (' . $locale . ')';
				$localeChoices[ $label ] = $locale;
			}
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
