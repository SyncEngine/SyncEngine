import React from 'react';
import { Button, Stack } from "react-bootstrap";

import EntityModal from '../../modals/EntityModal';
import ExportModal from '../../modals/ExportModal';
import DeleteModal from '../../modals/DeleteModal';
import { objectToMappable } from '../../../utils/data';

export default function Actions( props ) {

	const {
		actions = {},
		callbacks = {},
		item = props.entity,
		type,
	} = props;

	return (
		<Stack direction="horizontal" gap={2}>
			{
				objectToMappable( actions, 'action', 'label' ).map( action => {

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
								<EntityModal key={ action.action } entity={ item } { ...action }><Button variant={ type }><span className="bi bi-pencil" /></Button></EntityModal>
							)

						case 'export':
							return (
								<ExportModal key={ action.action } entity={ item } { ...action }><Button variant={ type }><span className="bi bi-upload" /></Button></ExportModal>
							)

						case 'delete':
							return (
								<DeleteModal key={ action.action } entity={ item } { ...action }><Button variant={ type }><span className="bi bi-trash" /></Button></DeleteModal>
							)
					}
				} )
			}
		</Stack>
	);
}
