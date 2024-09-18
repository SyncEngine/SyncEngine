import React from 'react';
import { useTranslation } from 'react-i18next';
import { ButtonGroup, DropdownButton, FormCheck, Stack } from 'react-bootstrap';

import Button from '../../partials/Button';
import EntityModal from '../../modals/EntityModal';
import ExportModal from '../../modals/ExportModal';
import DeleteModal from '../../modals/DeleteModal';
import RequestModal from '../../modals/RequestModal';

import { deepClone, objectToMappable } from '../../../utils/data';
import Collapsible from '../../services/Collapsible';
import Icon from '../../partials/Icon';

function getVariants( button, variant ) {
	const buttonVariant = ( 'string' === typeof button ) ? button : variant;
	return {
		variant: ( ! buttonVariant || 'link' === buttonVariant ) ? variant : buttonVariant,
		button: button ? buttonVariant : false,
		icon: ( ! buttonVariant || 'link' === buttonVariant ) ? variant : null,
	}
}

export default function Actions( props ) {
	const { t } = useTranslation();

	const {
		actions = {},
		callbacks = {},
		item = props.entity,
		type,
		variant = type,
		view = 'buttons',
	} = props;

	const buttons = props.buttons ?? ( 'grouped' === view || 'dropdown' === view || 'buttons' === view );


	let actionElements = objectToMappable( actions, 'action', 'label' ).map( ( action, index ) => {

		if ( 'function' === typeof action.label ) {
			return action.label( props );
		}

		if ( 'string' === typeof action ) {
			action = {
				action: action,
				button: buttons,
			};
		} else {
			// Remove ref.
			action = deepClone( action );
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

		const variants = getVariants( action.button ?? buttons, action.variant ?? variant );

		let iconClasses = "";

		switch ( action.action ) {
			case 'edit':
				iconClasses = ( variants.icon ? ' link-' + variants.icon : '' );
				return (
					<EntityModal key={ action.action } entity={ item } savable { ...action }>
						{ variants.button
							? <Button subtle variant={ variants.button }><Icon icon="edit" className={ iconClasses } /></Button>
							: <Icon icon="edit" className={ iconClasses + ' icon-btn' } />
						}
					</EntityModal>
				)

			case 'export':
				iconClasses = ( variants.icon ? ' link-' + variants.icon : '' );
				return (
					<ExportModal key={ action.action } entity={ item } { ...action }>
						{ variants.button
							? <Button subtle variant={ variants.button }><Icon icon="upload" className={ iconClasses } /></Button>
							: <Icon icon="export" className={ iconClasses + ' icon-btn' } />
						}
					</ExportModal>
				)

			case 'delete':
			case 'remove':
				const variant = action.variant ?? 'danger';
				return (
					<DeleteModal key={ action.action } entity={ item } { ...action }>
						{ ( 'link' === variants.button )
							? <Button variant="link"><Icon icon="delete" className="link-danger" /></Button>
							: ( variants.button ) && <Button subtle variant={ variant }><Icon icon="delete" /></Button>
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
					iconClasses = variants.icon ? ' link-' + variants.icon : '';
					if ( ! variants.button ) {
						iconClasses += ' icon-btn';
					}

					trigger = <Icon icon={ action.icon } className={ iconClasses } title={ trigger } aria-label={ trigger }/>;
				} else {
					if ( ! variants.button ) {
						trigger = <span className={ "link-" + variants.icon }>{ trigger }</span>
					}
				}

				return (
					<RequestModal key={ action.action + action.request } { ...action } entity={ item } action={ action.request }>
						{ variants.button
							? <Button subtle variant={ variants.button }>{ trigger }</Button>
							: trigger
						}
					</RequestModal>
				)
		}
	} );

	switch ( view ) {
		case 'grouped':
			actionElements = buttons ? <ButtonGroup>{ actionElements }</ButtonGroup> : <Stack direction="horizontal" gap={ 3 }>{ actionElements }</Stack>;
		break;
		case 'dropdown':
			const primary = actionElements.shift();

			// @todo Create separate components and call them here instead of in loop above.
			// @todo Override Bootstrap dropdown items to support custom button styles.
			actionElements = (
				<ButtonGroup>
					{ primary }
					<DropdownButton title="" as={ButtonGroup} variant={ 'outline-secondary' } placement="left">
						{ actionElements }
					</DropdownButton>
				</ButtonGroup>
			);
		break;
		case 'collapsed':
			actionElements = (
				<div className="position-relative d-inline-flex">
					<Collapsible
						autoClose={ true }
						trigger={
							buttons ?
								<Button subtle variant={ getVariants( buttons, variant ).button }>
									<Icon icon="toolbar-menu"/>
								</Button>
								:
								<Icon icon="toolbar-menu" className="p-2"/>
						}
						className={ "position-absolute top-0 end-100 z-2" + ( buttons ? ' mt-n2' : '' ) }
						dimension="width"
					>
						<Stack direction="horizontal" className={ "bg-body p-2" + ( buttons ? '' : ' px-3' ) } gap={ buttons ? 2 : 3 }>
							{ actionElements }
						</Stack>
					</Collapsible>
				</div>
			);
		break;
		default:
			actionElements = <Stack direction="horizontal" gap={ buttons ? 2 : 3 }>{ actionElements }</Stack>;
		break;
	}

	return actionElements;
}
