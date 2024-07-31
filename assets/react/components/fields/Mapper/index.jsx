import React, { useCallback, useEffect } from 'react';
import Grid from '../Grid';
import Fields from '../../form/Fields';
import useEntity from '../../../hooks/useEntity';
import { objectToMappable } from '../../../utils/data';
import { objectToTags } from '../../../utils/tags';
import { useTranslation } from 'react-i18next';
import { hasKey, isEmpty, isObject } from '../../../utils/conditions';
import useFieldValues from '../../../hooks/useFieldValues';

export default function Mapper( props ) {
	const { t } = useTranslation();
	const [ values ] = useFieldValues( props.values );

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
			sourceCallbacks.get( values[ choices ].source );
			targetCallbacks.get( values[ choices ].target );
		} else {
			sourceCallbacks.get( null );
			targetCallbacks.get( null );
		}
	}, [ choices, values ] );

	const getSchemaChoices = useCallback( schema => {
		if ( isEmpty( schema.columns ) ) {
			return [];
		}
		const choices = {};
		// @todo recursive.
		schema.columns.map( item => {
			choices[ item.key ] = item.label ?? item.name ?? item.key;
		} );
		return choices;
	}, [] );

	const getStorageChoices = useCallback( source => {
		let choices;

		if ( 'object' === typeof source.data ) {
			if ( Array.isArray( source.data ) ) {
				source.data.map( item => {
					item = parseChoice( item );
					choices[ item.value ] = item.label;
				} );
			} else {
				choices = { ...( source.data ?? {} ) };
			}
		}

		if ( ! isEmpty( source.config ) && ! isEmpty( source.config.schema ) ) {
			return {
				...choices, // Sets order.
				...objectToTags( getSchemaChoices( source.config.schema ) ),
				...choices // Storage data is leading.
			};
		}

		return source.data;
	}, [] );

	const parseChoice = useCallback( item => {
		if ( 'object' !== typeof item ) {
			return { value: item, label: item };
		}

		const value = String( item.value || item.key || item.name || item.label );
		const label = String( item.label || item.name || item.value || item.key );

		return {
			value: value,
			label: ( label !== value ) ? label + '  <' + value + '>' : value,
		}
	}, [] );

	const parseChoices = useCallback( ( data ) => {
		if ( 'object' !== typeof data ) {
			return [];
		}

		if ( ! Array.isArray( data ) ) {
			data = objectToMappable( objectToTags( data ), 'value', 'label', false );
		}

		return data.map( parseChoice );
	}, [] )

	return (
		<Grid
			taggable={ props.taggable }
			sortable={ props.sortable ?? true }
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
