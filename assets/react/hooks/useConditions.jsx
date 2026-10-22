import { useCallback, useContext, useEffect, useRef, useState } from 'react';
import { isArray, isEmpty, isObject, validate } from '../utils/conditions';
import { FieldsContext } from '../context/FieldsContext';
import { getEvent } from './useFieldValue';
import { subscribe, unsubscribe } from '../utils/events';
import { deepClone } from '../utils/data';

/**
 * @param {object} dependencies
 * @returns {Set<string>}
 */
function getFieldDependencies( dependencies = null ) {
	const fields = new Set();
	if ( ! isObject( dependencies ) ) {

		if ( isArray( dependencies ) ) {
			dependencies.forEach( ( item ) => {
				getFieldDependencies( item ).forEach(
					( field ) => fields.add( field )
				);
			} );
		}

		return fields;
	}

	if ( dependencies.hasOwnProperty( 'conditions' ) ) {
		getFieldDependencies( dependencies.conditions ).forEach(
			( field ) => fields.add( field )
		);
		return fields;
	}

	for ( const name in dependencies ) {
		if ( ! dependencies.hasOwnProperty( name ) ) {
			continue;
		}

		const value = dependencies[ name ];

		// Lists only, not objects.
		if ( isArray( value ) ) {
			getFieldDependencies( value ).forEach(
				( field ) => fields.add( field )
			);
			continue;
		}

		fields.add( name );
	}

	return fields;
}

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

		if ( isEmpty( dependencies ) ) {
			return;
		}

		for ( const name of getFieldDependencies( dependencies ) ) {
			callbacks.current[ name ] = ( data ) => {
				values[ name ] = data.detail;
				setChanged( c => c + 1 );
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
