import { useCallback, useContext, useEffect, useRef, useState } from 'react';
import { isEmpty, isObject, validate } from '../utils/conditions';
import { FieldsContext } from '../context/FieldsContext';
import { getEvent } from './useFieldValue';
import { subscribe, unsubscribe } from '../utils/events';
import { deepClone } from '../utils/data';

/**
 * @param {object} dependencies
 * @param {object} context
 * @return {[boolean|*,(function(null=, null=): (boolean|*))|*]}
 */
export default function useConditions( dependencies = null, context = null ) {

	if ( null === context ) {
		context = useContext( FieldsContext );
	}

	const values = ( isObject( context ) && context.values ) || {};

	const [ changed, setChanged ] = useState( 0 );

	const onValidate = useCallback( ( conditions = null, data = null ) => {
		conditions = deepClone( conditions || dependencies );
		data = deepClone( data || values );

		if ( conditions.hasOwnProperty( '_context' ) && ! isEmpty( context ) ) {
			// @todo: Root.
			if ( validate( conditions._context, context ) ) {
				return false;
			}

			// Remove from further parsing.
			conditions = { ...conditions };
			delete conditions._context;
		}
		return validate( conditions, data );
	}, [ changed, dependencies, values ] );

	const [ valid, setValid ] = useState( dependencies ? onValidate() : true );

	const callbacks = useRef({});

	useEffect( () => {

		if ( ! dependencies ) {
			return;
		}

		for ( const name in dependencies ) {
			callbacks.current[ name ] = ( data ) => {
				//values[]
				values[ name ] = data.detail;
				setChanged( changed + 1 );
				setValid( onValidate() );
			};
			subscribe( getEvent( [ name ], context ?? {} ), callbacks.current[ name ] );
		}

		return () => {
			for ( const name in callbacks.current ) {
				unsubscribe( getEvent( [ name ], context ?? {} ), callbacks.current[ name ] );
				delete callbacks.current[ name ];
			}
		}
	}, [ dependencies, values ] );

	return [ valid, onValidate ];
}
