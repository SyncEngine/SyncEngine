
function objectToMappable( obj, keyProp = '' ) {
	if ( Array.isArray( obj ) ) {
		return obj;
	}

	let mappable = [];

	for ( const key in obj ) {
		if ( ! obj.hasOwnProperty( key ) ) {
			continue;
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
