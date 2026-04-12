import React, { useContext } from 'react';
import { useTranslation } from 'react-i18next';
import { Alert, Stack } from 'react-bootstrap';
import Fields from '../../form/Fields';
import { FieldsContext } from '../../../context/FieldsContext';

export default function Body( props ) {
	const { t } = useTranslation();

	const {
		value = {},
		description,
		labeled = false,
	} = props;

	if ( ! props.hasOwnProperty( 'fields' ) ) {
		return <Alert variant="warning">{ t('No fields found') }</Alert>
	}

	const labelFields = labeled ? {
		_: {
			id: props._ref + '_',
			inline: true,
			fields: [
				{
					id: props._ref + '_label',
					name: '_label',
					label: t( 'Label' ),
					type: 'text',
					value: value._label ?? '',
				},
				{
					id: props._ref + '_description',
					name: '_description',
					label: t( 'Description' ),
					type: 'text',
					value: value._description ?? '',
				},
			],
		}
	} : {};

	return (
		<Stack gap={ 2 } className={ ( ( props._disabled ?? false ) ? 'opacity-50' : '' ) }>
			{ description }
			<Fields { ...props } fields={ { ...labelFields, ...props.fields } } fieldsContext={ useContext( FieldsContext ) } />
		</Stack>
	);
}
