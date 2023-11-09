import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

export default i18n
	.use( initReactI18next )
	.init( () => {
		// https://medium.com/swlh/how-we-translated-a-symfony-and-react-project-67906b7d5b30
		const parseSymfonyFormat = ( object ) => {
			const newObject = {};
			Object.keys( object ).forEach( ( key ) => {
				let elem = object[ key ];
				if ( typeof elem === 'object' ) {
					newObject[ key ] = parseSymfonyFormat( elem );
					return;
				}
				// replace all symfony parameters %param% with {{param}} in all strings for js
				elem = String( elem ).replace( /%([^%]+(?=%))%/gi, '{{$1}}' );

				// splits all plurales like "one apple|many apples" into different keys apple and apple_plural
				if ( typeof elem === 'string' && elem.includes( '|' ) ) {
					const plural = elem.split( '|' );
					newObject[ key ] = plural.shift();
					newObject[ `${key}_plural` ] = plural.shift();

					return;
				}

				newObject[ key ] = elem;
			} );

			return newObject;
		};

		const language = window.app.preferences.language || 'en';
		const resources = {};

		// @todo Improve this, maybe cache parsed translations?
		if ( window.app.translations.hasOwnProperty( language ) ) {
			resources[ language ] = {
				translation: parseSymfonyFormat( window.app.translations[ language ] )
			};
		}

		return {
			resources: resources,
			lng: language,
			fallbackLng: language,
			partialBundledLanguages: true, // Load on demand: https://www.i18next.com/overview/api#addresourcebundle
			interpolation: {
				escapeValue: false,
				// react already safes from xss => https://www.i18next.com/translation-function/interpolation#unescape
			},
			// keySeparator: false, // don't count "." as separator
		};
	} );
