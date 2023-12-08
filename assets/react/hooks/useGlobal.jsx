/**
 * @param {string} name
 * @param {string} namespace
 */
export default function useGlobal( name = '', namespace = 'SyncEngine' ) {
	if ( name ) {
		return window[ namespace ].hasOwnProperty( name ) ? window[ namespace ][ name ] : null;
	}
	return window[ namespace ];
}
