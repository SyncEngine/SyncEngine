import { useTranslation } from 'react-i18next';

export const OPERATOR_SET              = 'set'; //'?';
export const OPERATOR_NOT_SET          = 'not_set'; //'!?';
export const OPERATOR_EMPTY            = 'empty'; //'!';
export const OPERATOR_NOT_EMPTY        = 'not_empty'; //'!!';
export const OPERATOR_IN               = 'in'; //'@'; // '∈'
export const OPERATOR_NOT_IN           = 'not_in'; //'!@'; // '∉'
export const OPERATOR_IN_STRICT        = 'in_strict'; //'@='; // '∋'
export const OPERATOR_NOT_IN_STRICT    = 'not_in_strict'; //'!@='; // '∌'
export const OPERATOR_HAS_KEY          = 'has_key'; //'#';
export const OPERATOR_NOT_HAS_KEY      = 'not_has_key'; //'!#';
export const OPERATOR_LESSER           = '<';
export const OPERATOR_GREATER          = '>';
export const OPERATOR_LESSER_OR_EQUAL  = '<=';
export const OPERATOR_GREATER_OR_EQUAL = '>=';
export const OPERATOR_EQUAL            = '==';
export const OPERATOR_NOT_EQUAL        = '!=';
export const OPERATOR_EQUAL_STRICT     = '===';
export const OPERATOR_NOT_EQUAL_STRICT = '!==';
export const OPERATOR_REGEX            = 'regex';

const OPERATORS = {};

function getOperators() {
	const { t } = useTranslation();

	if ( ! isEmpty( OPERATORS ) ) {
		return OPERATORS;
	}

	OPERATORS[ OPERATOR_EQUAL_STRICT ] = '===';
	OPERATORS[ OPERATOR_NOT_EQUAL_STRICT ] = '!==';
	OPERATORS[ OPERATOR_EQUAL ] = '==';
	OPERATORS[ OPERATOR_NOT_EQUAL ] = '!=';
	OPERATORS[ OPERATOR_GREATER_OR_EQUAL ] = '>=';
	OPERATORS[ OPERATOR_LESSER_OR_EQUAL ] = '<=';
	OPERATORS[ OPERATOR_GREATER ] = '>';
	OPERATORS[ OPERATOR_LESSER ] = '<';
	OPERATORS[ OPERATOR_IN ] = t('in/contains');
	OPERATORS[ OPERATOR_NOT_IN ] = t('not in/contains');
	OPERATORS[ OPERATOR_IN_STRICT ] = t('in/contains (strict)');
	OPERATORS[ OPERATOR_NOT_IN_STRICT ] = t('not in/contains (strict)');
	OPERATORS[ OPERATOR_HAS_KEY ] = t('has key');
	OPERATORS[ OPERATOR_NOT_HAS_KEY ] = t('not has key');
	OPERATORS[ OPERATOR_SET ] = t('is set');
	OPERATORS[ OPERATOR_NOT_SET ] = t('not set');
	OPERATORS[ OPERATOR_EMPTY ] = t('is empty');
	OPERATORS[ OPERATOR_NOT_EMPTY ] = t('not empty');
	OPERATORS[ OPERATOR_REGEX ] = t( 'regex' );

	return OPERATORS;
}

