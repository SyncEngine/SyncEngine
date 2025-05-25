import React, { useEffect, useState } from 'react';
import { any, array, bool, element, func, object, oneOfType, string } from 'prop-types';
import { Button } from 'react-bootstrap';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

import Select from '../../fields/Select/Advanced';
import Repeatable from '../../services/Repeatable';
import EntityModal from '../../modals/EntityModal';
import useEntities from '../../../hooks/useEntities';
import { EntityConfig } from '../Entity';

import Header from '../../services/Repeatable/Header';
import { hasValue, isEmpty, isFieldEditable, isFunction, isString } from '../../../utils/conditions';
import { deepClone, mapGetIndex, objectMerge, objectToMappable } from '../../../utils/data';
import { createRefId, parseId, ucfirst } from '../../../utils/globals';
import { suppress } from '../../../utils/events';

function parseOrderFromValue ( value ) {
	return objectToMappable( value ).map( ( row ) => {
		if ( 'object' !== typeof row ) {
			row = {
				id: row,
			}
		}
		if ( ! row.hasOwnProperty( '_ref' ) ) {
			row._ref = createRefId();
		}
		return row;
	} )
}

export default function Entities( props ) {
	const editable = isFieldEditable( props );

	const {
		value = [],
		entity: entityType,
		config,
		activeKey,
		onChange,
		onClick,
		itemProps,
		itemCallbacks,
		itemActions,
		itemToolbar,
		itemHeader,
	} = props;

	const parseConfigsFromItems = ( items ) => {
		return objectToMappable( items ).map
	}

	const [ choices, choicesCallbacks, loading ] = useEntities( entityType, objectToMappable( props.choices ?? [], 'id', 'name' ), props.query ?? {} );
	const [ entities, setEntities ] = useState( parseOrderFromValue( value ) );

	// Make sure the refs are stored upwards if applicable
	useEffect( () => {
		if ( entities !== value ) {
			updateEntities( entities );
		}
	} );

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

	const create = ( props.create || ( props.actions && ( props.actions?.create || hasValue( props.actions, 'create' ) ) ) ) ?? true;

	const items = entities.map( _item => {
		const item =  ( itemProps ) ? isFunction( itemProps ) ? itemProps( _item ) : { ..._item, ...itemProps } : _item;

		const { id, _ref } = item;
		// @todo use loading var from useEntities?
		const itemEntity = choicesCallbacks.get( id, true );

		const callbacks = objectMerge( {
			delete: () => removeEntity( _ref ),
			config: ( config ) => updateEntityConfig( config, _ref ),
			edit: editEntity,
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
				fields: <EntityConfig key={ _ref } item={ item } config={ toolbar.actions.config } entity={ itemEntity } onChange={ callbacks.config } value={ item.config } />
			}
		}

		let headerComponent;
		if ( React.isValidElement( itemHeader ) ) {
			headerComponent = itemHeader;
		} else {
			headerComponent = itemEntity ? (
				<Header
					item={ itemEntity }
					type={ entityType }
					columns={ { ...columns, actions: toolbar } }
					callbacks={ callbacks }
				/>
			) : <LoadingPlaceholder/>;

			if ( isFunction( itemHeader ) ) {
				headerComponent = itemHeader( headerComponent, item, entityType, itemEntity, callbacks, entities );
			}
		}

		if ( isFunction( onClick ) ) {
			return {
				_ref: _ref,
				value: item,
				onClick: ( e ) => {
					suppress( e );
					onClick( e, { ...item, type: entityType, entity: itemEntity, callbacks: callbacks, entities: entities, toolbar: toolbar } );
				},
				header: headerComponent
			}
		}

		const openModal = {
			callback: () => {}
		}

		return {
			_ref: _ref,
			value: item,
			onClick: ( e ) => {
				suppress( e );
				openModal.callback();
			},
			header: itemEntity
				?
				<EntityModal entity={ itemEntity } type={ entityType } callback={ editEntity } savable triggerRef={ openModal }>
					{ headerComponent }
				</EntityModal>
				:
				{ headerComponent }
		}
	} );

	const toolbar = editable && (
		<>
			{ choices &&
				<Select
					{ ...props }
					value={ null }
					// Use map for then initial choices are not fetched entities.
					choices={ choices.map( item => { return ( { value: item.id, label: item.name, ...item } ) } ) }
					variant={ entityType }
					config=""
					onChange={ addEntity }
					async={ ( isEmpty( props.choices ) || ! isEmpty( props.query ) ) }
					onAsyncSearch={ searchEntities }
				/>
			}
			{ create &&
			    <EntityModal action="create" type={ entityType } callback={ createEntity } editCallback={ editEntity }>
				    <Button variant={ 'outline-' + entityType }>
					    { 'string' === typeof props.create ? props.create : 'Create' }
				    </Button>
			    </EntityModal>
			}
		</>
	);

	return (
		<Repeatable
			activeKey={ activeKey }
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
