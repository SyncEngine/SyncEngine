import React, { useEffect, useState } from 'react';
import Dataset from "../components/fields/Dataset";
import FieldGroup from "../components/form/FieldGroup";
import { publish, subscribe } from "../utils/events";

export default function DatasetController( props ) {

	const {
		args = {},
		value = {},
		element,
		onChange,
	} = props;

	const {
		prop,
		fields,
	} = args;

	const [ config, setConfig ] = useState( {} );

	switch ( prop ) {
		case 'data':
			subscribe( 'updateConfig', ( e ) => {
				if ( element.closest( 'form' ).id === e.detail.id ) {
					setConfig( e.detail.value );
				}
			} );
			break;
		case 'config':
			subscribe( 'requestConfig', ( e ) => {
				if ( element.closest( 'form' ).id === e.detail.id ) {
					publish( 'updateConfig', {
						id: element.closest( 'form' ).id,
						value: value
					} );
				}
			} );
			break;
	}

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
			return ( <Dataset value={ value } onChange={ update } columns={ config.columns ?? [] }></Dataset> );
		case 'config':
			// Todo
			return ( <FieldGroup fields={ fields } value={ value } onChange={ update } /> )
	}
}
