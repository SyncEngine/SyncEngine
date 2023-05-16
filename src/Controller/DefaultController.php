<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
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

	public function __get( string $property )
	{
		$getter = array( $this, 'get_' . $property );
		if ( is_callable( $getter ) ) {
			return call_user_func( $getter );
		}
		return $this->$property ?? null;
	}

	function classes_in_namespace($namespace) {
		$namespace .= '\\';
		$Classes  = array_filter(get_declared_classes(), function($item) use ($namespace) { return substr($item, 0, strlen($namespace)) === $namespace; });
		$theClasses = [];
		foreach ($Classes AS $class){
			$theClasses[] = $class;
		}
		return $theClasses;
	}
}
