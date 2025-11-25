import React, { useEffect, useState } from 'react';
import { any, array, bool, element, func, object, oneOfType, string } from 'prop-types';
import { Button, InputGroup } from 'react-bootstrap';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

import Select from '../../fields/Select/Advanced';
import Repeatable from '../../services/Repeatable';
import EntityModal from '../../modals/EntityModal';
import useEntities from '../../../hooks/useEntities';
import Entity, { EntityConfig } from '../Entity';

import Header from '../../services/Repeatable/Header';
import { hasValue, isEmpty, isFieldEditable, isFunction, isNumeric, isString } from '../../../utils/conditions';
import { deepClone, mapGetIndex, objectMerge, objectToMappable } from '../../../utils/data';
import { createRefId, parseId, ucfirst } from '../../../utils/globals';
import { suppress } from '../../../utils/events';
import Help from '../../form/Help';
import Icon from '../../partials/Icon';

function parseValue( value, entityType = '' ) {
	return objectToMappable( value ).map( ( row ) => parseEntity( row, entityType ) );
}

function parseEntity( item, entityType ) {
	if ( 'object' !== typeof item ) {
		item = {
			id: item,
		}
	}
	if ( ! item.id && isNumeric( item[ entityType ] ) ) {
		item.id = item[ entityType ];
	}
	if ( ! item.hasOwnProperty( '_ref' ) ) {
		item._ref = createRefId();
	}
	return item;
}

export default function Entities( props ) {
	const editable = isFieldEditable( props );

	const {
		value = [],
		entity: entityType,
		activeKey,
		onChange,
		onClick,
		itemProps,
		itemCallbacks,
		itemActions,
		itemToolbar,
		itemHeader,
	} = props;

	const [ choices, choicesCallbacks, loading ] = useEntities( entityType, objectToMappable( props.choices ?? [], 'id', 'name' ), props.query ?? {} );
	const [ entities, setEntities ] = useState( parseValue( value, entityType ) );

	// Make sure the refs are stored upwards if applicable
	useEffect( () => {
		updateEntities( entities );
	}, [] );

	const updateEntities = ( entities ) => {
		if ( ! Array.isArray( entities ) ) {
			throw new Error( 'Not an array' );
		}
		setEntities( entities );
		if ( props.config ) {
			onChange( entities );
		} else {
			onChange( entities.map( ( item ) => item.id ) );
		}
	}

	const setEntity = ( index, entity ) => {
		let newEntities = [ ...entities ];
		newEntities[ index ] = parseEntity( entity );
		updateEntities( newEntities );
	}

	const updateEntityConfig = ( config, _ref ) => {
		let newEntities = [ ...entities ];
		newEntities[ mapGetIndex( entities, _ref, '_ref' ) ].config = config;
		updateEntities( newEntities );
	}

	const editEntity = ( entity ) => {
		choicesCallbacks.update( entity );
	}

	const addEntity = ( id ) => {
		if ( ! id ) {
			return;
		}
		let newEntities = [ ...entities ];
		newEntities.push( { id: id, _ref: createRefId() } );
		updateEntities( newEntities );
	}

	const createEntity = ( entity ) => {
		choicesCallbacks.add( entity );
		addEntity( parseId( entity ) );
	}

	const removeEntity = async ( _ref ) => {
		let newEntities = [ ...entities ];
		newEntities.splice( mapGetIndex( entities, _ref, '_ref' ), 1 );
		updateEntities( newEntities );
	}

	const searchEntities = async ( search ) => {
		const query = props.query ?? {};

		query.search = { name: search };

		return await choicesCallbacks.fetch( query, false );
	}

	const columns = parseColumns( props );

	const create = ( props.create || ( props.actions && ( props.actions?.create || hasValue( props.actions, 'create' ) ) ) ) ?? true;

	const items = entities.map( ( item, index ) => parseItem( {
		index: index,
		item: item,
		entityType: entityType,
		columns: columns,
		entities: entities,
		choices: choices,
		callbacks: {
			get: choicesCallbacks.get,
			create: createEntity,
			add: addEntity,
			edit: editEntity,
			set: setEntity,
			search: searchEntities,
			remove: removeEntity,
			choices: choicesCallbacks,
			updateConfig: updateEntityConfig,
		},
		events: {
			onClick: onClick,
		},
		modifiers: {
			itemProps: itemProps,
			itemCallbacks: itemCallbacks,
			itemActions: itemActions,
			itemToolbar: itemToolbar,
			itemHeader: itemHeader,
		}
	} ) );

	const toolbar = editable && (
		<Toolbar
			entityType={ entityType }
			selectProps={ props }
			selectChoices={ choices }
			selectQuery={ props.query }
			create={ create }
			callbacks={ {
				create: createEntity,
				add: addEntity,
				edit: editEntity,
				search: searchEntities,
			} }
		/>
	);

	return (
		<Repeatable
			activeKey={ activeKey }
			className={ props.className }
			items={ items }
			toolbar={ toolbar }
			inline={ true }
			max={ props.max }
			editable={ editable }
			sortable={ props.sortable ?? editable }
			disabled={ props.disabled }
			reorderCallback={ updateEntities }
		/>
	);
}

