
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

function mapGroupBy(list, key, fallback) {
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

export {
	objectToMappable,
	objectKeyToProp,
	mapGroupBy
}
