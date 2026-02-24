import React, { useContext, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { any, array, bool, func, object, oneOfType, string } from 'prop-types';
import { Card, InputGroup, Stack } from 'react-bootstrap';

import Button from '../../partials/Button';
import Icon from '../../partials/Icon';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import Info from '../../views/Blocks/Info';
import Actions from '../../views/Blocks/Actions';

import useEntities from '../../../hooks/useEntities';

import Fields from '../../form/Fields';
import Help from '../../form/Help';
import Select from '../../fields/Select/Advanced';
import Webservice from '../Webservice';

import { TagsContext } from '../../../context/TagsContext';
import { EntityContext } from '../../../context/EntityContext';

import { parseId } from '../../../utils/globals';
import { deepClone, objectMerge, objectToMappable } from '../../../utils/data';
import { isConfigured, isEmpty, isFieldEditable, isFunction } from '../../../utils/conditions';
import { parseTag, parseTagsObject } from '../../../utils/tags';

function parseValue( val ) {
	if ( 'object' === typeof val ) {
		return val;
	}
	return ( isNaN( val ) || ! val ) ? {} : { id: val };
}

export default function Entity( props ) {
	const editable = isFieldEditable( props );
	const {
		selectable = true,
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

	let configLocation = '';
	const getEntityConfigFields = ( location, props = {} ) => {
		if ( location ) {
			configLocation = location;
		}
		return ( config &&
			<EntityConfig
				config={ config }
				value={ value }
				onChange={ updateFields }
				entity={ ( selectedEntity && choices ) && choicesCallbacks.get( selectedEntity ) }
				{ ...props }
			/>
		)
	}

	const normalizedActions = props.actions && normalizeEntityActions( {
		actions: props.actions,
		entity: selectedEntity,
		entityType: entityType,
		actionCallbacks: {
			edit: editEntity,
			create: createEntity,
			config: updateFields,
		},
		entityCallbacks: choicesCallbacks,
		configValue: value,
		configForm: ( props ) => getEntityConfigFields( 'actions', props )
	} );

	const actions = normalizedActions && (
		<Actions
			actions={ normalizedActions }
			callbacks={ {
				edit: editEntity,
				create: createEntity,
				config: updateFields,
			} }
			item={ selectedEntity && choicesCallbacks.get( selectedEntity ) }
			type={ entityType }
			buttons={ true }
			variant={ 'outline-' + entityType }
			subtle={ false }
			view="grouped"
		/>
	);

	const prefixClasses = 'z-3 border-' + entityType + ' bg-' + entityType + '-subtle';

	const select =
		<InputGroup className="bg-body flex-nowrap">
			{ props.prefix && ( React.isValidElement( props.prefix ) ? props.prefix : <InputGroup.Text className={ prefixClasses }>{ props.prefix }</InputGroup.Text> ) }
			{ props.help ?
				<Help text={ props.help } icon={ props.icon ?? entityType } className={ prefixClasses } inputGroup={ true } />
				:
				<InputGroup.Text className={ prefixClasses }><Icon icon={ props.icon ?? entityType } /></InputGroup.Text>
			}
			{ selectable ?
				<Select
					{ ...props }
					prefix={ null }
					help={ null }
					value={ selectedEntity }
					// Use map for then initial choices are not fetched entities.
					choices={ choices.map( item => { return ( { value: item.id, label: item.name, ...item } ) } ) }
					variant={ entityType }
					config=""
					onChange={ selectEntity }
					async={ ( isEmpty( props.choices ) || ! isEmpty( props.query ) ) }
					onAsyncSearch={ searchEntities }
				/>
				:
				<InputGroup.Text>
					<Info type={ entityType } item={ ( selectedEntity && choices ) ? choicesCallbacks.get( selectedEntity ) ?? {} : {} } />
				</InputGroup.Text>
			}
			{ actions }
		</InputGroup>;

	const configForm = ( editable && ! configLocation ) && getEntityConfigFields();

	return (
		<Stack gap={0}>
			{ ! configForm ? select :
				<Card className="fields-container border-0">
					<Card.Header className="p-0 border-0 fields-container-header">{ select }</Card.Header>
					<Card.Body className={ 'p-3 border border-top-0 border-' + entityType }>{ configForm }</Card.Body>
				</Card>
			}
		</Stack>
	);
}

Entity.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	entity: string,
	config: oneOfType( [ string, object ] ),
	query: object,
	choices: oneOfType( [ object, array ] ),
	actions: oneOfType( [ object, array ] ),
}

function normalizeEntityActions( props ) {
	let {
		actions,
		entity,
		entityType,
		entityCallbacks,
		actionCallbacks,
		configValue,
		configForm,
	} = props;

	if ( ! actions ) {
		return null;
	}

	return objectToMappable( actions, 'action', 'label' ).map( ( action ) => {
		if ( React.isValidElement( action ) ) {
			return action;
		}

		if ( 'string' === typeof action ) {
			action = {
				action: action,
			};
		}

		if ( ! action.action ) {
			return null;
		}

		if ( ! action.type ) {
			action.type = entityType;
		}

		const normalizedAction = { ...action };

		switch ( action.action ) {
			case 'edit':
				if ( ! entity ) {
					return null;
				}
				normalizedAction.callback = actionCallbacks.edit;
				normalizedAction.label = action.label ?? <Icon icon="edit" />;
				normalizedAction.savable = action.savable ?? true;
				normalizedAction.entity = entityCallbacks.get( entity );
				break;
			case 'create':
				normalizedAction.callback = actionCallbacks.create;
				normalizedAction.savable = action.savable ?? true;
				if ( action.savable ) {
					normalizedAction.editCallback = actionCallbacks.edit;
				}
				break;
			case 'config':
				normalizedAction.fields = isFunction( configForm ) ? configForm() : configForm;
				normalizedAction.label = action.label ?? <Icon icon={ isConfigured( { ...configValue, id: null } ) ? "configured" : "config" } />;
				normalizedAction.callback = actionCallbacks.config;
				break;
		}

		return normalizedAction;
	} ).filter( Boolean );
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

		let configParts = config.split( ':' );

		switch ( configParts[0] ) {
			case 'webservice': // send|retrieve
				component = <Webservice webservice={ entity && entity.config.webservice } { ...componentProps } mode={ configParts[1] } />;
				break;
			case 'entity':
				component = <Fields fields={ parseTag( configParts[1], entity ) || {} } { ...componentProps } />;
				break;
			default:
				return null;
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
