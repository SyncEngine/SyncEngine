
function objectToMappable( obj, keyProp = '', valueProp = '', force = false ) {
	if ( Array.isArray( obj ) ) {
		return obj;
	}

	let mappable = [];

	for ( const key in obj ) {
		if ( ! obj.hasOwnProperty( key ) ) {
			continue;
		}
		if ( valueProp ) {
			if ( force || 'object' !== typeof obj[ key ] || Array.isArray( obj[ key ] ) ) {
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

function listRenameProp( list, oldName, newName, newDefault = null ) {
	let parsed = [];
	for ( const index in list ) {
		parsed[ index ] = { ...list[ index ] };
		if ( parsed[ index ].hasOwnProperty( oldName ) ) {
			parsed[ index ][ newName ] = parsed[ index ][ oldName ];
			continue;
		}
		if ( newDefault ) {
			parsed[ index ][ newName ] = newDefault;
		}
	}
	return parsed;
}

function mapGetProp( data, prop ) {
	return data.map( item => item[ prop ] );
}

function mapGetIndex( data, value, prop = '' ) {
	if ( prop ) {
		data = mapGetProp( data, prop );
	}
	return data.indexOf( value );
}

function mapFilter( data, filters ) {
	if ( ! data || ! filters ) {
		return data;
	}

	// Support key as second and value as third param.
	if ( 'object' !== typeof filters ) {
		if ( ! arguments[2] ) {
			return data;
		}
		let key = filters;
		filters = {};
		filters[ key ] = arguments[2];
	}

	return data.filter( ( value ) => {

		for ( const key in filters ) {
			if ( filters.hasOwnProperty( key ) && filters[ key ] ) {
				// Row has key of the selected filter.
				if ( ! value.hasOwnProperty( key ) ) {
					return false;
				}
				// Row value doesn't mach filter.
				if ( value[ key ] != filters[ key ] ) {
					return false;
				}
			}
		}

		return value;

	} );
}

function mapGroupBy( list, key, fallback ) {
	return list.reduce( function( rv, x ) {
		const group = x[key] || fallback || '';
		( rv[ group ] = rv[ group ] || [] ).push( x );
		return rv;
	}, {} );
}

function mapSortBy( list, key, desc ) {
	return list.sort( (a, b) => {
		let keyA    = a[key];
		let keyB    = b[key];
		let reverse = desc;
		if ( 'string' === typeof keyA ) {
			reverse = ! desc; // Reverse order for alphabetical.
			keyA = keyA.toUpperCase(); // ignore upper and lowercase
			keyB = keyB.toUpperCase(); // ignore upper and lowercase
		}
		if ( keyA < keyB ) {
			return ( reverse ) ? -1 : 1;
		}
		if ( keyA > keyB ) {
			return ( reverse ) ? 1 : -1;
		}

		// names must be equal
		return 0;
	} );
}

export {
	objectToMappable,
	objectKeyToProp,
	listRenameProp,
	mapGetProp,
	mapGetIndex,
	mapFilter,
	mapGroupBy,
	mapSortBy
}
