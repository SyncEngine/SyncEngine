import { useContext, useEffect, useState } from 'react';
import { FieldsContext } from '../context/FieldsContext';
import { getEvent } from './useFieldValue';
import { subscribe, unsubscribe } from '../utils/events';
import { isEmpty } from '../utils/conditions';

export default function useFieldValues( initial = null, context = null ) {
	if ( ! context ) {
		context = useContext( FieldsContext );
	}

	const [ values, setValues ] = useState( initial || context.values || {} );

	useEffect( () => {

		if ( isEmpty( values ) ) {
			return;
		}

		const callbacks = {};

		for ( const name in values ) {
			callbacks[ name ] = ( data ) => {
				//values[]
				values[ name ] = data.detail;
				setValues( { ...values } );
			};
			subscribe( getEvent( [ name ], context ?? {}, context ?? {} ), callbacks[ name ] );
		}

		return () => {
			for ( const name in callbacks ) {
				unsubscribe( getEvent( [ name ], context ?? {}, context ?? {} ), callbacks[ name ] );
			}
		}
	}, [] );

	return [ values ];
}
