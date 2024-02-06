import useSettings from './useSettings';

/**
 * @param {string} namespace
 * @param {string} key
 * @param {*} initial
 * @param {boolean} json
 * @returns {*,function,boolean}
 */
export default function usePreference( key, namespace = '', initial = null, json = false ) {
	return useSettings( 'user', namespace, key, initial, json );
}
