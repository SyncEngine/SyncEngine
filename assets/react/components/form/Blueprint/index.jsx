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
import ConfirmModal from '../../modals/ConfirmModal';

export default function BlueprintControl( props ) {
	const { t } = useTranslation();

	const {
		value,
		manualFields,
		onChange,
	} = props;

	const config = { ...props.value };
	const blueprintConfig = value._blueprint ?? {};

	const filter = props.filter ?? ( props.entity && { entity: props.entity._entity } ) ?? null;

	const [ blueprintTypes, blueprintCallbacks, loading ] = useBlueprints( props.blueprintTypes ?? null );
	const [ selectedBlueprint, setSelectedBlueprint ] = useState( blueprintConfig._class );
	const [ manual, setManual ] = useState( ( selectedBlueprint && ! isEmpty( value ) ) );

	const blueprint = ( blueprintTypes && blueprintTypes[ selectedBlueprint ] ) ?? {};

	useEffect( () => {
		const result = blueprintCallbacks.fetch();
		if ( result && filter ) {
			blueprintCallbacks.filter( filter );
		}
	}, [ filter ] );

	const selectBlueprint = ( type ) => {
		setSelectedBlueprint( type );
		setManual( false );

		if ( ! config._blueprint ) {
			config._blueprint = {};
		}

		config._blueprint._class = type;
		onChange( config );
	}

	const selectManual = () => {
		delete config._blueprint;
		setManual( true );
	}

	const update = ( newValue ) => {
		if ( selectedBlueprint ) {
			config._blueprint = objectMerge( config._blueprint, newValue );
			onChange( config );
		} else {
			onChange( newValue );
		}
	}

	if ( null === blueprintTypes || loading ) {
		return <LoadingPlaceholder />
	}

	if ( ! isEmpty( value ) || manual ) {
		// Not in blueprint mode at all.
		return <Fields fields={ manualFields } value={ { ...value } } onChange={ update } />
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
				<ConfirmModal trigger="onChange" callback={ selectBlueprint } text={ t('You will loose your current configuration') }>
					<SelectBlueprint options={ blueprintTypes } value={ selectedBlueprint } />
				</ConfirmModal>
				<ConfirmModal callback={ selectManual } text={ t('You will loose your current configuration') }>
					<Button>{ t('Manual') }</Button>
				</ConfirmModal>
			</InputGroup>
			<Fields fields={ structuredClone( blueprint.fields ) } value={ blueprintConfig } onChange={ update } />
		</Stack>
	);
}
