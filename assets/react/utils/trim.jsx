// Helper function to detect if a string contains another string
//     at a specific position.
// Equivalent to using `str.indexOf(substr, pos) === pos` but *should* be more efficient on longer strings as it can exit early (needs benchmarks to back this up).
function hasSubstringAt( str, substr, pos ) {
	var idx = 0, len = substr.length;

	for ( var max = str.length; idx < len; ++ idx ) {
		if ( ( pos + idx ) >= max || str[ pos + idx ] != substr[ idx ] ) {
			break;
		}
	}

	return idx === len;
}

function trimWord( str, word ) {
	var start = 0,
		end = str.length,
		len = word.length;

	while ( start < end && hasSubstringAt( str, word, start ) ) {
		start += word.length;
	}

	while ( end > start && hasSubstringAt( str, word, end - len ) ) {
		end -= word.length;
	}

	return (
		start > 0 || end < str.length
	) ? str.substring( start, end ) : str;
}

function trim( str, chars ) {
	var start = 0,
		end = str.length;

	while ( start < end && chars.indexOf( str[ start ] ) >= 0 ) {
		++ start;
	}

	while ( end > start && chars.indexOf( str[ end - 1 ] ) >= 0 ) {
		-- end;
	}

	return (
		start > 0 || end < str.length
	) ? str.substring( start, end ) : str;
}

export {
	trim,
	trimWord,
}
