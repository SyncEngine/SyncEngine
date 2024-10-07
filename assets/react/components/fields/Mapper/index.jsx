import React, { useEffect } from 'react';
import { any, array, bool, func, object, oneOfType } from 'prop-types';
import { useTranslation } from 'react-i18next';

import Grid from '../Grid';
import Fields from '../../form/Fields';
import useEntity from '../../../hooks/useEntity';
import useFieldValues from '../../../hooks/useFieldValues';
import { objectToMappable } from '../../../utils/data';
import { objectToTags } from '../../../utils/tags';
import { hasKey, isEmpty, isFieldEditable, isObject } from '../../../utils/conditions';

function getSchemaChoices( schema ) {
	if ( isEmpty( schema ) ) {
		return [];
	}
	const choices = {};
	// @todo recursive.
	objectToMappable( schema, 'key', 'label' ).forEach( item => {
		choices[ item.key ] = item.label ?? item.name ?? item.key;
	} );
	return choices;
}

function getStorageChoices( storage ) {
	let choices = {};

	if ( 'object' === typeof storage.data ) {
		if ( Array.isArray( storage.data ) ) {
			storage.data.forEach( item => {
				item = parseChoice( item );
				choices[ item.value ] = item.label;
			} );
		} else {
			choices = { ...( storage.data ?? {} ) };
		}
	}

	if ( ! isEmpty( storage._schema ) ) {
		choices = {
			...choices, // Sets order.
			...objectToTags( getSchemaChoices( storage._schema ) ),
			...choices // Storage data is leading.
		};
	}

	return choices;
}

function parseChoice( item ) {
	if ( 'object' !== typeof item ) {
		return { value: item, label: item };
	}

	const value = String( item.value || item.key || item.name || item.label );
	const label = String( item.label || item.name || item.value || item.key );

	return {
		value: value,
		label: ( label !== value ) ? label + '  <' + value + '>' : value,
	}
}

function parseChoices( data ) {
	if ( 'object' !== typeof data ) {
		return [];
	}

	if ( ! Array.isArray( data ) ) {
		data = objectToMappable( objectToTags( data ), 'value', 'label', false );
	}

	return data.map( parseChoice );
}

export default function Mapper( props ) {
	const { t } = useTranslation();
	const [ values ] = useFieldValues( props.values );
	const editable = isFieldEditable( props );

	let {
		value,
		config,
		choices = 'choices',
		onChange,
	} = props;

	if ( config ) {
		// Switch to configurable mapper.
		return <Fields fields={ config } value={ value } onChange={ onChange } />
	}

	const [ sourceStorage, sourceCallbacks ] = useEntity( 'storage' );
	const [ targetStorage, targetCallbacks ] = useEntity( 'storage' );

	useEffect( () => {
		if ( hasKey( values, choices ) && isObject( values[ choices ] ) ) {
			sourceCallbacks.set( values[ choices ].source );
			targetCallbacks.set( values[ choices ].target );
		} else {
			sourceCallbacks.set( null );
			targetCallbacks.set( null );
		}
	}, [ choices, values ] );

	return (
		<Grid
			editable={ editable }
			taggable={ props.taggable }
			sortable={ props.sortable ?? editable }
			disabled={ props.disabled }
			value={ value }
			onChange={ onChange }
			columns={ {
				source: {
					label: t('From'),
					choices: parseChoices( ( sourceStorage ) ? getStorageChoices( sourceStorage ) : choices.source ),
					customizable: props.customizable ?? true,
				},
				target: {
					label: t('To'),
					choices: parseChoices( ( targetStorage ) ? getStorageChoices( targetStorage ) : choices.target ),
					customizable: props.customizable ?? true,
				},
			} }
		/>
	);
}

Mapper.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	sortable: bool,
	taggable: bool,
	config: object,
	customizable: bool,
	choices: oneOfType( [ object, array ] ),
}
