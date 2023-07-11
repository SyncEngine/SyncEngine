import React, { useEffect, useState } from 'react';
import Dataset from "../components/fields/Dataset";
import Fields from "../components/form/Fields";
import { publish, subscribe } from "../utils/events";

export default function DatasetController( props ) {

	const {
		value,
		args = {},
		element,
		onChange,
	} = props;

	const {
		prop,
		fields,
	} = args;

	const [ config, setConfig ] = useState( {} );

	useEffect( () => {
		if ( 'data' === prop ) {
			publish( 'requestConfig', { id: element.closest( 'form' ).id } );
		}
	}, [] )

	const update = ( newValue ) => {
		onChange( newValue );

		if ( 'config' === prop ) {
			publish( 'updateConfig', {
				id: element.closest( 'form' ).id,
				value: newValue
			} );
		}
	}

	switch ( prop ) {

		case 'data':
			subscribe( 'updateConfig', ( e ) => {
				if ( element.closest( 'form' ).id === e.detail.id ) {
					setConfig( e.detail.value );
				}
			} );

			return ( <Dataset value={ [ ...value ] } onChange={ update } columns={ config.columns ?? [] } /> );

		case 'config':

			subscribe( 'requestConfig', ( e ) => {
				if ( element.closest( 'form' ).id === e.detail.id ) {
					publish( 'updateConfig', {
						id: element.closest( 'form' ).id,
						value: value
					} );
				}
			} );

			return ( <Fields fields={ fields } value={ { ...value } } onChange={ update } /> )
	}
}
