import { useContext } from 'react';
import { FieldsContext } from '../context/FieldsContext';
import useSyncedState from './useSyncedState';

export default function useFieldValue( key, context = null ) {
	if ( ! context ) {
		context = useContext( FieldsContext );
	}

	const prefix = 'update:FieldValue:';
	const path = ( context.path && context.path.join( '.' ) + '.' );

	const event = prefix + path + key;
	const [ value, update, publish ] = useSyncedState( event, context.values && context.values[ key ] );

	const updateValue = ( state, name, child ) => {

		if ( name ) {
			if ( child ) {
				//??
			}
			publish( prefix + path + name );
		}

		console.log( event );

		update( state );
	}

	return [ value, updateValue ];
}
