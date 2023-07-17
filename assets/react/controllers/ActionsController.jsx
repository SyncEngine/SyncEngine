import React from 'react';
import {  Button, Stack } from "react-bootstrap";

import { ucfirst } from '../utils/globals';
import EntityModal from '../components/modals/EntityModal';
import { BiExport, BiPencil, BiTrash } from 'react-icons/bi';

export default function ActionsController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		actions = {},
		entity,
		type,
	} = args;

	const editEntity = ( entity ) => {
		console.log( 'edit' );
	}

	const deleteEntity = ( entity ) => {
		console.log( 'add' );
	}

	const exportEntity = ( entity ) => {
		console.log( 'export' );
	}

	return (
		<Stack direction="horizontal" gap={2}>
			{
				actions.map( action => {

					if ( 'string' === typeof action ) {
						action = {
							action: action,
						};
					}

					if ( ! action.action ) {
						return;
					}

					if ( ! action.type ) {
						action.type = type;
					}

					action.id = entity.id;
					action.name = entity.name;

					switch ( action.action ) {
						case 'edit':
							action.callback = editEntity;
							action.label = <BiPencil/>
							break;
						case 'export':
							action.callback = exportEntity;
							action.label = <BiExport/>
							break;
						case 'delete':
							action.callback = deleteEntity;
							action.label = <BiTrash/>
							break;
					}

					return (
						<EntityModal key={ action.action } entity={ entity } { ...action }><Button variant={ type }>{ action.label ?? ucfirst( action.action ) ?? '' }</Button></EntityModal>
					);
				} )
			}
		</Stack>
	);
}
