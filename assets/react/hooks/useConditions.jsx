import { isEmpty, validate } from '../utils/conditions';
import { useContext } from 'react';
import { FieldsContext } from '../context/FieldsContext';

/**
 * @returns {[Object,{fetch:((function((Object|Function)): Promise<void>)|*),update,total}]}
 */
export default function useConditions() {

	const context = useContext( FieldsContext ); // @todo Create custom context for this?
	const values = context.values ?? {};

	return ( conditions, data = values ) => {
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
	};
}
