import useGlobal from './useGlobal';
import usePreference from './usePreference';
import { debug } from '../utils/globals';

/**
 * @returns {DateTimeFormat}
 */
export default function useDateFormatter() {
	const app = useGlobal();
	const [ locale ] = usePreference( 'locale' );

	let currentLocale = locale ? locale.replaceAll( '_', '-' ) : 'en';

	if ( 5 < currentLocale.length ) {
		// Unknown locale format, revert to two letter locale.
		currentLocale = currentLocale.split('-');
		currentLocale = currentLocale[ 0 ] + '-' + currentLocale[ currentLocale.length - 1 ];
	}

	if ( ! app.hasOwnProperty( '_dateFormatter' ) ) {
		app._dateFormatter = {};
	}

	let formatter = app._dateFormatter[ currentLocale ];

	if ( ! app._dateFormatter[ currentLocale ] ) {

		app._dateFormatter[ currentLocale ] = {
			DateTimeFormat: Intl.DateTimeFormat(
				currentLocale,
				{
					dateStyle: 'medium',
					timeStyle: 'medium',
				}
			)
		};

		formatter = app._dateFormatter[ currentLocale ];

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