function getOperator( operator ) {
	switch( operator ) {
		case '?': case 'isset': case 'defined': return OPERATOR_SET;
		case '!?': case 'notset': case 'undefined': return OPERATOR_NOT_SET;
		case '!': return OPERATOR_EMPTY;
		case '!!': case 'notempty': return OPERATOR_NOT_EMPTY;
		case '@': case 'contains': return OPERATOR_IN;
		case '!@': case 'not_contains': return OPERATOR_NOT_IN;
		case '@=': case 'contains_strict': return OPERATOR_IN_STRICT;
		case '!@=': case 'not_contains_strict': return OPERATOR_NOT_IN_STRICT;
		case '#': case 'haskey': return OPERATOR_HAS_KEY;
		case '!#': case 'nothaskey': return OPERATOR_NOT_HAS_KEY;
		case 'lt': case 'lesser': return OPERATOR_LESSER;
		case 'gt': case 'greater': return OPERATOR_GREATER;
		case 'le': case 'lesser_or_equal': return OPERATOR_LESSER_OR_EQUAL;
		case 'ge': case 'greater_or_equal': return OPERATOR_GREATER_OR_EQUAL;
		case 'eq': case 'equal': return OPERATOR_EQUAL;
		case 'ne': case 'not_equal': return OPERATOR_NOT_EQUAL;
		case 'eqs': case 'equal_strict': return OPERATOR_EQUAL_STRICT;
		case 'nes': case 'not_equal_strict': return OPERATOR_NOT_EQUAL_STRICT;
		case '.*': return OPERATOR_REGEX;
		default: return operator;
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
				operator = ( condition && condition.hasOwnProperty( 'operator' ) ) ? getOperator( condition.operator ) : null;

			if ( ! operator ) {
				if ( null === compare || false === compare || '' === compare ) {
					operator = OPERATOR_EMPTY;
				} else if ( true === compare ) {
					operator = OPERATOR_NOT_EMPTY;
				} else {
					operator = ( 'object' === typeof compare ) ? OPERATOR_IN : 'default';
				}
			}

			switch ( operator ) {
				case OPERATOR_SET:
					valid = data.hasOwnProperty( key );
					break;
				case OPERATOR_NOT_SET:
					valid = ! data.hasOwnProperty( key );
					break;
				case OPERATOR_EMPTY:
					valid = ! data.hasOwnProperty( key ) || isEmpty( data[ key ] );
					break;
				case OPERATOR_NOT_EMPTY:
					valid = data.hasOwnProperty( key ) && ! isEmpty( data[ key ] );
					break;
				case OPERATOR_IN:
					valid = data.hasOwnProperty( key ) && hasValue( compare, data[ key ] );
					break;
				case OPERATOR_NOT_IN:
					valid = data.hasOwnProperty( key ) && ! hasValue( compare, data[ key ] );
					break;
				case OPERATOR_HAS_KEY:
					valid = data.hasOwnProperty( key ) && data[ key ].hasOwnProperty( compare );
					break;
				case OPERATOR_NOT_HAS_KEY:
					valid = ! data.hasOwnProperty( key ) || ! data[ key ].hasOwnProperty( compare );
					break;
				case OPERATOR_REGEX:
					valid = data.hasOwnProperty( key ) && isMatch( data[ key ], compare );
					break;
				case OPERATOR_LESSER:
					valid = compare < data[ key ];
					break;
				case OPERATOR_GREATER:
					valid = compare > data[ key ];
					break;
				case OPERATOR_LESSER_OR_EQUAL:
					valid = compare <= data[ key ];
					break;
				case OPERATOR_GREATER_OR_EQUAL:
					valid = compare >= data[ key ];
					break;
				case OPERATOR_NOT_EQUAL:
					valid = compare != data[ key ];
					break;
				case OPERATOR_EQUAL:
					valid = compare == data[ key ];
					break;
				case OPERATOR_NOT_EQUAL_STRICT:
					valid = compare !== data[ key ];
					break;
				case OPERATOR_EQUAL_STRICT:
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
 * @param {array|object} obj
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

/**
 * Checks the value contents and compares it to a set of default values.
 * Empty values like empty object keys are not considered configured.
 * Set values that are the same as the default values are also not considered configured.
 *
 * @param value
 * @param compare
 * @return {boolean}
 */
function isConfigured( value, compare ) {
	if ( 'object' !== typeof value ) {
		if ( isSet( value ) && isSet( compare ) ) {
			return value === compare;
		}
		return ! isEmpty( value );
	}

	if ( Array.isArray( value ) ) {
		if ( ! Array.isArray( compare ) ) {
			compare = [];
		}

		for ( const index in value.filter(isSet) ) {
			if ( isConfigured( value[ index ], compare[ compare.indexOf( value[ index ] ) ] ) ) {
				return true;
			}
		}
		return false;
	}

	if ( ! isObject( compare ) ) {
		compare = {};
	}

	for ( const key in value ) {
		if ( ! value.hasOwnProperty( key ) ) { continue; }
		if ( isConfigured( value[ key ], compare[ key ] ) ) {
			return true;
		}
	}
	return false;
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

/**
 * Check for a set variable or function.
 * @param value
 * @return {boolean}
 */
function isSet( value ) {
	return null !== value && 'undefined' !== typeof value;
}

/**
 * Check for a set value.
 * Functions and symbols are not values.
 * @param value
 * @return {boolean}
 */
function isValue( value ) {
	switch ( typeof value ) {
		case 'string':
		case 'boolean':
		case 'number':
		case 'bigint':
		case 'object':
			return ( null !== value );
		case 'symbol':
		case 'function':
		case 'undefined':
		default:
			return false;
	}
}

function isKey( value ) {
	switch ( typeof value ) {
		case 'string':
			return ! isEmpty( value )
		case 'number':
		case 'bigint':
			return true;
		case 'boolean':
		case 'object':
		case 'function':
		case 'symbol':
		case 'undefined':
		default:
			return false;
	}
}

function isId( value ) {
	return 0 < value && /^\d+$/.test( value );
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

function isBool( variable ) {
	return 'boolean' === typeof variable;
}

function isString( variable ) {
	return 'string' === typeof variable;
}

function isMatch( string, regex ) {
	if ( ! isString( string ) || ! isString( regex ) ) {
		return false;
	}
	if ( ! regex.startsWith( '/' ) ) {
		return string.match( regex );
	}
	// Regex with flags.
	const parts = /\/(.*)\/(.*)/.exec( regex );
	return string.match( new RegExp( parts[1], parts[2] ) );
}

/**
 * @param {string} value
 * @return {boolean}
 */
function isMultiline( value ) {
	return 'string' === typeof value && -1 !== value.indexOf( "\n" );
}

function isFieldEditable( props ) {
	// Only on false, not on undefined/empty.
	if ( false === props.editable ) {
		return false;
	}
	return ! props.disabled && ! props.readonly && ! props.readOnly;
}

export {
	getOperators,
	validate,
	hasKey,
	hasValue,
	isConfigured,
	isEmpty,
	isSet,
	isValue,
	isKey,
	isId,
	isHidden,
	isObject,
	isPromise,
	isArray,
	isBool,
	isString,
	isMatch,
	isMultiline,
	isFieldEditable,
}
