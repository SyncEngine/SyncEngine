import { useState } from 'react';
import { objectToMappable } from '../utils/data';
import useColumns from './useColumns';
import { isEmpty } from '../utils/conditions';

export function buildFieldsFromSchemaDefinitions( definitions, columns ) {
	return objectToMappable( definitions ).map( ( definition, index ) => {

		if ( ! columns.hasOwnProperty( definition._class ) ) {
			return null;
		}

		const field = {
			label: definition._label ?? definition.label ?? definition.name ?? index,
			name: definition.name ?? index,
			...columns[ definition._class ]
		};

		if ( ! isEmpty( definition.columns ) ) {
			field.nested = buildFieldsFromSchemaDefinitions( definitions.columns, columns );
		}

		return field;
	} );
}

/**
 * @todo Use schema definitions to generate fields etc.
 *
 * @param definitions
 * @return {[{toFields: (function(): (*&{label, name})[])},(value: unknown) => void]}
 */
export default function useSchema( definitions ) {
	throw new Error( 'Not implemented yet' );

	const [ columns ] = useColumns( null, true );
	const [ definitions, setDefinitions ] = useState( definitions );

	return [ {
		toFields: ( definitions ) => buildFieldsFromSchemaDefinitions( definitions, columns )
	} ];
}
