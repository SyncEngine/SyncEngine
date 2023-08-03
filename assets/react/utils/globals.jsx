/**
 * @param {string} prefix
 * @param {string} postfix
 * @returns {string}
 */
function createRefId( prefix = '', postfix = '' ) {
	return prefix + Date.now().toString( 36 ) + Math.random().toString( 36 ).substr( 2 ) + postfix;
}

/**
 * @param {string} str
 * @returns {string}
 */
function ucfirst( str ) {
	return str.charAt( 0 ).toUpperCase() + str.slice( 1 );
}

/**
 * @param {string} str
 * @returns {string}
 */
function sprintf( str ) {
	let args = Array.prototype.slice.call( arguments, 1 );

	if ( args.length ) {
		str = decodeURI( str );

		let t = typeof args[ 0 ];
		let key;
		args = (
			'string' === t || 'number' === t
		) ? Array.prototype.slice.call( args ) : args[ 0 ];

		for ( key in args ) {
			if ( 'object' === typeof args[ key ] ) {
				continue;
			}
			str = str.replace( new RegExp( '\\{' + key + '\\}', 'gi' ), args[ key ] );
		}
	}

	return str;
}

function parseTag( tag, object ) {
	const parts = tag.split( '.' );

	for ( const index in parts ) {
		if ( ! object.hasOwnProperty( parts[ index ] ) ) {
			return '';
		}
		object = object[ parts[ index ] ];
	}
	return object;
}

export {
	createRefId,
	ucfirst,
	sprintf,
	parseTag,
};
