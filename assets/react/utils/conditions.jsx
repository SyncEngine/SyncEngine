import { useTranslation } from 'react-i18next';

function getOperators() {
	const { t } = useTranslation();
	return {
		'===': '===',
		'!==': '!==',
		'==': '==',
		'!=': '!=',
		'>=': '>=',
		'<=': '<=',
		'>': '>',
		'<': '<',
		'in': t('in/contains'),
		'not': t('not in/contains'),
		'in_strict': t('in/contains (strict)'),
		'not_strict': t('not in/contains (strict)'),
		'has_key': t('has key'),
		'not_has_key': t('not has key'),
		'set': t('is set'),
		'not_set': t('not set'),
		'empty': t('is empty'),
		'not_empty': t('not empty'),
	}
}


function validate( conditions, data ) {
	let valid = true;
	if ( conditions && Object.keys( conditions ).length ) {
		for ( let key in conditions ) {
			if ( ! valid ) {
				break;
			}

			const condition = conditions[ key ];

			let compare = ( condition && condition.hasOwnProperty( 'compare' ) ) ? condition.compare : condition,
				operator = ( condition && condition.hasOwnProperty( 'operator' ) ) ? condition.operator : null;

			if ( ! operator ) {
				if ( null === compare || false === compare || '' === compare ) {
					operator = 'empty';
				} else if ( true === compare ) {
					operator = 'not_empty';
				} else {
					operator = ( 'object' === typeof compare ) ? 'in' : 'default';
				}
			}

			switch ( operator ) {
				case 'isset':
				case 'set':
					valid = data.hasOwnProperty( key );
					break;
				case 'notset':
				case 'not_set':
					valid = ! data.hasOwnProperty( key );
					break;
				case 'empty':
					valid = ! data.hasOwnProperty( key ) || isEmpty( data[ key ] );
					break;
				case 'notempty':
				case 'not_empty':
					valid = data.hasOwnProperty( key ) && ! isEmpty( data[ key ] );
					break;
				case 'in':
				case 'contains':
					valid = data.hasOwnProperty( key ) && hasValue( compare, data[ key ] );
					break;
				case 'not':
				case 'not_contains':
					valid = data.hasOwnProperty( key ) && ! hasValue( compare, data[ key ] );
					break;
				case 'haskey':
				case 'has_key':
					valid = data.hasOwnProperty( key ) && data[ key ].hasOwnProperty( compare );
					break;
				case 'nothaskey':
				case 'not_has_key':
					valid = ! data.hasOwnProperty( key ) || ! data[ key ].hasOwnProperty( compare );
					break;
				case '<':
					valid = compare < data[ key ];
					break;
				case '>':
					valid = compare > data[ key ];
					break;
				case '<=':
					valid = compare <= data[ key ];
					break;
				case '>=':
					valid = compare >= data[ key ];
					break;
				case '!=':
					valid = compare != data[ key ];
					break;
				case '==':
					valid = compare == data[ key ];
					break;
				case '!==':
					valid = compare !== data[ key ];
					break;
				case '===':
				default:
					valid = compare === data[ key ];
					break;
			}
		}
	}

	return valid;
}

/**
 * Wrapper for hasOwnProperty to validate the object and key.
 * @param {array} obj
 * @param {*} key
 * @return {*}
 */
function hasKey( obj, key ) {
	if ( isEmpty( obj ) ) {
		return false;
	}
	if ( 'object' !== typeof obj ) {
		return false;
	}
	// Also works on arrays.
	return obj.hasOwnProperty( key );
}

/**
 * If value is an object|array it will check if -any- value matches.
 * @param {array} obj
 * @param {*} value
 * @return {*}
 */
function hasValue( obj, value ) {
	if ( null !== value && 'object' === typeof value ) {
		if ( isArray( value ) ) {
			return value.some( ( val ) => obj.includes( val ) );
		}
		return Object.values( value ).some( ( val ) => obj.includes( val ) );
	}
	return obj.includes( value );
}

function isEmpty( value ) {
	switch ( typeof value ) {
		case 'string':
			return ! value;
		case 'boolean':
			return ! value;
		case 'number':
		case 'bigint':
			return 0 === value;
		case 'object':
			if ( null === value ) {
				return true;
			} else if ( Array.isArray( value ) ) {
				return 0 === value.length;
			} else {
				return 0 === Object.keys( value ).length;
			}
		case 'function':
		case 'symbol':
			return false;
		case 'undefined':
		default:
			return true;
	}
}

function isSet( value ) {
	switch ( typeof value ) {
		case 'string':
		case 'boolean':
		case 'number':
		case 'bigint':
			return true;
		case 'object':
			if ( null === value ) {
				return false;
			} else if ( Array.isArray( value ) ) {
				return 0 < value.length;
			} else {
				return 0 < Object.keys( value ).length;
			}
		case 'function':
		case 'symbol':
			return false;
		case 'undefined':
		default:
			return false;
	}
}

/**
 * @link https://stackoverflow.com/a/21696585
 * @param {object} element
 * @returns {boolean}
 */
function isHidden( element ) {
	return ( element.offsetParent === null );
}

function isObject( variable ) {
	return 'object' === typeof variable && ! Array.isArray( variable ) && null !== variable;
}

function isPromise( variable ) {
	return variable instanceof Promise;
}

function isArray( variable ) {
	return Array.isArray( variable );
}

function isString( variable ) {
	return 'string' === typeof variable;
}

export {
	getOperators,
	validate,
	hasKey,
	hasValue,
	isEmpty,
	isSet,
	isHidden,
	isObject,
	isPromise,
	isArray,
	isString,
}
