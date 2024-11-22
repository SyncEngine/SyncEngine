import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { ListGroup, Stack } from 'react-bootstrap';

import useBlueprints from '../../../hooks/useBlueprints';

import Fields from '../Fields';
import Info from '../../views/Blocks/Info';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

import { deepClone, objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditions';
import { suppress } from '../../../utils/events';

export default function BlueprintControl( props ) {
	const { t } = useTranslation();

	const {
		value,
		manualFields,
		onChange,
		entity,
	} = props;

	const blueprintConfig = value._blueprint ?? {};

	const filter = props.filter ?? ( entity && { entity: entity._entity } ) ?? null;

	const [ blueprintTypes, blueprintCallbacks, loading ] = useBlueprints( props.blueprintTypes ?? null, { filter: filter } );
	const [ selectedBlueprint, setSelectedBlueprint ] = useState( blueprintConfig._class );
	const [ manual, setManual ] = useState( ( ! selectedBlueprint && ! isEmpty( value ) ) );

	const blueprint = ( blueprintTypes && blueprintTypes[ selectedBlueprint ] ) ?? {};

	const selectBlueprint = ( type ) => {
		setSelectedBlueprint( type );
		setManual( false );

		if ( ! value._blueprint ) {
			value._blueprint = {};
		}

		value._blueprint._class = type;
		onChange( { ...value } );
	}

	const selectManual = () => {
		setSelectedBlueprint( null );
		setManual( true );
	}

	const update = ( newValue ) => {
		if ( selectedBlueprint ) {
			value._blueprint = newValue;
			value._blueprint._class = selectedBlueprint;
			onChange( { ...value } );
		} else {
			delete newValue._blueprint;
			onChange( { ...newValue } );
		}
	}

	if ( null === blueprintTypes || loading ) {
		return <LoadingPlaceholder />
	}

	if ( manual ) {
		// Not in blueprint mode at all.
		return <Fields name={ entity.ref } fields={ manualFields } value={ value } onChange={ update } />
	}

	if ( isEmpty( blueprintConfig ) ) {

		if ( ! selectedBlueprint ) {
			return (
				<ListGroup>
					<ListGroup.Item action onClick={ (e) => { suppress( e ); selectManual() } }>
						<Info item={ { name: t('Manual'), description: t('Expert mode with full configuration') } } />
					</ListGroup.Item>
					{
						objectToMappable( blueprintTypes, 'value' ).map( ( item ) => {
							return (
								<ListGroup.Item key={ item.value } action onClick={ (e) => { suppress( e ); selectBlueprint( item.value ) } }>
									<Info item={ item } type="info" badge={ item.version } />
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
			{ /*
			<InputGroup>
				<ConfirmModal trigger="onChange" callback={ selectBlueprint } text={ t('You will loose your current configuration') }>
					<SelectBlueprint options={ blueprintTypes } value={ selectedBlueprint } />
				</ConfirmModal>
				<ConfirmModal callback={ selectManual } text={ t('You will loose your current configuration') }>
					<Button variant="outline-secondary">{ t('Manual') }</Button>
				</ConfirmModal>
			</InputGroup>
			*/ }
			<Info item={ blueprint } type="info" badge={ blueprint.version } />
			<Fields name={ entity.ref } fields={ deepClone( blueprint.fields ) } value={ blueprintConfig } onChange={ update } />
		</Stack>
	);
}
