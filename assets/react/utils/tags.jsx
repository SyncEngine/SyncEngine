
function parseTagsObject( tags, parse ) {
	for ( const tag in tags ) {
		if ( tags.hasOwnProperty( tag ) ) {
			if ( 'object' === typeof tags[ tag ] ) {
				tags[ tag ] = parseTagsObject( tags[ tag ] );
				continue;
			}

			if ( parse[ tag ] ) {
				tags[ tag ] = parse[ tag ];
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
