
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

function parseTag( tag, object ) {
	const parts = tag.split( '.' );

	for ( const index in parts ) {
		if ( ! object.hasOwnProperty( parts[ index ] ) ) {
			return '';
		}
		object = object[ parts[ index ] ];
	}
	return object;
}

export {
	objectToTags,
	parseTagsObject,
	parseTag,
};
