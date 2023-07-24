import React, { useEffect, useState, useRef } from 'react';
import { Stack, Card, InputGroup, Button } from "react-bootstrap";
import Fields from "../../form/Fields";
import EntityModal from "../../modals/EntityModal";
import Select from '../../form/Select';
import useEntities from '../../../hooks/useEntities';
import { ucfirst } from "../../../utils/globals";
import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';

export default function Entity( props ) {
	const {
		value,
		config,
		entity,
		onChange,
	} = props;

	const parseEntityValue = ( val ) => {
		if ( 'object' === typeof val ) {
			val = val.id;
		}
		return ( isNaN( val ) || ! val ) ? '' : val;
	}

	const parseValue = ( val ) => {
		if ( 'object' === typeof val ) {
			return val;
		}
		return ( isNaN( val ) || ! val ) ? {} : { id: val };
	}

	const initCache = () => {
		const cache = {};
		if ( selectedEntity ) {
			cache[ selectedEntity ] = value;
		}
		return cache;
	}

	const [ selectedEntity, setSelectedEntity ] = useState( parseEntityValue( value ) );
	const [ choices, fetchChoices, updateChoices, addChoice ] = useEntities( entity, objectToMappable( props.choices, 'id', 'name' ) );
	const [ cache, setCache ] = useState( initCache() );

	const initialRender = useRef( true );
	useEffect( () => {
		if ( initialRender.current ) {
			initialRender.current = false;
		} else {
			if ( ! config ) {
				onChange( selectedEntity );
			} else if ( selectedEntity ) {
				onChange( { ...cache[ selectedEntity ] ?? {}, id: selectedEntity } );
			} else {
				onChange( {} );
			}
		}
	}, [ selectedEntity, cache ] );

	const selectEntity = ( newValue ) => {
		setSelectedEntity( parseEntityValue( newValue ) );
	}

	const updateFields = ( newValue ) => {
		let newCache = { ...cache };

		newCache[ selectedEntity ] = {
			...newValue,
			id: selectedEntity,
		}

		setCache( newCache );
	}

	const editEntity = ( entity ) => {
		updateChoices( entity );
	}

	const addEntity = ( entity ) => {
		entity.name += ' (new)'; // @todo remove?
		addChoice( entity );
	}

	const searchEntities = ( search ) => {
		// Do not update state, the AsyncSelect has an internal state handler.
		fetchChoices( { search: { name: search } }, false );
	}

	const getEntityConfigFields = () => {
		if ( config ) {
			return config[ selectedEntity ] ?? null;
		}
		return null;
	}

	const actions = props.actions && props.actions.map( ( action ) => {
		if ( 'string' === typeof action ) {
			action = {
				action: action,
			};
		}

		if ( ! action.action ) {
			return;
		}

		if ( ! action.type ) {
			action.type = entity;
		}

		switch ( action.action ) {
			case 'edit':
				if ( ! selectedEntity ) {
					return;
				}
				action.callback = editEntity;
				action.id = selectedEntity;
				action.name = choices[ selectedEntity ];
				break;
			case 'create':
				action.callback = addEntity;
				break;
		}

		return (
			<EntityModal key={ action.action } entity={ selectedEntity } { ...action }><Button variant={ 'outline-' + entity }>{ action.label ?? ucfirst( action.action ) ?? '' }</Button></EntityModal>
		);
	} );

	const select =
		<InputGroup>
			<Select
				{...props}
				value={ selectedEntity }
				choices={ choices.map( item => { return ( { value: item.id, label: item.name } ) } ) }
				variant={ entity }
				config=""
				onChange={ selectEntity }
				async={ ( isEmpty( props.choices ) || ! isEmpty( props.query ) ) }
				onSearch={ searchEntities }
			/>
			{ actions }
		</InputGroup>;

	return (
		<Stack gap={0}>
			{ ! getEntityConfigFields() &&
				select
			}
			{ getEntityConfigFields() &&
				<Card className="bg-body border-0">
					<Card.Header className="border-0 p-0">{ select }</Card.Header>
					<Card.Body className="border p-3">
						<Fields fields={ getEntityConfigFields() } value={ parseValue( value ) } onChange={ updateFields } />
					</Card.Body>
				</Card>
			}
		</Stack>
	);
}
