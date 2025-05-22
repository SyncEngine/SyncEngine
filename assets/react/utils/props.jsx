
function mergeClassNames( ...params ) {
	return [ ...params ].filter( Boolean ).join( ' ' );
}

export {
	mergeClassNames
}
