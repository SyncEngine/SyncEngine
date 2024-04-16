import { useContext } from 'react';
import { FieldsContext } from '../context/FieldsContext';
import useSyncedState from './useSyncedState';

export default function useFieldValue( key, context = null ) {
	if ( ! context ) {
		context = useContext( FieldsContext );
	}

	const name = ( context.path && context.path.join( '.' ) + '.' ) + key;

	return useSyncedState( 'update:FieldValue:' + name, context.values && context.values[ key ] );
}
