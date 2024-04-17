import { useContext } from 'react';
import { FieldsContext } from '../context/FieldsContext';
import useSyncedState from './useSyncedState';

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

export default function useFieldValue( key, context = null ) {
	if ( ! context ) {
		context = useContext( FieldsContext );
	}

	const event = getEvent( [ key ], context );

	const [ value, update, publish ] = useSyncedState( event, context.values && context.values[ key ] );

	const updateValue = ( state, name, field ) => {
		state = parseValue( state, field );

		if ( key !== name || ( field && field.name !== key ) ) {
			publish( getEvent( [ name ], context ), state );
			return;
		}

		update( state, true );
	}

	return [ value, updateValue ];
}
