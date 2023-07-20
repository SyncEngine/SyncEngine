import { toFormat } from './format';

const fetchPost = async ( url, data, init = {} ) => {
	init.method = 'POST';
	init.body = toFormat( data, 'url' );

	return ( await fetch( url, init ) ).json();
}

const fetchPostJson = async ( url, data, init = {} ) => {
	init.method = 'POST';
	init.body = toFormat( data, 'json' );
	init.headers[ 'Content-Type' ] = 'application/json';

	return ( await fetch( url, init ) ).json();
}

export {
	fetchPost,
	fetchPostJson
}
