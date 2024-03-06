import useGlobal from './useGlobal';
import usePreference from './usePreference';

/**
 * @returns {DateTimeFormat}
 */
export default function useDateFormatter() {
	const app = useGlobal();
	const [ language ] = usePreference( 'language' );

	if ( ! app.hasOwnProperty( '_dateFormatter' ) ) {
		app._dateFormatter = Intl.DateTimeFormat(
			language || 'en',
			{
				dateStyle: 'medium',
				timeStyle: 'medium',
			}
		);
	}

	return app._dateFormatter;
}
