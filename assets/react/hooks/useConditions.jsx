import { isEmpty, validate } from '../utils/conditions';

/**
 * @returns {[Object,{fetch:((function((Object|Function)): Promise<void>)|*),update,total}]}
 */
export default function useConditions() {

	const context = {}; // @todo Create custom context for this?

	return ( conditions, data ) => {
		if ( conditions.hasOwnProperty( '_context' ) && ! isEmpty( context ) ) {
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
