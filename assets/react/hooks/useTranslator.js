import { trans } from '../../translator';

/**
 * useTranslator hook.
 * Wraps Symfony UX Translator's trans() function to provide a similar API to react-i18next's useTranslation().
 *
 * This allows easy migration from i18next while leveraging Symfony's
 * translation infrastructure.
 */
export function useTranslator( domain = 'frontend' ) {
	return {
		t: ( key, params = {} ) => trans( key, params, domain ),
	};
}
