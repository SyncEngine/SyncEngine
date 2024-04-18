import { useCallback, useContext, useEffect, useState } from 'react';
import { isEmpty, isObject, validate } from '../utils/conditions';
import { FieldsContext } from '../context/FieldsContext';
import { getEvent } from './useFieldValue';
import { subscribe, unsubscribe } from '../utils/events';
import { deepClone } from '../utils/data';

/**
 * @returns {[Object,{fetch:((function((Object|Function)): Promise<void>)|*),update,total}]}
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

	useEffect( () => {

		if ( ! dependencies ) {
			return;
		}

		const callbacks = {};

		for ( const name in dependencies ) {
			callbacks[ name ] = ( data ) => {
				//values[]
				values[ name ] = data.detail;
				setChanged( changed + 1 );
				setValid( onValidate() );
			};
			subscribe( getEvent( [ name ], context ?? {} ), callbacks[ name ] );
		}

		return () => {
			for ( const name in callbacks ) {
				unsubscribe( getEvent( [ name ], context ?? {} ), callbacks[ name ] );
			}
		}
	}, [ dependencies, values ] );

	return [ valid, onValidate ];
}
