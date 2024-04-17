import { useContext } from 'react';
import { FieldsContext } from '../context/FieldsContext';
import useSyncedState from './useSyncedState';

const prefix = 'update:FieldValue:';

export function getEvent( path, context ) {
	return prefix + [ ...context.path, ...path ].join( '.' );
}

export default function useFieldValue( key, context = null ) {
	if ( ! context ) {
		context = useContext( FieldsContext );
	}

	const event = getEvent( [ key ], context );

	const [ value, update, publish ] = useSyncedState( event, context.values && context.values[ key ] );

	const updateValue = ( state, name, child ) => {

		if ( name ) {
			if ( child ) {
				//??
			}
			publish( getEvent( [ name ], context ) );
		}

		console.log( event );

		update( state );
	}

	return [ value, updateValue ];
}
