/**
 * @param {string} prefix
 * @param {string} postfix
 * @returns {string}
 */
function createRefId( prefix = '', postfix = '' ) {
	return prefix + Date.now().toString( 36 ) + Math.random().toString( 36 ).substr( 2 ) + postfix;
}

/**
 * @param {*} val
 * @return {null|number}
 */
function parseId( val ) {
	if ( 'object' === typeof val && val !== null && val.hasOwnProperty( 'id' ) ) {
		val = val.id;
	}
	return ( isNaN( val ) || ! val ) ? null : parseInt( val, 10 );
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
		try {
			str = decodeURI( str );
		} catch ( e ) {
			// decodeURI can throw on invalid URI sequences
		}

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

function round( number, decimals = 0 ) {
	if ( decimals === 0 ) {
		return Math.round( number );
	}
	const exp = decimals >= 0 ? 'e+' + decimals : 'e' + decimals;
	const expBack = decimals >= 0 ? 'e-' + Math.abs(decimals) : 'e+' + Math.abs(decimals);
	return + ( Math.round( number + exp ) + expBack );
}

function sleep( timeout ) {
	return new Promise(r => setTimeout(r, timeout));
}

function debug( message, context = undefined ) {
	if ( window.SyncEngine.debug ) {
		console.log( message, context );
	}
}

export {
	createRefId,
	parseId,
	ucfirst,
	sprintf,
	round,
	sleep,
	debug,
};
