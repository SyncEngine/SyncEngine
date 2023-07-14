
function createRefId( prefix = '', postfix = '' ) {
	return prefix + Date.now().toString(36) + Math.random().toString(36).substr(2) + postfix;
}

function ucfirst( str ) {
	return str.charAt(0).toUpperCase() + str.slice(1);
}

export {
	createRefId,
	ucfirst,
}
