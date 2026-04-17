import React from 'react';
import { any, array, bool, func, object, oneOfType, string } from 'prop-types';
import SelectSimple from './Simple';
import SelectAdvanced from './Advanced';
import { useTranslation } from 'react-i18next';
import useFieldChoices from '../../../hooks/useFieldChoices';
import Placeholder from '../../partials/Loading/Placeholder';


export default function Select( props ) {
	const { t } = useTranslation();
	const [ choices, setChoices, loading ] = useFieldChoices( props );

	if ( loading ) {
		return <Placeholder />
	}

	if ( props.customizable ) {
		return <SelectSimple { ...props } choices={ choices ?? ( loading ? [ t( 'Loading ...' ) ] : [] ) } />
	}

	return <SelectAdvanced { ...props } choices={ choices ?? ( loading ? [ t( 'Loading ...' ) ] : [] ) } />
}

Select.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	label: oneOfType( [ string, object ] ),
	icon: string,
	description: string,
	placeholder: string,
	attr: oneOfType( [ object, array ] ),
	id: string,
	help: oneOfType( [ string, object, array ] ),
	customizable: bool,
	prefix: any,
	postfix: any,
	choices: oneOfType( [ object, array ] ),
	group: oneOfType( [ object, array ] ),
	// Advanced.
	async: bool,
	onAsyncSearch: func,
	variant: string,
	filters: oneOfType( [ object, array ] ),
	selectProps: oneOfType( [ object, array ] ),
	compact: bool,
}
