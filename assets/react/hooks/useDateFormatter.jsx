import useGlobal from './useGlobal';
import usePreference from './usePreference';
import { debug } from '../utils/globals';

function normalizeDateTimeFormatOptions( options = {}, { skipUndefined = true } = {} ) {
	const validKeys = [
		'localeMatcher', 'calendar', 'numberingSystem',
		'timeZone', 'hour12', 'hourCycle',
		'formatMatcher', 'weekday', 'era',
		'year', 'month', 'day',
		'hour', 'minute', 'second',
		'fractionalSecondDigits', 'dayPeriod',
		'timeZoneName', 'dateStyle', 'timeStyle',
	];

	const result = {};
	for ( const key of validKeys ) {
		if ( key in options ) {
			const value = options[ key ];
			if ( value !== undefined || ! skipUndefined ) {
				result[ key ] = value;
			}
		}
	}

	return result;
}

/**
 * @returns {DateTimeFormat}
 */
export default function useDateFormatter( options = null ) {
	const app = useGlobal();
	const [ locale ] = usePreference( 'locale' );

	let currentLocale = locale ? locale.replaceAll( '_', '-' ) : 'en';

	if ( 5 < currentLocale.length ) {
		// Unknown locale format, revert to two letter locale.
		currentLocale = currentLocale.split( '-' );
		currentLocale = currentLocale[ 0 ] + '-' + currentLocale[ currentLocale.length - 1 ];
	}

	if ( ! app.hasOwnProperty( '_dateFormatter' ) ) {
		app._dateFormatter = {};
	}

	if ( ! app._dateFormatter[ currentLocale ] ) {
		app._dateFormatter[ currentLocale ] = {};
	}

	const localeFormatter = app._dateFormatter[ currentLocale ];

	let format = 'dateStyle:medium;timeStyle:short';
	if ( ! options ) {
		options = {
			dateStyle: 'medium',
			timeStyle: 'short',
		};
	} else {
		options = normalizeDateTimeFormatOptions( options, { skipUndefined: true } );
		format  = Object.entries( options ).map( ( [ key, value ] ) => `${ key }:${ value }` ).join( ';' );
	}

	let formatter = localeFormatter[ format ];

	if ( ! formatter ) {

		localeFormatter[ format ] = { DateTimeFormat: Intl.DateTimeFormat( currentLocale, options ) };

		formatter = localeFormatter[ format ];

		formatter.format = ( ...args ) => {
			try {
				return formatter.DateTimeFormat.format( ...args );
			} catch ( e ) {
				debug( e );
			}
			return '--';
		};
	}


	return formatter;
}
