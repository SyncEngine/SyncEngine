
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
			if ( 'object' !== typeof obj[ key ] || Array.isArray( obj[ key ] ) ) {
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

function objectKeyToProp( obj, keyProp ) {
	let parsed = {...obj};
	for ( const key in obj ) {
		if ( ! obj.hasOwnProperty( key ) ) {
			continue;
		}
		if ( ! obj[ key ].hasOwnProperty( keyProp ) ) {
			obj[ key ][ keyProp ] = key;
		}
	}
	return parsed;
}

function mapGroupBy( list, key, fallback ) {
	list = list.reduce(function(rv, x) {
		(rv[x[key]] = rv[x[key]] || []).push(x);
		return rv;
	}, {});
	if ( fallback && list.hasOwnProperty( 'null' ) ) {
		list[ fallback ] = list[ 'null' ];
		delete list[ 'null' ];
	}
	return list;
}

function mapSortBy( list, key, desc ) {
	return list.sort( (a, b) => {
		let keyA    = a[key];
		let keyB    = b[key];
		let reverse = desc;
		if ( 'string' === typeof a[key] ) {
			reverse = ! desc; // Reverse order for alphabetical.
			keyA = keyA.toUpperCase(); // ignore upper and lowercase
			keyB = keyB.toUpperCase(); // ignore upper and lowercase
		}
		if (keyA < keyB) {
			return ( reverse ) ? -1 : 1;
		}
		if (keyA > keyB) {
			return ( reverse ) ? 1 : -1;
		}

		// names must be equal
		return 0;
	} );
}

export {
	objectToMappable,
	objectKeyToProp,
	mapGroupBy,
	mapSortBy
}
