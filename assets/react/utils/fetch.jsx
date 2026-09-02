import { isObject } from './conditions';

const generateCsrfToken = () => {
	const tokenName = window.SyncEngine?.csrfToken;

	if ( ! tokenName ) {
		return [ null, null ];
	}

	const token = crypto.randomUUID();

	// Double-submit stateless token.
	document.cookie = `${tokenName}=${token}; Path=/; SameSite=Strict; Secure`;

	return [ tokenName, token ];
}

const fetchPost = async ( url, data, init = {} ) => {
	const [ tokenName, token ] = generateCsrfToken();
	if ( token ) {
		init.headers = { ...( init.headers || {} ) };
		init.headers[ tokenName ] = token;
	}

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
	init.headers = { ...( init.headers || {} ) };
	init.headers[ 'Content-Type' ] = 'application/json';

	const [ tokenName, token ] = generateCsrfToken();
	if ( token ) {
		init.headers[ tokenName ] = token;
	}

	init.method = 'POST';
	init.body = JSON.stringify( data );

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
		// Distinguish network errors from parsing errors
		const error = e instanceof TypeError 
			? { type: 'network', message: 'Network request failed' }
			: { type: 'parsing', message: 'Failed to parse response' };
		
		console.error( '[fetchJson]', url, error );
		
		return { success: false, error: error };
	}
}

export {
	fetchPost,
	fetchPostJson,
	fetchJson
}
