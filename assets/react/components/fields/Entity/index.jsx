import React, { useEffect, useState, useRef, useCallback, useContext } from 'react';
import { Stack, Card, InputGroup, Button } from "react-bootstrap";

import Select from '../../form/Select';
import Fields from "../../form/Fields";
import Webservice from '../Webservice';
import EntityModal from "../../modals/EntityModal";
import useEntities from '../../../hooks/useEntities';

import { TagsContext } from '../../../context/TagsContext';

import { parseId, ucfirst } from '../../../utils/globals';
import { objectMerge, objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';
import { parseTagsObject } from '../../../utils/tags';

export default function Entity( props ) {
	const {
		value,
		config,
		entity: entityType,
		onChange,
	} = props;

	const parseValue = useCallback( ( val ) => {
		if ( 'object' === typeof val ) {
			return val;
		}
		return ( isNaN( val ) || ! val ) ? {} : { id: val };
	}, [] )

	const initCache = () => {
		const cache = {};
		if ( selectedEntity ) {
			cache[ selectedEntity ] = value;
		}
		return cache;
	}

	const [ selectedEntity, setSelectedEntity ] = useState( parseId( value ) );
	const [ choices, choicesCallbacks ] = useEntities( entityType, objectToMappable( props.choices ?? {}, 'id', 'name' ), props.query ?? {} );
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
	}

	const createEntity = ( entity ) => {
		entity.name += ' (new)'; // @todo remove?
		choicesCallbacks.add( entity );
	}

	const searchEntities = async ( search ) => {
		const query = props.query ?? {};

		query.search = { name: search };

		return await choicesCallbacks.fetch( query, false );
	}

	const getEntityConfigFields = () => {
		if ( config ) {

			const tagsContext = useContext( TagsContext );
			let parseTags = {};
			let component;

			const componentProps = {
				value: parseValue( value ),
				onChange: updateFields,
			}

			if ( 'string' === typeof config ) {
				if ( ! choices || ! selectedEntity ) {
					return null;
				}
				const entity = choicesCallbacks.get( selectedEntity );

				parseTags._entity = { ...entity };
				parseTags._config = entity ? entity.config.webservice : {};

				switch ( config ) {
					case 'webservice':
						component = <Webservice webservice={ entity && entity.config.webservice } { ...componentProps } />;
						break;
					default:
						return null; // @todo
				}
			} else {
				if ( selectedEntity ) {
					parseTags._entity = choicesCallbacks.get( selectedEntity );
				}
				parseTags._config = config[ selectedEntity ] ?? config;

				component = <Fields fields={ config[ selectedEntity ] ?? config } { ...componentProps } />;
			}

			const fetchTags = () => {
				if ( ! selectedEntity ) {
					return tagsContext;
				}
				return objectMerge(
					structuredClone( tagsContext ),
					parseTagsObject( { ...props.tags }, parseTags )
				)
			}

			return (
				<TagsContext.Provider value={ fetchTags() }>{ component }</TagsContext.Provider>
			);
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
			action.type = entityType;
		}

		switch ( action.action ) {
			case 'edit':
				if ( ! selectedEntity ) {
					return;
				}
				action.callback = editEntity;
				action.entity = choicesCallbacks.get( selectedEntity );
				break;
			case 'create':
				action.callback = createEntity;
				break;
		}

		return (
			<EntityModal key={ action.action } entity={ selectedEntity } { ...action }><Button variant={ 'outline-' + entityType }>{ action.label ?? ucfirst( action.action ) ?? '' }</Button></EntityModal>
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

	const configFields = getEntityConfigFields();

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
