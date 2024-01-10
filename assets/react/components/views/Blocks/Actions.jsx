import React, { useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, FormCheck, Stack } from 'react-bootstrap';

import EntityModal from '../../modals/EntityModal';
import ExportModal from '../../modals/ExportModal';
import DeleteModal from '../../modals/DeleteModal';
import { objectToMappable } from '../../../utils/data';
import RequestModal from '../../modals/RequestModal';

export default function Actions( props ) {
	const { t } = useTranslation();

	const {
		actions = {},
		callbacks = {},
		item = props.entity,
		type,
		variant = type,
		button = true,
	} = props;

	const getVariants = useCallback( ( button, variant ) => {
		button = ( 'string' === typeof button ) ? button : variant;
		return {
			button: button,
			icon: ( ! button || 'link' === button ) ? variant : null,
		}
	}, [] );

	return (
		<Stack direction="horizontal" gap={2}>
			{
				objectToMappable( actions, 'action', 'label' ).map( action => {

					if ( 'string' === typeof action ) {
						action = {
							action: action,
							button: button,
						};
					} else {
						// Remove ref.
						action = structuredClone( action );
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

					const variants = getVariants( action.button ?? button, action.variant ?? variant );

					let iconClasses = "";

					switch ( action.action ) {
						case 'edit':
							iconClasses = "bi bi-pencil-fill" + ( variants.icon ? ' link-' + variants.icon : '' );
							return (
								<EntityModal key={ action.action } entity={ item } savable { ...action }>
									{ button
										? <Button variant={ variants.button }><span className={ iconClasses } /></Button>
										: <span className={ iconClasses + 'icon-btn' } />
									}
								</EntityModal>
							)

						case 'export':
							iconClasses = "bi bi-upload" + ( variants.icon ? ' link-' + variants.icon : '' );
							return (
								<ExportModal key={ action.action } entity={ item } { ...action }>
									{ button
										? <Button variant={ variants.button }><span className={ iconClasses } /></Button>
										: <span className={ iconClasses + 'icon-btn' } />
									}
								</ExportModal>
							)

						case 'delete':
							return (
								<DeleteModal key={ action.action } entity={ item } { ...action }>
									{ ( 'link' === variants.button )
										? <Button variant="link"><span className="bi bi-trash-fill link-danger" /></Button>
										: ( button ) && <Button variant="danger"><span className="bi bi-trash-fill" /></Button>
									}
								</DeleteModal>
							)

						case 'disable':
							return (
								<FormCheck
									key={ key }
									aria-label={ t('Disable') }
									className="mt-n1 no-label"
									type="switch"
									defaultChecked={ ! ( item._disabled ?? false ) }
									onClick={ action.callback }
								/>
							)

						case 'request':
							let trigger = action.label ?? action.action;
							if ( action.icon ) {
								iconClasses = action.icon + ( variants.icon ? ' link-' + variants.icon : '' );
								if ( ! button ) {
									iconClasses += 'icon-btn';
								}

								trigger = <span className={ iconClasses } title={ trigger } aria-label={ trigger }/>;
							} else {
								if ( ! button ) {
									trigger = <span className={ "link-" + variants.icon }>{ trigger }</span>
								}
							}

							return (
								<RequestModal key={ action.action + action.request } { ...action } entity={ item } action={ action.request }>
									{ button
										? <Button variant={ variants.button }>{ trigger }</Button>
										: trigger
									}
								</RequestModal>
							)
					}
				} )
			}
		</Stack>
	);
}
