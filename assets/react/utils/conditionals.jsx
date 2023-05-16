
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
			if ( Array.isArray( value ) ) {
				return 0 < value.length;
			} else {
				return 0 < Object.keys( value ).length;
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
			if ( Array.isArray( value ) ) {
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

export {
	isEmpty,
	isSet,
	hasValue
}
