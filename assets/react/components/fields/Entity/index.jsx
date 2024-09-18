import React, { useContext, useEffect, useRef, useState } from 'react';
import { Button, Card, InputGroup, Stack } from 'react-bootstrap';

import Select from '../../fields/Select/Advanced';
import Fields from '../../form/Fields';
import Webservice from '../Webservice';
import EntityModal from '../../modals/EntityModal';
import useEntities from '../../../hooks/useEntities';

import { TagsContext } from '../../../context/TagsContext';

import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import { parseId, ucfirst } from '../../../utils/globals';
import { deepClone, objectMerge, objectToMappable } from '../../../utils/data';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import { parseTagsObject } from '../../../utils/tags';
import Icon from '../../partials/Icon';
import { EntityContext } from '../../../context/EntityContext';

function parseValue( val ) {
	if ( 'object' === typeof val ) {
		return val;
	}
	return ( isNaN( val ) || ! val ) ? {} : { id: val };
}

export default function Entity( props ) {
	const editable = isFieldEditable( props );
	const {
		value,
		config,
		entity: entityType,
		onChange,
	} = props;

	const initCache = () => {
		const cache = {};
		if ( selectedEntity ) {
			cache[ selectedEntity ] = value;
		}
		return cache;
	}

	const [ selectedEntity, setSelectedEntity ] = useState( parseId( value ) );
	const [ choices, choicesCallbacks, loading ] = useEntities( entityType, objectToMappable( props.choices ?? {}, 'id', 'name' ), props.query ?? {} );
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
		setSelectedEntity( parseId( newValue ) );
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
		choicesCallbacks.update( entity );
		selectEntity( entity );
	}

	const createEntity = ( entity ) => {
		choicesCallbacks.add( entity );
		selectEntity( entity );
	}

	const searchEntities = async ( search ) => {
		const query = props.query ?? {};

		query.search = { name: search };

		return await choicesCallbacks.fetch( query, false );
	}

	if ( loading ) {
		return <LoadingPlaceholder />
	}

	const getEntityConfigFields = () => {
		return ( config &&
			<EntityConfig
				config={ config }
				value={ value }
				onChange={ updateFields }
				entity={ ( selectedEntity && choices ) && choicesCallbacks.get( selectedEntity ) }
			/>
		)
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
			action.type = entityType;
		}

		switch ( action.action ) {
			case 'edit':
				if ( ! selectedEntity ) {
					return;
				}
				action.callback = editEntity;
				action.label = action.label ?? <Icon icon="edit" />;
				action.savable = action.savable ?? true;
				action.entity = choicesCallbacks.get( selectedEntity );
				break;
			case 'create':
				action.callback = createEntity;
				action.savable = action.savable ?? true;
				if ( action.savable ) {
					action.editCallback = editEntity;
				}
				break;
		}

		return (
			<EntityModal key={ action.action } entity={ selectedEntity } { ...action }>
				<Button variant={ 'outline-' + entityType }>{ action.label ?? ucfirst( action.action ) ?? '' }</Button>
			</EntityModal>
		);
	} );

	const select =
		<InputGroup className="bg-body">
			<Select
				{ ...props }
				value={ selectedEntity }
				// Use map for then initial choices are not fetched entities.
				choices={ choices.map( item => { return ( { value: item.id, label: item.name, ...item } ) } ) }
				variant={ entityType }
				config=""
				onChange={ selectEntity }
				async={ ( isEmpty( props.choices ) || ! isEmpty( props.query ) ) }
				onAsyncSearch={ searchEntities }
			/>
			{ actions }
		</InputGroup>;

	const configFields = editable && getEntityConfigFields();

	return (
		<Stack gap={0}>
			{ ! configFields ? select :
				<Card className="bg-body border-0">
					<Card.Header className="border-0 p-0">{ select }</Card.Header>
					<Card.Body className="border p-3">{ configFields }</Card.Body>
				</Card>
			}
		</Stack>
	);
}

export function EntityConfig( props ) {
	const {
		entity,
		config,
		value,
		onChange,
		tagsContext = useContext( TagsContext ),
	} = props;

	if ( ! entity ) {
		return;
	}

	let parseTags = {};
	let component;

	const componentProps = {
		value: parseValue( value ),
		onChange: onChange,
	}

	if ( 'string' === typeof config ) {
		if ( ! entity ) {
			return null;
		}

		parseTags._entity = { ...entity };
		parseTags._config = entity ? entity.config.webservice : {};

		switch ( config ) {
			case 'webservice:send':
				component = <Webservice webservice={ entity && entity.config.webservice } { ...componentProps } mode="send" />;
				break;
			case 'webservice:retrieve':
				component = <Webservice webservice={ entity && entity.config.webservice } { ...componentProps } mode="retrieve" />;
				break;
			default:
				return null; // @todo
		}
	} else {
		parseTags._entity = entity;
		parseTags._config = config;

		component = <Fields fields={ config } { ...componentProps } />;
	}

	const fetchTags = () => {
		if ( ! entity ) {
			return tagsContext;
		}
		return objectMerge(
			deepClone( tagsContext ),
			parseTagsObject( { ...props.tags }, parseTags )
		)
	}

	return (
		<EntityContext.Provider value={ entity }>
			<TagsContext.Provider value={ fetchTags() }>
				{ component }
			</TagsContext.Provider>
		</EntityContext.Provider>
	);
}
