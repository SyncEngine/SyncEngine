import React, { useEffect, useState } from 'react';
import Dataset from '../components/fields/Dataset';
import { publish, subscribe, unsubscribe } from '../utils/events';

export default function StorageController( props ) {

	const {
		value,
		element,
		onChange,
	} = props;

	const [ config, setConfig ] = useState( null );

	const update = ( newValue ) => {
		onChange( newValue );
	}

	useEffect( () => {
		function updateConfig( e ) {
			if ( element.closest( 'form' ).id === e.detail.id ) {
				setConfig( e.detail.value ?? {} );
			}
		}

		subscribe( 'updateConfig', updateConfig );
		publish( 'requestConfig', { id: element.closest( 'form' ).id } );
		return function cleanup() {
			unsubscribe( 'updateConfig', updateConfig );
		}
	}, [ element, setConfig ] );

	if ( config ) {
		return ( <Dataset value={ value } onChange={ update } type={ config.type } storageConfig={ config } /> );
	}
}
