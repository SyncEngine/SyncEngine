import { useContext } from 'react';
import { FieldsContext } from '../context/FieldsContext';
import useSyncedState from './useSyncedState';
import { publish } from '../utils/events';

const prefix = 'update:FieldValue:';

export function getEvent( path, context ) {
	return prefix + [ ...context.path, ...path ].join( '.' );
}

export function parseValue( value, field ) {
	if ( field.hasOwnProperty( 'default' ) ) {
		return value ?? field.default;
	}
	return value ?? null;
}

export function publishFieldValue( name, context, state ) {
	const event = getEvent( [ name], context );
	publish( event, state );
}

export default function useFieldValue( key, context = null ) {
	if ( ! context ) {
		context = useContext( FieldsContext );
	}

	const event = getEvent( [ key ], context );

	const [ value ] = useSyncedState( event, context.values && context.values[ key ] );

	return [ value ];
}
