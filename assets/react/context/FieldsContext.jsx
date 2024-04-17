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

	if ( ! isEmpty( parent.path ) && ! isKey( key ) ) {
		throw new Error( 'Key is required: ' + key );
	}
	if ( ! refId ) {
		throw new Error( 'RefId is required.' );
	}
	if ( ! isObject( values ) ) {
		throw new Error( 'Values is a required object property.' );
	}

	if ( ! key ) {
		key = refId;
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
