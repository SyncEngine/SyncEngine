
function objectToTags( obj, parent = null, separator = '.' ) {
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

function parseTagsObject( tags, parse ) {
	if ( 'string' === typeof tags ) {
		return parse[ tags ] ?? {};
	}

	for ( const tag in tags ) {
		if ( tags.hasOwnProperty( tag ) ) {
			if ( 'object' === typeof tags[ tag ] ) {
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

function parseTagString( string, resource ) {
	const parts = string.split( '{{' );

	for ( const index in parts ) {
		if ( -1 === parts[ index ].indexOf( '}}' ) ) {
			continue;
		}

		const tag = parts[ index ].split( '}}' );

		tag[0] = parseTag( tag[0], resource );

		parts[ index ] = tag.join('');
	}

	return parts.join('');
}

function parseTag( tag, resource ) {
	const parts = tag.trim().split( '.' );

	for ( const index in parts ) {
		if ( ! resource.hasOwnProperty( parts[ index ] ) ) {
			return '';
		}
		resource = resource[ parts[ index ] ];
	}
	return resource;
}

export {
	objectToTags,
	parseTagsObject,
	parseTagString,
	parseTag,
};
