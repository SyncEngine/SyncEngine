import React, { useState } from 'react';
import { any, array, bool, func, object, oneOfType, string } from 'prop-types';
import { Button } from 'react-bootstrap';

import Select from '../../fields/Select/Advanced';
import Repeatable from '../../services/Repeatable';
import EntityModal from '../../modals/EntityModal';
import useEntities from '../../../hooks/useEntities';

import { hasValue, isEmpty, isFieldEditable } from '../../../utils/conditions';
import { mapGetIndex, objectToMappable } from '../../../utils/data';
import { createRefId, parseId, ucfirst } from '../../../utils/globals';
import Header from '../../services/Repeatable/Header';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import { suppress } from '../../../utils/events';

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
	const [ entities, setEntities ] = useState( parseOrderFromValue( value ) );

	const updateEntities = ( entities ) => {
		if ( ! Array.isArray( entities ) ) {
			throw new Error( 'Not an array' );
		}
		setEntities( entities );
		onChange( entities.map( ( item ) => item.id ) );
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

	const columns = { info: { classes: 'flex-grow-1', badge: ucfirst( entityType ) + ' #{id}' }, ...props.columns };
	if ( ! columns.actions ) {
		columns.actions = props.actions ?? { buttons: false, actions: [ 'delete' ] };
	}

	const create = ( props.create || props.actions?.create || hasValue( props.actions, 'create' ) ) ?? true;

	const items = entities.map( item => {
		const { id, _ref } = item;
		// @todo use loading var from useEntities?
		const itemEntity = choicesCallbacks.get( id, true );

		const callbacks = {
			delete: () => removeEntity( _ref ),
			edit: editEntity,
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
			reorderCallback={ updateEntities }
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
	choices: oneOfType( [ object, array ] ),
	columns: oneOfType( [ object, array ] ),
	actions: oneOfType( [ object, array ] ),
	create: oneOfType( [ bool, string ] ),
}
