function createRefId( prefix = '', postfix = '' ) {
	return prefix + Date.now().toString( 36 ) + Math.random().toString( 36 ).substr( 2 ) + postfix;
}

function ucfirst( str ) {
	return str.charAt( 0 ).toUpperCase() + str.slice( 1 );
}

function sprintf( str ) {
	let args = Array.prototype.slice.call( arguments, 1 );

	if ( args.length ) {
		str = decodeURI( str );

		let t = typeof args[ 0 ];
		let key;
		args = ( 'string' === t || 'number' === t ) ? Array.prototype.slice.call( args ) : args[ 0 ];

		for ( key in args ) {
			if ( 'object' === typeof args[ key ] ) {
				continue;
			}
			str = str.replace( new RegExp( '\\{' + key + '\\}', 'gi' ), args[ key ] );
		}
	}

	return str;
}

export {
	createRefId,
	ucfirst,
	sprintf,
};
