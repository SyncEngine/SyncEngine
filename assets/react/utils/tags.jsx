
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
	parseTagsObject,
	parseTag,
};
