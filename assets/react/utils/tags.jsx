import React from 'react';
import { trim } from './trim';

export const TAG_START_CHAR = '{{';
export const TAG_END_CHAR = '}}';
export const TAG_SEPARATOR = '.';
export const TAG_SUB_START_CHAR = '<{';
export const TAG_SUB_END_CHAR = '}>';
export const TAG_ENCLOSURE_CHAR = '"';
export const TAG_FILTER_CHAR = '|';

function objectToTags( obj, parent = null, separator = TAG_SEPARATOR ) {
	if ( 'object' !== typeof obj ) {
		return {};
	}

	let tags = {};

	for ( const key in obj ) {
		if ( ! obj.hasOwnProperty( key ) ) {
			continue;
		}

		const name = ( parent ) ? parent + separator + key : key;

		if ( 'object' === typeof obj[ key ] ) {
			if ( Array.isArray( obj[ key ] ) ) {
				tags = { ...tags, ...objectToTags( obj[ key ][0], name + separator + '[]' ) }
			} else {
				tags = { ...tags, ...objectToTags( obj[ key ], name ) }
			}
			continue;
		}

		tags[ name ] = obj[ key ];
	}

	return tags;
}

/**
 * Parse tags context object with new object.
 * This does not require the tags to be wrapped in braces.
 * @param tags
 * @param parse
 * @return {*}
 */
function parseTagsObject( tags, parse ) {
	if ( 'string' === typeof tags ) {
		return parse[ tags ] ?? {};
	}

	for ( const tag in tags ) {
		if ( tags.hasOwnProperty( tag ) ) {
			if ( 'object' === typeof tags[ tag ] && ! React.isValidElement( tags[ tag ] ) ) {
				tags[ tag ] = parseTagsObject( tags[ tag ], parse );
				continue;
			}

			if ( parse[ tags[ tag ] ] ) {
				tags[ tag ] = parse[ tags[ tag ] ];
			} else if ( 'string' === typeof tags[ tag ] ) {
				tags[ tag ] = parseTag( tags[ tag ], parse );
			}
		}
	}

	return tags;
}

/**
 * Parse all tags with a tags resource recursively.
 * Requires the tags to be wrapped in braces.
 * @param {object} obj
 * @param {object} resource
 * @return {*}
 */
function parseTagsRecursive( obj, resource ) {
	if ( React.isValidElement( obj ) ) {
		return obj;
	}

	for ( const key in obj ) {
		if ( obj.hasOwnProperty( key ) && Object.getOwnPropertyDescriptor( obj, key ).writable ) {
			if ( 'object' === typeof obj[ key ] && !React.isValidElement( obj[ key ] ) ) {
				obj[ key ] = parseTagsRecursive( obj[ key ], resource );
				continue;
			}
			if ( 'string' === typeof obj[ key ] ) {
				obj[ key ] = parseTagString( obj[ key ], resource );
			}
		}
	}

	return obj;
}

function splitByTags(input) {
	const result = [];
	let i = 0;

	while ( i < input.length ) {
		const start = input.indexOf( TAG_START_CHAR, i );
		if ( -1 === start ) {
			result.push( input.slice( i ) );
			break
		}

		// Push preceding text
		if ( start > i ) {
			result.push( input.slice( i, start ) );
		}

		// Look for matching closing '}}' with nested support
		let depth = 1;
		let end = start + 2;
		while ( end < input.length ) {
			if ( input.slice( end, end + 2 ) === TAG_START_CHAR ) {
				depth++;
				end += 2;
			} else if ( input.slice( end, end + 2 ) === TAG_END_CHAR ) {
				depth--;
				end += 2;
				if ( 0 === depth ) break;
			} else {
				end++;
			}
		}

		if ( 0 === depth ) {
			result.push( input.slice( start, end ) )
			i = end;
		} else {
			// Malformed tag, treat rest as plain text
			result.push( input.slice( start ) );
			break
		}
	}

	return result
}


/**
 * @param {string} string
 * @param {object} resource
 * @return {string|*}
 */
function parseTagString( string, resource ) {
	if ( ! resource ) {
		return;
	}

	const parts = string.split( TAG_START_CHAR );

	if ( 2 === parts.length && parts[1].endsWith( TAG_END_CHAR ) ) {
		return parts[0] + parseTag( parts[ 1 ].split( TAG_END_CHAR )[0].trim(), resource );
	}

	for ( const index in parts ) {
		if ( -1 === parts[ index ].indexOf( TAG_END_CHAR ) ) {
			continue;
		}

		const tag = parts[ index ].split( TAG_END_CHAR );

		tag[0] = parseTag( tag[0], resource );

		parts[ index ] = tag.join('');
	}

	return parts.join('');
}

/**
 * @param {string} tag
 * @param {object} resource
 * @return {*}
 */
function parseTag( tag, resource ) {
	const parts = getTagParts( tag );

	for ( const index in parts ) {
		if ( ! resource || ! resource.hasOwnProperty( parts[ index ] ) ) {
			return '';
		}
		resource = resource[ parts[ index ] ];
	}
	return resource;
}

function getTagPart( tag, index ) {
	const parts = getTagParts( tag );
	return parts[ index ] ?? null;
}

function getTagParts( tag ) {
	return trimTag( tag ).split( TAG_SEPARATOR );
}

function isTag( string ) {
	if ( 'string' !== typeof string ) {
		return false;
	}
	return string.startsWith( TAG_START_CHAR ) && string.endsWith( TAG_END_CHAR );
}

function hasTag( string ) {
	if ( 'string' !== typeof string ) {
		return false;
	}
	const startIndex = string.indexOf( TAG_START_CHAR );
	return -1 !== startIndex && startIndex < string.indexOf( TAG_END_CHAR );
}

function trimTag( tag ) {
	return trim( tag, '{} ' );
}

export {
	objectToTags,
	parseTagsObject,
	parseTagsRecursive,
	parseTagString,
	parseTag,
	getTagPart,
	getTagParts,
	splitByTags,
	isTag,
	hasTag,
	trimTag,
};
