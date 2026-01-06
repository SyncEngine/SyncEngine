import React from 'react';
import { isObject } from './conditions';

/**
 * Create a new object and remove all old references.
 * @see https://github.com/nodejs/node/issues/50320
 * @param {{}|[]} obj
 * @param {{}} options
 * @return {{}}
 */
function deepClone( obj, options = { cloneReactElement: false } ) {
	if ( ! obj || 'object' !== typeof obj ) {
		return obj;
	}

	// https://jsperf.com/deep-copy-vs-json-stringify-json-parse/25
	if ( Array.isArray( obj ) ) {
		const newO = [];
		for ( let i = 0; i < obj.length; i += 1 ) {
			const val = ! obj[i] || typeof obj[i] !== 'object' ? obj[i] : deepClone( obj[i] );
			newO[i] = val === undefined ? null : val;
		}
		return newO;
	}

	if ( React.isValidElement( obj ) ) {
		return options.cloneReactElement ? React.cloneElement( obj ) : obj;
	}

	const newO = {};
	for ( const i of Object.keys( obj ) ) {
		const val = ! obj[i] || typeof obj[i] !== 'object' ? obj[i] : deepClone( obj[i] );
		if ( val === undefined ) {
			continue;
		}
		newO[i] = val;
	}
	return newO;
}

/**
 * Convert an object to a mappable array.
 * @param {{}|[]} obj
 * @param {string|number} keyProp
 * @param {string|number} valueProp
 * @param {boolean} force
 * @return {[{}]}
 */
function objectToMappable( obj, keyProp = '', valueProp = '', force = false ) {
	if ( Array.isArray( obj ) ) {
		return obj.map( ( row ) => {
			if ( keyProp && 'object' !== typeof row ) {
				row = {
					[ keyProp ]: row,
				};
				if ( valueProp ) {
					row[ valueProp ] = row[ keyProp ];
				}
			}
			return row;
		} );
	}

	let mappable = [];

	for ( const key in obj ) {
		if ( ! obj.hasOwnProperty( key ) ) {
			continue;
		}
		if ( valueProp ) {
			if ( force || 'object' !== typeof obj[ key ] || Array.isArray( obj[ key ] ) || React.isValidElement( obj[ key ] ) ) {
				const value = obj[ key ];
				obj[ key ] = {};
				obj[ key ][ valueProp ] = value;
			}
		}
		if ( keyProp ) {
			if ( ! obj[ key ] ) {
				obj[ key ] = {};
			}
			if ( ! obj[ key ].hasOwnProperty( keyProp ) ) {
				obj[ key ][ keyProp ] = key;
			}
		}
		mappable.push( obj[ key ] );
	}

	return mappable;
}

/**
 * Add the objects key as a property of the objects value.
 * @param {{}} obj
 * @param {string|number} keyProp
 * @return {{}}
 */
function objectKeyToProp( obj, keyProp ) {
	let parsed = { ...obj };
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

/**
 * Merge object with reference.
 * @param {{}} target
 * @param {[{}]}sources
 * @return {{}}
 */
function objectMerge( target, ...sources ) {
	return objectMergeDepth( target, -1, ...sources );
}

/**
 * Merge object with reference to a fixed depth.
 * @param {{}}target
 * @param {int} depth
 * @param {[{}]} sources
 * @return {{}}
 */
function objectMergeDepth( target, depth, ...sources ) {
	if ( ! sources.length ) return target;

	if ( -1 !== depth ) {
		if ( 1 > depth ) {
			return target;
		}
		depth--;
	}

	const source = sources.shift();

	if ( isObject( target ) && isObject( source ) ) {
		for ( const key in source ) {
			if ( isObject( source[ key ] ) ) {
				if ( ! isObject( target[ key ] ) || ! depth ) {
					target[ key ] = source[ key ];
				} else {
					objectMergeDepth( target[ key ], depth, source[ key ] );
				}
			} else {
				target[ key ] = source[ key ];
			}
		}
	}

	return objectMergeDepth( target, depth, ...sources );
}

/**
 * Filter an object with a callback, works just like an array filter.
 * @param {{}} obj
 * @param {function} callback
 * @returns {{}}
 */
function objectFilter( obj, callback ) {
	const filtered = {};

	for ( let key in obj ) {
		if ( obj.hasOwnProperty( key ) && callback( obj[ key ], key ) ) {
			filtered[ key ] = obj[ key ];
		}
	}

	return filtered;
}

/**
 * Rename the property of a list of objects.
 * @param {[]|{}} list
 * @param {string|number} oldName
 * @param {string|number} newName
 * @param {*} newDefault
 * @return {*[]}
 */
function listRenameProp( list, oldName, newName, newDefault ) {
	let parsed = [];
	for ( const index in list ) {
		parsed[ index ] = { ...list[ index ] };
		if ( parsed[ index ].hasOwnProperty( oldName ) ) {
			parsed[ index ][ newName ] = parsed[ index ][ oldName ];
			continue;
		}
		if ( 'undefined' !== typeof newDefault ) {
			parsed[ index ][ newName ] = newDefault;
		}
	}
	return parsed;
}

/**
 * Return a single property of a mappable array containing objects.
 * @param {[]} data
 * @param {string|number} prop
 * @return {*[]}
 */
function mapGetProp( data, prop ) {
	return data.map( item => item[ prop ] );
}

/**
 * Get the index of a map value.
 * Can also return the index of a map containing objects using an objects property.
 * @param {[]} data
 * @param {*} value
 * @param {string|number} prop
 * @return {number}
 */
function mapGetIndex( data, value, prop = '' ) {
	if ( prop ) {
		data = mapGetProp( data, prop );
	}
	return data.indexOf( value );
}

/**
 * Find the item in a list by its property value.
 * @param {[]} data
 * @param {*} value
 * @param {string|number} prop
 * @param {*} fallback
 * @return {*}
 */
function mapFind( data, value, prop = '', fallback = null ) {
	return data.find( item => prop ? item[ prop ] === value : item === value ) || fallback;
}

/**
 * Filter a mappable array containing objects by the objects key.
 * @param {[{}]} data
 * @param {[]|string} filters An array of key>value pairs, or a key (in which case you can use a 3rd param for the value).
 * @return {[{}]}
 */
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

/**
 * Group a mappable array containing objects by the objects key.
 * @param {[{}]} list
 * @param {string|number} key
 * @param {string|number} fallback Fallback group.
 * @return {{}}
 */
function mapGroupBy( list, key, fallback ) {
	return list.reduce( function( rv, x ) {
		const group = x[key] || fallback || '';
		( rv[ group ] = rv[ group ] || [] ).push( x );
		return rv;
	}, {} );
}

/**
 * Sort a mappable array containing objects by the objects key.
 * @param {[{}]} list
 * @param {string|number} key
 * @param {boolean} desc Sort order
 * @return {[{}]}
 */
function mapSortBy( list, key, desc ) {
	return list.sort( ( a, b ) => {
		let keyA = a[ key ];
		let keyB = b[ key ];
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
	deepClone,
	objectToMappable,
	objectKeyToProp,
	objectMerge,
	objectMergeDepth,
	objectFilter,
	listRenameProp,
	mapGetProp,
	mapGetIndex,
	mapFind,
	mapFilter,
	mapGroupBy,
	mapSortBy
}
