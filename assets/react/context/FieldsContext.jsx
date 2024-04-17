import { createContext, useContext } from 'react';
import { isEmpty, isKey, isObject } from '../utils/conditions';

export const FieldsContext = createContext(
	{
		parent: null,
		children: {},
		root: '',
		prefix: '',
		id: '',
		path: [],
		values: {},
	}
);

export const FieldContext = createContext( {} );

FieldsContext.create = ( key, values = {}, refId, props = {} ) => {
	const parent = useContext( FieldsContext );
	const field = useContext( FieldContext );

	if ( ! isEmpty( parent.path ) && ! isKey( key ) && ! isKey( field.name ) ) {
		throw new Error( 'A key is required to create a new fields context' );
	}
	if ( ! refId ) {
		throw new Error( 'RefId is required.' );
	}
	if ( ! isObject( values ) ) {
		throw new Error( 'Values is a required object property.' );
	}

	if ( ! isKey( key ) ) {
		key = field.name ?? refId;
	}

	const context= {
		parent: parent,
		children: {},
		root: parent.root ?? null,
		prefix: ( parent.prefix && parent.prefix + '_' ) + refId,
		id: refId,
		path: parent.path ? [ ...parent.path, key ] : [ key ],
		values: values,
		...props,
	}

	parent.children[ key ] = context;

	return context;
}
