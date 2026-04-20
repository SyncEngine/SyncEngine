import { useContext } from 'react';
import { FieldsContext } from '../context/FieldsContext';
import { isEmpty, isIterable, isString } from '../utils/conditions';
import useFieldValues from './useFieldValues';
import useFetch from './useFetch';
import { parseTagsRecursive } from '../utils/tags';
import { EntityContext } from '../context/EntityContext';
import { deepClone } from '../utils/data';

export default function useFieldChoices( props ) {
	const {
		choices,
		choicesProvider,
	} = props;

	if ( ! isEmpty( choicesProvider ) ) {
		const {
			endpoint,
		} = choicesProvider;

		const params = deepClone( choicesProvider.params ?? {} );
		const entity = useContext( EntityContext );
		const context = useContext( FieldsContext );
		const [ config ] = useFieldValues( null, isString( params.config ) ? context[ params.config ] : context ?? null );

		return useFetch( endpoint, parseTagsRecursive( params, { entity: entity, config: config } ), null );
	}

	return [ isIterable( choices ) ? choices : [], null, false ];
}
