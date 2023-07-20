
const fetchPost = async ( url, data, init = {} ) => {
	const params = new URLSearchParams();
	for ( const key in data ) {
		const value = ( 'object' === typeof data[ key ] ) ? JSON.stringify( data[ key ] ) : data[ key ];
		params.append( key, value );
	}

	init.method = 'POST';
	init.body = params;

	return ( await fetch( url, init ) ).json();
}

const fetchPostJson = async ( url, data, init = {} ) => {
	init.method = 'POST';
	init.body = JSON.stringify( data );
	init.headers[ 'Content-Type' ] = 'application/json';

	return ( await fetch( url, init ) ).json();
}

export {
	fetchPost,
	fetchPostJson
}
