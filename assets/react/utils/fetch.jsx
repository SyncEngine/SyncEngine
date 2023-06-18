
const fetchPost = async ( data ) => {
	const params = new URLSearchParams();
	for ( const key in data ){
		params.append( key, data[ key ] );
	}

	return (
		await fetch( endpoint, {
			method: "POST",
			body: params,
		} )
	).json();
}

const fetchPostJson = async ( data ) => {
	return (
		await fetch( endpoint, {
			method: "POST",
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( data ),
		} )
	).json();
}

export {
	fetchPost,
	fetchPostJson
}
