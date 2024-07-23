import React, { useCallback } from 'react';
import Code from '../../fields/Code';

export default function TraceLog( props ) {

	const removeRefs = useCallback( ( data ) => {
		if ( Array.isArray( data ) ) {
			return data.map( item => removeRefs( item ) );
		}
		if ( 'object' !== typeof data ) {
			return data;
		}
		data = { ...data };

		if ( data.hasOwnProperty( 'trace' ) ) {
			data.trace = removeRefs( data.trace );
		}
		if ( data.hasOwnProperty( '_ref' ) ) {
			delete data._ref;
		}
		if ( data.hasOwnProperty( '_ancestors' ) ) {
			delete data._ancestors;
		}

		return data;
	} )

	return (
		<Code value={ JSON.stringify( removeRefs( props.data ), null, 2 ) } language="json" readOnly />
	);
}
