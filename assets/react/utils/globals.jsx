
function createRefId( prefix = '' ) {
	return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

function ucfirst( str ) {
	return str.charAt(0).toUpperCase() + str.slice(1);
}

export {
	createRefId,
	ucfirst,
}
