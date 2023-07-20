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
		callbacks = {},
		entity,
		item = entity,
		type,
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

					action.id = item.id;
					action.name = item.name;

					if ( ! action.callback && callbacks.hasOwnProperty( action.action ) ) {
						action.callback = callbacks[ action.action ];
					}

					switch ( action.action ) {
						case 'edit':
							return (
								<EntityModal key={ action.action } entity={ item } { ...action }><Button variant={ type }><BiPencil/></Button></EntityModal>
							)

						case 'export':
							return (
								<ExportModal key={ action.action } entity={ item } { ...action }><Button variant={ type }><BiExport/></Button></ExportModal>
							);

						case 'delete':
							return (
								<DeleteModal key={ action.action } entity={ item } { ...action }><Button variant={ type }><BiTrash/></Button></DeleteModal>
							)
					}
				} )
			}
		</Stack>
	);
}
