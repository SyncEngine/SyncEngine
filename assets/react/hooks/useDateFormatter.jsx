import useGlobal from './useGlobal';
import usePreference from './usePreference';
import { debug } from '../utils/globals';

/**
 * @returns {DateTimeFormat}
 */
export default function useDateFormatter() {
	const app = useGlobal();
	const [ language ] = usePreference( 'language' );
	const currentLanguage = language || 'en';

	if ( ! app.hasOwnProperty( '_dateFormatter' ) ) {
		app._dateFormatter = {};
	}

	let formatter = app._dateFormatter[ currentLanguage ];

	if ( ! app._dateFormatter[ currentLanguage ] ) {

		app._dateFormatter[ currentLanguage ] = {
			DateTimeFormat: Intl.DateTimeFormat(
				currentLanguage,
				{
					dateStyle: 'medium',
					timeStyle: 'medium',
				}
			)
		};

		formatter = app._dateFormatter[ currentLanguage ];

		formatter.format = ( ...args ) => {
			try {
				return formatter.DateTimeFormat.format( ...args );
			} catch ( e ) {
				debug( e );
			}
			return '--';
		}
	}


	return formatter;
}
