import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, InputGroup, ListGroup, Stack } from 'react-bootstrap';

import useBlueprints from '../../../hooks/useBlueprints';

import Fields from '../Fields';
import Info from '../../views/Blocks/Info';
import SelectBlueprint from '../SelectBlueprint';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

import { objectMerge, objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditions';
import ConfirmModal from '../../modals/ConfirmModal';

export default function BlueprintControl( props ) {
	const { t } = useTranslation();

	const {
		value,
		manualFields,
		onChange,
	} = props;

	const blueprintConfig = value._blueprint ?? {};

	const filter = props.filter ?? ( props.entity && { entity: props.entity._entity } ) ?? null;

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
		onChange( value );
	}

	const selectManual = () => {
		setSelectedBlueprint( null );
		setManual( true );
	}

	const update = ( newValue ) => {
		if ( selectedBlueprint ) {
			value._blueprint = newValue;
			value._blueprint._class = selectedBlueprint;
			onChange( value );
		} else {
			delete newValue._blueprint;
			onChange( newValue );
		}
	}

	if ( null === blueprintTypes || loading ) {
		return <LoadingPlaceholder />
	}

	if ( manual ) {
		// Not in blueprint mode at all.
		return <Fields fields={ manualFields } value={ value } onChange={ update } />
	}

	if ( isEmpty( blueprintConfig ) ) {

		if ( ! selectedBlueprint ) {
			return (
				<ListGroup>
					<ListGroup.Item action onClick={ (e) => { e.preventDefault(); selectManual() } }>
						<Info item={ { name: t('Manual'), description: t('Expert mode with full configuration') } } />
					</ListGroup.Item>
					{
						objectToMappable( blueprintTypes, 'value' ).map( ( item ) => {
							return (
								<ListGroup.Item key={ item.value } action onClick={ (e) => { e.preventDefault(); selectBlueprint( item.value ) } }>
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
			<Fields fields={ structuredClone( blueprint.fields ) } value={ blueprintConfig } onChange={ update } />
		</Stack>
	);
}
