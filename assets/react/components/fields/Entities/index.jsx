import React, { useState } from 'react';
import { Button, Stack } from "react-bootstrap";

import Select from '../../form/Select';
import Repeatable from "../../services/Repeatable";
import EntityModal from '../../modals/EntityModal';
import useEntities from '../../../hooks/useEntities';

import { isEmpty } from '../../../utils/conditionals';
import { objectToMappable } from "../../../utils/data";
import { createRefId, ucfirst } from '../../../utils/globals';
import Header from '../../services/Repeatable/Header';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

export default function Entities( props ) {

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

	const [ choices, choicesCallbacks ] = useEntities( entityType, objectToMappable( props.choices ?? [], 'id', 'name' ), props.query ?? {} );
	const [ order, setOrder ] = useState( parseOrderFromValue( value ) );

	const getOrderRefs = () => order.map( item => item._ref );
	const getOrderIndex = ( _ref ) => getOrderRefs().indexOf( _ref );

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
		newOrder.push( { id: id , _ref: createRefId() } );
		updateOrder( newOrder );
	}

	const removeEntity = async ( _ref ) => {
		let newOrder = [ ...order ];
		newOrder.splice( getOrderIndex( _ref ), 1 );
		updateOrder( newOrder );
	}

	const searchEntities = async ( search ) => {
		const query = props.query ?? {};

		query.search = { name: search };

		return await choicesCallbacks.fetch( query );
	}

	const columns = { info: { classes: 'flex-grow-1', badge: ucfirst( entityType ) + ' #{id}' }, ...props.columns };
	if ( ! columns.actions ) {
		columns.actions = props.actions ?? { button: 'link', actions: [ 'delete' ] };
	}

	const create = ( props.create || columns.actions.create ) ?? true;

	const items = order.map( item => {
		const { id, _ref } = item;
		const itemEntity = choicesCallbacks.get( id );

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
				<EntityModal entity={ itemEntity } type={ entityType } callback={ editEntity } triggerRef={ openModal }>
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

	const toolbar = (
		<>
			{ choices &&
				<Select
					{...props}
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
			    <EntityModal type={ entityType } callback={ addEntity }>
				    <Button variant={ 'outline-' + entityType }>
					    { 'string' === typeof props.create ? props.create : 'Create' }
				    </Button>
			    </EntityModal>
			}
		</>
	);

	return (
		<Stack gap={2}>
			<Repeatable items={ items } toolbar={ toolbar } inline={ true } sortable={ true } reorderCallback={ updateOrder } />
		</Stack>
	);
}
