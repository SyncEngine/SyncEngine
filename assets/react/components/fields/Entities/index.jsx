import React, { useState } from 'react';
import { any, bool, func, object, string } from 'prop-types';
import { Button } from 'react-bootstrap';

import Select from '../../fields/Select/Advanced';
import Repeatable from '../../services/Repeatable';
import EntityModal from '../../modals/EntityModal';
import useEntities from '../../../hooks/useEntities';

import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import { mapGetIndex, objectToMappable } from '../../../utils/data';
import { createRefId, parseId, ucfirst } from '../../../utils/globals';
import Header from '../../services/Repeatable/Header';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

export default function Entities( props ) {
	const editable = isFieldEditable( props );

	const {
		value = [],
		entity: entityType,
		onChange,
	} = props;

	const parseOrderFromValue = ( value ) => {
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

	const [ choices, choicesCallbacks, loading ] = useEntities( entityType, objectToMappable( props.choices ?? [], 'id', 'name' ), props.query ?? {} );
	const [ order, setOrder ] = useState( parseOrderFromValue( value ) );

	const updateOrder = ( order ) => {
		setOrder( order );
		onChange( order.map( ( item ) => item.id ) );
	}

	const editEntity = ( entity ) => {
		choicesCallbacks.update( entity );
	}

	const addEntity = ( id ) => {
		if ( ! id ) {
			return;
		}
		let newOrder = [ ...order ];
		newOrder.push( { id: id, _ref: createRefId() } );
		updateOrder( newOrder );
	}

	const createEntity = ( entity ) => {
		choicesCallbacks.add( entity );
		addEntity( parseId( entity ) );
	}

	const removeEntity = async ( _ref ) => {
		let newOrder = [ ...order ];
		newOrder.splice( mapGetIndex( order, _ref, '_ref' ), 1 );
		updateOrder( newOrder );
	}

	const searchEntities = async ( search ) => {
		const query = props.query ?? {};

		query.search = { name: search };

		return await choicesCallbacks.fetch( query, false );
	}

	const columns = { info: { classes: 'flex-grow-1', badge: ucfirst( entityType ) + ' #{id}' }, ...props.columns };
	if ( ! columns.actions ) {
		columns.actions = props.actions ?? { buttons: false, actions: [ 'delete' ] };
	}

	const create = ( props.create || columns.actions.create ) ?? true;

	const items = order.map( item => {
		const { id, _ref } = item;
		// @todo use loading var from useEntities?
		const itemEntity = choicesCallbacks.get( id, true );

		const callbacks = {
			delete: () => removeEntity( _ref ),
		}

		const openModal = {
			callback: () => {}
		}

		return {
			_ref: _ref,
			value: item,
			onClick: ( e ) => {
				e.preventDefault();
				e.stopPropagation();
				openModal.callback();
			},
			header: itemEntity
				?
				<EntityModal entity={ itemEntity } type={ entityType } callback={ editEntity } savable triggerRef={ openModal }>
					<Header
						item={ itemEntity }
						type={ entityType }
						actions={ columns.actions }
						columns={ columns }
						callbacks={ callbacks }
					/>
				</EntityModal>
				:
				<LoadingPlaceholder/>
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
			items={ items }
			toolbar={ toolbar }
			inline={ true }
			max={ props.max }
			editable={ editable }
			sortable={ props.sortable ?? editable }
			disabled={ props.disabled }
			reorderCallback={ updateOrder }
		/>
	);
}

Entities.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	entity: string,
	query: object,
	choices: object,
	columns: object,
	actions: object,
	create: bool|string,
}
