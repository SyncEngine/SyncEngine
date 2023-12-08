
const fetchPost = async ( url, data, init = {} ) => {
	const params = new URLSearchParams();
	for ( const key in data ) {
		const value = ( 'object' === typeof data[ key ] ) ? JSON.stringify( data[ key ] ) : data[ key ];
		params.append( key, value );
	}

	init.method = 'POST';
	init.body = params;

	return await fetchJson( url, init );
}

const fetchPostJson = async ( url, data, init = {} ) => {
	init.method = 'POST';
	init.body = JSON.stringify( data );
	init.headers[ 'Content-Type' ] = 'application/json';

	return await fetchJson( url, init );
}

const fetchJson = async ( url, init ) => {
	try {

		const res = await fetch( url, init );

		if ( res.ok ) {
			return await res.json();
		}

		return await res.json();
	} catch ( e ) {
		return e;
	}
}

export {
	fetchPost,
	fetchPostJson,
	fetchJson
}