function parseColumns( props ) {
	const entityType = props.entityType ?? props.entity;
	const columns = { info: { classes: 'flex-grow-1', badge: ucfirst( entityType ) + ' #{{id}}' }, ...props.columns };
	if ( ! columns.toolbar ) {
		columns.toolbar = props.toolbar ?? { buttons: false, actions: [ 'delete' ] };
	}
	if ( props.config ) {
		// @todo Improve this ugly piece of code. Create util function to convert array actions to object actions (true value)
		if ( Array.isArray( columns.toolbar.actions ) ) {
			columns.toolbar.actions = Object.fromEntries( columns.toolbar.actions.map( ( action => isString( action ) ? [ action, true ] : [ action.action, action ] ) ) );
		}
		columns.toolbar.actions = { ...{ config: props.config }, ...columns.toolbar.actions, ...{ config: props.config } };
	}

	return columns;
}

function parseItem( args ) {

	const {
		index,
		entityType,
		entities,
		columns,
		modifiers,
		events,
		callbacks: entityCallbacks,
		choices,
		loading,
	} = args;

	const {
		itemProps,
		itemCallbacks,
		itemActions,
		itemToolbar,
		itemHeader,
	} = modifiers;

	const item =  ( itemProps ) ? isFunction( itemProps ) ? itemProps( args.item ) : { ...args.item, ...itemProps } : args.item;

	const { id, _ref } = item;

	// @todo use loading var from useEntities?
	const itemEntity = entityCallbacks.get( id, true );

	const callbacks = objectMerge( {
		delete: () => entityCallbacks.remove( _ref ),
		config: ( config ) => entityCallbacks.updateConfig( config, _ref ),
		edit: entityCallbacks.edit,
	}, isFunction( itemCallbacks ) ? itemCallbacks( item ) : itemCallbacks );

	let toolbar = deepClone( columns.toolbar );
	if ( itemToolbar ) {
		if ( isFunction( itemToolbar ) ) {
			toolbar = itemToolbar( toolbar, item, entityType, itemEntity, callbacks, entities );
		} else if ( React.isValidElement( itemToolbar ) ) {
			toolbar = itemToolbar;
		}
	}

	if ( ! React.isValidElement( toolbar ) && toolbar.actions && itemActions ) {
		toolbar.actions = isFunction( itemActions ) ? itemActions( toolbar.actions, item, entityType, itemEntity, callbacks, entities ) : { ...toolbar.actions, ...itemActions };
	}

	if ( toolbar.actions?.config && ! React.isValidElement( toolbar.actions.config ) && ! isFunction( toolbar.actions.config ) ) {
		toolbar.actions.config = {
			action: 'config',
			icon: 'config',
			fields: (
				<EntityConfig
					key={ _ref }
					item={ item }
					config={ toolbar.actions.config }
					entity={ itemEntity }
					onChange={ callbacks.config }
					value={ item.config }
				/>
			)
		}
	}

	let headerComponent;
	if ( React.isValidElement( itemHeader ) ) {
		headerComponent = itemHeader;
	} else {
		headerComponent = loading ? <LoadingPlaceholder/> : itemEntity ? (
			<Header
				item={ itemEntity }
				type={ entityType }
				columns={ { ...columns, actions: toolbar } }
				callbacks={ callbacks }
			/>
		) : (
			<Entity
				entity={ entityType }
				choices={ choices }
				onChange={ val => entityCallbacks.set( index, val ) }
			/>
		);

		if ( isFunction( itemHeader ) ) {
			headerComponent = itemHeader( headerComponent, item, entityType, itemEntity, callbacks, entities );
		}
	}

	let onClick;
	if ( itemEntity ) {
		if ( isFunction( events.onClick ) ) {
			onClick = ( e ) => {
				suppress( e );
				events.onClick( e, { ...item, type: entityType, entity: itemEntity, entityCallbacks: entityCallbacks, callbacks: callbacks, entities: entities, toolbar: toolbar } );
			};
		} else {
			const openModal = {
				callback: () => {}
			}
			onClick = ( e ) => {
				suppress( e );
				openModal.callback();
			};
			if ( itemEntity ) {
				headerComponent = (
					<EntityModal entity={ itemEntity } type={ entityType } callback={ callbacks.edit } savable triggerRef={ openModal }>
						{ headerComponent }
					</EntityModal>
				)
			}
		}
	}

	return {
		_ref: _ref,
		value: item,
		onClick: onClick,
		header: headerComponent,
	}
}

