import useSettings from './useSettings';

/**
 * @param {string} key
 * @param {string} namespace
 * @param {*} initial
 * @param {boolean} json
 * @returns {*,function,boolean}
 */
export default function usePreference( key, namespace = '', initial = null, json = false ) {
	return useSettings( 'user', namespace, key, initial, json );
}
