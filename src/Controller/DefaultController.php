<?php

namespace App\Controller;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
	protected $security = null;

	public function __construct( Security $security ) {
		$this->security = $security;
	}

	public function slugify($text)
	{
		// replace non letter or digits by -
		$text = preg_replace('~[^\pL\d]+~u', '-', $text);

		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		// trim
		$text = trim($text, '-');

		// remove duplicated - symbols
		$text = preg_replace('~-+~', '-', $text);

		// lowercase
		$text = strtolower($text);

		if (empty($text)) {
			return 'n-a';
		}
		return $text;
	}

	public function render( string $view, array $parameters = [], Response $response = null ): Response {
		if ( ! isset( $parameters['user'] ) && $this->security->getUser() ) {
			$parameters['user'] = $this->security->getUser()->getUserIdentifier();
		}

		return parent::render( $view, $parameters, $response );
	}
}
