import React, { useEffect, useState, useRef } from 'react';
import { Stack, Card, InputGroup, Button } from "react-bootstrap";
import Fields from "../../form/Fields";
import Field from "../../form/Field";
import EntityModal from "../../modals/EntityModal";
import { ucfirst } from "../../../utils/globals";

export default function Entity( props ) {
	const {
		value,
		config,
		entity,
		actions = [],
		onChange,
	} = props;

	const parseEntity = ( val ) => {
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

	const [ selectedEntity, setSelectedEntity ] = useState( parseEntity( value ) );
	const [ choices, setChoices ] = useState( props.choices );
	const [ cache, setCache ] = useState( {} );

	const initialRender = useRef( true );
	useEffect( () => {
		if ( initialRender.current ) {
			initialRender.current = false;
		} else {
			if ( ! getEntityConfigFields() ) {
				onChange( selectedEntity );
			} else if ( selectedEntity ) {
				onChange( { ...cache[ selectedEntity ] ?? {}, id: selectedEntity } );
			} else {
				onChange( {} );
			}
		}
	}, [ selectedEntity, cache ] );

	const updateEntity = ( newValue ) => {
		setSelectedEntity( parseEntity( newValue ) );
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
		let newChoices = { ...choices };
		newChoices[ entity.id ] = entity.name;
		setChoices( newChoices );
	}

	const addEntity = ( entity ) => {
		let newChoices = { ...choices };
		newChoices[ entity.id ] = entity.name + ' (new)';
		setChoices( newChoices );
		setSelectedEntity( entity.id );
	}

	const getEntityConfigFields = () => {
		if ( config ) {
			return config[ selectedEntity ] ?? null;
		}
		return null;
	}

	return (
		<Stack gap={0}>
			<InputGroup>
				<Field
					{...props}
					value={ selectedEntity }
					choices={ { ...choices } }
					className={ 'border-' + entity + '-subtle' }
					type="select"
					config=""
					onChange={ updateEntity }
				/>
				{ actions.map( ( action ) => {
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
				} ) }
			</InputGroup>
			{ getEntityConfigFields() &&
				<Card className="bg-body-tertiary border border-top-0 p-1">
					<Card.Body className="bg-body p-3">
						<Fields fields={ getEntityConfigFields() } value={ parseValue( value ) } onChange={ updateFields } />
					</Card.Body>
				</Card>
			}
		</Stack>
	);
}
