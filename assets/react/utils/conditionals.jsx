
function getOperators() {
	return {
		'===': '===',
		'!==': '!==',
		'==': '==',
		'!=': '!=',
		'>=': '>=',
		'<=': '<=',
		'>': '>',
		'<': '<',
		'in': 'in/contains',
		'not': 'not in/contains',
		'in_strict': 'in/contains (strict)',
		'not_strict': 'not in/contains (strict)',
		'set': 'is set',
		'not_set': 'not set',
		'empty': 'is empty',
		'not_empty': 'not empty',
	}
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
			return '' !== value;
		case 'boolean':
			return true;
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

function hasValue( value ) {
	return ! isEmpty( value );
}


function validate ( conditionals, data ) {
	let valid = true;
	if ( conditionals && Object.keys( conditionals ).length ) {
		for ( let key in conditionals ) {
			const conditional = conditionals[ key ];

			let compare = conditional.hasOwnProperty( 'compare' ) ? conditional.compare : conditional,
				operator = conditional.hasOwnProperty( 'operator' ) ? conditional.operator : null;

			if ( ! operator ) {
				operator = ( 'object' === typeof compare ) ? 'in' : 'default';
			}

			switch ( operator ) {
				case 'isset':
					valid = data.hasOwnProperty( key );
					break;
				case 'notset':
					valid = ! data.hasOwnProperty( key );
					break;
				case 'empty':
					valid = ! data.hasOwnProperty( key ) || isEmpty( data[ key ] );
					break;
				case 'notempty':
					valid = data.hasOwnProperty( key ) && ! isEmpty( data[ key ] );
					break;
				case 'in':
					valid = data.hasOwnProperty( key ) && -1 !== compare.indexOf( data[ key ] );
					break;
				case 'not':
					valid = data.hasOwnProperty( key ) && -1 === compare.indexOf( data[ key ] );
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

export {
	getOperators,
	isEmpty,
	isSet,
	hasValue,
	validate
}
