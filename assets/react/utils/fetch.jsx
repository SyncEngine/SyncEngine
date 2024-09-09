import { isObject } from './conditions';

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

		return await fetch( url, init ).then(
			res => res.clone().json().catch( () => res.text() )
		).then(
			res => isObject( res ) ? res : { success: false, error: res }
		);

	} catch ( e ) {
		return { success: false, error: e };
	}
}

export {
	fetchPost,
	fetchPostJson,
	fetchJson
}
