
function objectToMappable( obj, keyProp = '', valueProp = '' ) {
	if ( Array.isArray( obj ) ) {
		return obj;
	}

	let mappable = [];

	for ( const key in obj ) {
		if ( ! obj.hasOwnProperty( key ) ) {
			continue;
		}
		if ( valueProp ) {
			if ( 'object' !== typeof obj[ key ] ) {
				const value = obj[ key ];
				obj[ key ] = {};
				obj[ key ][ valueProp ] = value;
			}
		}
		if ( keyProp && ! obj[ key ].hasOwnProperty( keyProp ) ) {
			obj[ key ][ keyProp ] = key;
		}
		mappable.push( obj[ key ] );
	}

	return mappable;
}

export {
	objectToMappable
}
