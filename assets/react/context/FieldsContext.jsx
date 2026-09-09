import { createContext, useContext } from 'react';
import { isEmpty, isFieldEditable, isKey, isObject } from '../utils/conditions';

/**
 * Context for field hierarchy in form structures.
 * Provides parent reference, path tracking, value storage, and editable state.
 * @type {import('react').Context<{
 *   parent: FieldsContext | null,
 *   children: Record<string, FieldsContext>,
 *   root: string | null,
 *   prefix: string,
 *   id: string,
 *   path: string[],
 *   values: Record<string, any>,
 *   editable: boolean,
 *   _root: any,
 *   _index: number | null
 * }>}
 */
export const FieldsContext = createContext(
	{
		parent: null,
		children: {},
		root: '',
		prefix: '',
		id: '',
		path: [],
		values: {},
		_root: null,
		_index: null,
	}
);

/**
 * Context for individual field configuration.
 * @type {import('react').Context<Object<string, any>>}
 */
export const FieldContext = createContext( {} );

FieldContext.create = ( field ) => {
	const context = useContext( FieldsContext );

	return {
		...field,
		editable: false !== context.editable && isFieldEditable( field )
	};
}

FieldsContext.create = ( key, values = {}, refId, parent, field, props = {} ) => {

	parent = parent ?? useContext( FieldsContext );
	field = field ?? useContext( FieldContext );

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
		editable: false !== parent.editable && isFieldEditable( field ),
		...props,
	}

	// Set root of context.
	context._root = parent._root ?? parent.root ? parent : context;

	// Set index in case of repeatable fields.
	if ( parent._index && ! context._index ) {
		context._index = parent._index;
	}

	parent.children[ key ] = context;

	return context;
}
