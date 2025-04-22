import { useState } from 'react';
import { objectToMappable } from '../utils/data';
import useColumns from './useColumns';
import { isEmpty } from '../utils/conditions';

function buildFields( definitions, columns ) {
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
			field.nested = buildFields( definitions.columns, columns );
		}

		return field;
	} );
}

/**
 * Use schema definitions to generate fields etc.
 *
 * @param definitions
 * @return {[{toFields: (function(): (*&{label, name})[])},(value: unknown) => void]}
 */
export default function useSchema( definitions ) {
	const [ columns ] = useColumns( null, true );
	const [ definitions, setDefinitions ] = useState( definitions );

	return [ {
		toFields: () => buildFields( definitions, columns )
	}, setDefinitions ];
}
