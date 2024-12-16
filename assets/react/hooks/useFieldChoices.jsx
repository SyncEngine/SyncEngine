import { useContext } from 'react';
import { FieldsContext } from '../context/FieldsContext';
import { isEmpty, isString } from '../utils/conditions';
import useFieldValues from './useFieldValues';
import useFetch from './useFetch';

export default function useFieldChoices( props ) {
	const {
		choices,
		choicesProvider,
	} = props;

	if ( ! isEmpty( choicesProvider ) ) {
		const {
			endpoint,
			params,
		} = choicesProvider;

		if ( params.config ) {
			const context = useContext( FieldsContext );
			const [ config ] = useFieldValues( null, isString( params.config ) ? context[ params.config ] : context ?? null );
			params.config = config;
		}

		return useFetch( endpoint, params );
	}

	return [ choices, null, false ];
}
