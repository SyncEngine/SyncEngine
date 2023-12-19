import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, InputGroup, ListGroup, Stack } from 'react-bootstrap';

import useBlueprints from '../../../hooks/useBlueprints';

import Fields from '../Fields';
import Info from '../../views/Blocks/Info';
import SelectBlueprint from '../SelectBlueprint';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

import { objectMerge, objectToMappable } from '../../../utils/data';
import { isEmpty, isSet } from '../../../utils/conditionals';

export default function BlueprintControl( props ) {
	const { t } = useTranslation();

	const {
		value,
		manualFields,
		onChange,
	} = props;

	const config = { ...props.value };
	const blueprintConfig = ! isEmpty( value._blueprint ) ? value._blueprint : null;

	const filter = props.filter ?? ( props.entity && { entity: props.entity._entity } ) ?? null;

	const [ blueprintTypes, blueprintCallbacks ] = useBlueprints( props.blueprintTypes ?? null );
	const [ selectedBlueprint, setSelectedBlueprint ] = useState( blueprintConfig._class );
	const [ manual, setManual ] = useState( ( ! selectedBlueprint ) || 'manual' === value._mode );

	const blueprint = ( blueprintTypes && blueprintTypes[ selectedBlueprint ] ) ?? {};

	useEffect( () => {
		const result = blueprintCallbacks.fetch();
		if ( result && filter ) {
			blueprintCallbacks.filter( filter );
		}
	}, [] );

	const selectBlueprint = ( type ) => {
		setSelectedBlueprint( type );
		setManual( false );

		if ( ! config.blueprint ) {
			config.blueprint = { _mode: 'supervised' };
		} else {
			config.blueprint._mode = 'supervised';
		}

		config.blueprint._class = type;
		onChange( config );
	}

	const selectManual = () => {
		if ( config.blueprint ) {
			config.blueprint._mode = 'manual';
		}

		setManual( true );
	}

	const update = ( newValue ) => {
		if ( selectedBlueprint ) {
			config.blueprint = objectMerge( config.blueprint, newValue );
			onChange( config );
		} else {
			onChange( newValue );
		}
	}

	if ( null === blueprintTypes ) {
		return <LoadingPlaceholder />
	}

	if ( ! blueprintConfig ) {

		if ( ! isEmpty( value ) ) {
			// Not in blueprint mode at all.
			return <Fields fields={ manualFields } value={ { ...value } } onChange={ update } />
		}

		if ( ! selectedBlueprint ) {
			return (
				<ListGroup>
					<ListGroup.Item action onClick={ selectManual }>
						<Info item={ { name: t('Manual'), description: t('Expert mode with full configuration') } } />
					</ListGroup.Item>
					{
						objectToMappable( blueprintTypes, 'value' ).map( ( item ) => {
							return (
								<ListGroup.Item key={ item.value } action onClick={ selectBlueprint }>
									<Info item={ item } />
								</ListGroup.Item>
							)
						} )
					}
				</ListGroup>
			)
		}
	}

	return (
		<Stack gap={2}>
			<InputGroup>
				<SelectBlueprint options={ blueprintTypes } value={ selectedBlueprint } onChange={ selectBlueprint } />
				<Button onClick={ selectManual }>{ t('Manual') }</Button>
			</InputGroup>
			<Fields fields={ blueprint.fields } value={ blueprintConfig } onChange={ update } />
		</Stack>
	);
}
