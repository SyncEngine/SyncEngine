import React from 'react';
import { Button, FormCheck, Stack } from 'react-bootstrap';

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
		button = true,
	} = props;

	const buttonVariant = ( 'string' === typeof button ) ? button : type;
	const iconVariant = ( ! button || 'link' === buttonVariant ) ? type : null;

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

					let iconClasses = "";

					switch ( action.action ) {
						case 'edit':
							iconClasses = "bi bi-pencil-fill" + ( iconVariant ? ' link-' + iconVariant : '' );
							return (
								<EntityModal key={ action.action } entity={ item } { ...action }>
									{ button
										? <Button variant={ buttonVariant }><span className={ iconClasses } /></Button>
										: <span className={ iconClasses + "icon-link" } />
									}
								</EntityModal>
							)

						case 'export':
							iconClasses = "bi bi-upload" + ( iconVariant ? ' link-' + iconVariant : '' );
							return (
								<ExportModal key={ action.action } entity={ item } { ...action }>
									{ button
										? <Button variant={ buttonVariant }><span className={ iconClasses } /></Button>
										: <span className={ iconClasses + "icon-link" } />
									}
								</ExportModal>
							)

						case 'delete':
							return (
								<DeleteModal key={ action.action } entity={ item } { ...action }>
									{ ( 'link' === buttonVariant )
										? <Button variant="link"><span className="bi bi-trash-fill link-danger" /></Button>
										: ( button ) && <Button variant="danger"><span className="bi bi-trash-fill" /></Button>
									}
								</DeleteModal>
							)

						case 'disable':
							return (
								<FormCheck
									key={ key }
									aria-label="Disable"
									className="mt-n1 no-label"
									type="switch"
									defaultChecked={ ! ( item._disabled ?? false ) }
									onClick={ action.callback }
								/>
							)
					}
				} )
			}
		</Stack>
	);
}
