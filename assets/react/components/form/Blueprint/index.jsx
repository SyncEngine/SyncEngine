import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Badge, Breadcrumb, ListGroup, Stack } from 'react-bootstrap';

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
	const blueprints = objectToMappable( blueprintTypes ?? {}, 'value' );

	const modules = blueprints.reduce( ( acc, item ) => {
		const moduleKey = item._module ?? item.module;

		if ( ! moduleKey ) {
			return acc;
		}

		if ( ! acc[ moduleKey ] ) {
			acc[ moduleKey ] = {
				key: moduleKey,
				label: item.module ?? item._module,
				icon: item.icon,
				count: 0,
			};
		}

		acc[ moduleKey ].count += 1;
		return acc;
	}, {} );

	const modulesList = Object.values( modules );
	const useAdvancedSelection = blueprints.length > 5 || modulesList.length > 2;

	const [ selectedBlueprint, setSelectedBlueprint ] = useState( blueprintConfig._class );
	const [ selectedModule, setSelectedModule ] = useState( null );
	const [ selectedType, setSelectedType ] = useState( null );
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

	const resetModuleFilter = () => {
		setSelectedModule( null );
		setSelectedType( null );
	}

	const selectModule = ( moduleKey ) => {
		setSelectedModule( moduleKey );
		setSelectedType( null );
	}

	const selectType = ( type ) => {
		setSelectedType( type );
	}

	useEffect( () => {
		if ( ! useAdvancedSelection || ! selectedBlueprint ) {
			return;
		}

		const selected = blueprints.find( ( item ) => item.value === selectedBlueprint );

		if ( ! selected ) {
			return;
		}

		setSelectedModule( selected._module ?? selected.module ?? null );
		setSelectedType( selected.type ?? null );
	}, [ useAdvancedSelection, selectedBlueprint, blueprints ] );

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
			const filteredByModule = selectedModule
				? blueprints.filter( ( item ) => ( item._module ?? item.module ) === selectedModule )
				: [];

			const types = filteredByModule.reduce( ( acc, item ) => {
				if ( ! item.type ) {
					return acc;
				}

				if ( ! acc[ item.type ] ) {
					acc[ item.type ] = {
						name: item.type,
						icon: item.icon,
						count: 0,
					};
				}

				acc[ item.type ].count += 1;
				return acc;
			}, {} );

			const typesList = Object.values( types );
			const hasTypeStep = useAdvancedSelection && selectedModule && typesList.length > 0;
			const blueprintsWithoutType = filteredByModule.filter( ( item ) => ! item.type );
			const filteredBlueprints = hasTypeStep && selectedType
				? filteredByModule.filter( ( item ) => item.type === selectedType )
				: filteredByModule;
			const isRootSelection = ! selectedModule && ! selectedType;
			const showManualOption = isRootSelection;
			const showTypeCategories = hasTypeStep && ! selectedType && typesList.length > 0;
			const showUntypedBlueprints = hasTypeStep && ! selectedType && blueprintsWithoutType.length > 0;
			const showBlueprintList = ! useAdvancedSelection || ( useAdvancedSelection && selectedModule && ( ! hasTypeStep || selectedType ) );

			const selectedModuleLabel = selectedModule
				? ( modules[ selectedModule ]?.label ?? selectedModule )
				: null;

			return (
				<Stack gap={2}>
					{ useAdvancedSelection && selectedModule && (
						<Breadcrumb className="mb-0">
							<Breadcrumb.Item
								active={ ! selectedModule }
								onClick={ (e) => {
									suppress( e );
									resetModuleFilter();
								} }
							>
								{ t('All categories') }
							</Breadcrumb.Item>
							{ selectedModule && (
								<Breadcrumb.Item
									active={ ! selectedType }
									onClick={ (e) => {
										suppress( e );
										setSelectedType( null );
									} }
								>
									{ selectedModuleLabel }
								</Breadcrumb.Item>
							) }
							{ selectedType && <Breadcrumb.Item active>{ selectedType }</Breadcrumb.Item> }
						</Breadcrumb>
					) }

					{ showManualOption && (
						<ListGroup>
							<ListGroup.Item action onClick={ (e) => { suppress( e ); selectManual() } }>
								<Info item={ { name: t('Manual'), description: t('Configure all fields manually') } } />
							</ListGroup.Item>
						</ListGroup>
					) }

					{ ! useAdvancedSelection && (
						<Stack gap={1}>
							<small className="text-muted px-1">{ t('Available blueprints') }</small>
							<ListGroup>
								{ blueprints.map( ( item ) => (
									<ListGroup.Item key={ item.value } action onClick={ (e) => { suppress( e ); selectBlueprint( item.value ) } }>
										<Info item={ item } type="info" badge={ item.version } />
									</ListGroup.Item>
								) ) }
							</ListGroup>
						</Stack>
					) }

					{ useAdvancedSelection && isRootSelection && (
						<Stack gap={1}>
							<small className="text-muted px-1">{ t('Modules') }</small>
							<ListGroup>
								{ modulesList.map( ( item ) => (
									<ListGroup.Item key={ item.key } action onClick={ (e) => { suppress( e ); selectModule( item.key ) } }>
										<Stack direction="horizontal" className="justify-content-between align-items-start">
											<Info item={ item } type="info" />
											<Badge bg="secondary">{ item.count }</Badge>
										</Stack>
									</ListGroup.Item>
								) ) }
							</ListGroup>
						</Stack>
					) }

					{ showTypeCategories && (
						<Stack gap={1}>
							<small className="text-muted px-1">{ t('{name} Types', { name: selectedModuleLabel }) }</small>
							<ListGroup>
								{ typesList.map( ( item ) => (
									<ListGroup.Item key={ item.name } action onClick={ (e) => { suppress( e ); selectType( item.name ) } }>
										<Stack direction="horizontal" className="justify-content-between align-items-start">
											<Info item={ { name: item.name, icon: item.icon } } type="info" />
											<Badge bg="secondary">{ item.count }</Badge>
										</Stack>
									</ListGroup.Item>
								) ) }
							</ListGroup>
						</Stack>
					) }

					{ showUntypedBlueprints && (
						<Stack gap={1}>
							<small className="text-muted px-1">{ t('Other') }</small>
							<ListGroup>
								{ blueprintsWithoutType.map( ( item ) => (
									<ListGroup.Item key={ item.value } action onClick={ (e) => { suppress( e ); selectBlueprint( item.value ) } }>
										<Info item={ item } type="info" badge={ item.version } />
									</ListGroup.Item>
								) ) }
							</ListGroup>
						</Stack>
					) }

					{ showBlueprintList && (
						<Stack gap={1}>
							<ListGroup>
								{ filteredBlueprints.map( ( item ) => (
									<ListGroup.Item key={ item.value } action onClick={ (e) => { suppress( e ); selectBlueprint( item.value ) } }>
										<Info item={ item } type="info" badge={ item.version } />
									</ListGroup.Item>
								) ) }
							</ListGroup>
						</Stack>
					) }
				</Stack>
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
					<Button variant="secondary" outline>{ t('Manual') }</Button>
				</ConfirmModal>
			</InputGroup>
			*/ }
			<Info item={ blueprint } type="info" badge={ blueprint.version } />
			<Fields gap={3} name={ entity.ref } fields={ deepClone( blueprint.fields ) } value={ blueprintConfig } onChange={ update } />
		</Stack>
	);
}
