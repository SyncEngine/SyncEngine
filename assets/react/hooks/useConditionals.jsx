import { isEmpty, validate } from '../utils/conditionals';

/**
 * @returns {[Object,{fetch:((function((Object|Function)): Promise<void>)|*),update,total}]}
 */
export default function useConditionals() {

	const context = {}; // @todo Create custom context for this?

	return ( conditionals, data ) => {
		if ( conditionals.hasOwnProperty( '_context' ) && ! isEmpty( context ) ) {
			if ( validate( conditionals._context, context ) ) {
				return false;
			}

			// Remove from further parsing.
			conditionals = { ...conditionals };
			delete conditionals._context;
		}
		return validate( conditionals, data );
	};
}
