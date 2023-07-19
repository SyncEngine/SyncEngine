import React from 'react';
import {  Button, Stack } from "react-bootstrap";

import { ucfirst } from '../utils/globals';
import EntityModal from '../components/modals/EntityModal';
import { BiExport, BiPencil, BiTrash } from 'react-icons/bi';
import ExportModal from '../components/modals/ExportModal';
import DeleteModal from '../components/modals/DeleteModal';

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
							return (
								<ExportModal key={ action.action } entity={ entity } { ...action }><Button variant={ type }><BiExport/></Button></ExportModal>
							);

						case 'delete':
							action.callback = deleteEntity;
							return (
								<DeleteModal key={ action.action } entity={ entity } { ...action }><Button variant={ type }><BiTrash/></Button></DeleteModal>
							)
					}

					return (
						<EntityModal key={ action.action } entity={ entity } { ...action }><Button variant={ type }>{ action.label ?? ucfirst( action.action ) ?? '' }</Button></EntityModal>
					);
				} )
			}
		</Stack>
	);
}
