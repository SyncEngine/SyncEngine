import useStorage from './useStorage';

/**
 * @param {string} key
 * @param {*} initial
 * @param {boolean} json
 * @returns {*,function,boolean}
 */
export default function useClipboard( key, initial = null, json = true ) {
	return useStorage( 'session', 'clipboard', key, initial, json );
}