function Toolbar( props ) {
	const {
		entityType,
		selectProps,
		selectChoices,
		selectQuery,
		create,
		callbacks,
	} = props;

	const prefixClasses = 'z-3 border-' + entityType + ' bg-' + entityType + '-subtle';

	return <>
		{ props.help ?
			<Help text={ props.help } icon={ props.icon ?? entityType } className={ prefixClasses } inputGroup={ true } />
			:
			<InputGroup.Text className={ prefixClasses }><Icon icon={ props.icon ?? entityType } /></InputGroup.Text>
		}
		{ selectChoices &&
			<Select
				{ ...selectProps }
				value={ null }
				// Use map for then initial choices are not fetched entities.
				choices={ selectChoices.map( item => { return ( { value: item.id, label: item.name, ...item } ) } ) }
				variant={ entityType }
				config=""
				onChange={ callbacks.add }
				async={ ( isEmpty( selectChoices ) || ! isEmpty( selectQuery ) ) }
				onAsyncSearch={ callbacks.search }
			/>
		}
		{ create &&
			<EntityModal action="create" type={ entityType } callback={ callbacks.create } editCallback={ callbacks.edit }>
				<Button variant={ 'outline-' + entityType }>
					{ 'string' === typeof create ? create : 'Create' }
				</Button>
			</EntityModal>
		}
	</>
}

Entities.propTypes = {
	onChange: func,
	activeKey: string,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	entity: string,
	query: object,
	config: oneOfType( [ string, object, array ] ),
	choices: oneOfType( [ object, array ] ),
	columns: oneOfType( [ object, array ] ),
	actions: oneOfType( [ object, array ] ),
	create: oneOfType( [ bool, string ] ),
	itemProps: oneOfType( [ object, func ] ),
	itemCallbacks: oneOfType( [ object, func ] ),
	itemActions: oneOfType( [ object, func ] ),
	itemToolbar: oneOfType( [ element, func ] ),
	itemHeader: oneOfType( [ element, func ] ),
}
