import React from 'react';
import {  Button, Stack } from "react-bootstrap";
import { BiExport, BiPencil, BiTrash } from 'react-icons/bi';

import EntityModal from '../../../modals/EntityModal';
import ExportModal from '../../../modals/ExportModal';
import DeleteModal from '../../../modals/DeleteModal';
import { ucfirst } from '../../../../utils/globals';

export default function Actions( props ) {

	const {
		actions = {},
		entity,
		type,
		handleEdit,
		handleDelete,
		handleExport,
	} = props;

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
							action.callback = handleEdit;
							action.label = <BiPencil/>
							break;

						case 'export':
							action.callback = handleExport;
							return (
								<ExportModal key={ action.action } entity={ entity } { ...action }><Button variant={ type }><BiExport/></Button></ExportModal>
							);

						case 'delete':
							action.callback = handleDelete;
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
