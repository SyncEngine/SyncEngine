
const parseForm = ( element ) => {
	const data = new FormData( element );
	const parsed = {};
	for ( const pair of data.entries() ) {
		parsed[ pair[0] ] = pair[1];
	}
	return parsed;
}

export {
	parseForm,
}
