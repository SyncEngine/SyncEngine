import React, { useEffect, useState } from 'react';
import Dataset from "../components/fields/Dataset";
import { publish, subscribe } from "../utils/events";

export default function DatasetController( props ) {

	const {
		value,
		element,
		onChange,
	} = props;

	const [ config, setConfig ] = useState( {} );

	useEffect( () => {
		publish( 'requestConfig', { id: element.closest( 'form' ).id } );
	}, [] )

	const update = ( newValue ) => {
		onChange( newValue );
	}

	subscribe( 'updateConfig', ( e ) => {
		if ( element.closest( 'form' ).id === e.detail.id ) {
			setConfig( e.detail.value );
		}
	} );

	return ( <Dataset value={ value } onChange={ update } columns={ config.columns ?? [] } /> );
}
