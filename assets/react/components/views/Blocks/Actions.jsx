import React from 'react';
import PropTypes from 'prop-types';
import { useTranslation } from 'react-i18next';
import { ButtonGroup, DropdownButton, DropdownItem, FormCheck } from 'react-bootstrap';

import useGlobal from '../../../hooks/useGlobal';

import { HStack, VStack } from '../../partials/Stack';
import Button from '../../partials/Button';
import Icon from '../../partials/Icon';
import Label from '../../form/Label';

import Modal from '../../modals/Modal';
import ConfirmModal from '../../modals/ConfirmModal';
import EntityModal from '../../modals/EntityModal';
import ExportModal from '../../modals/ExportModal';
import DeleteModal from '../../modals/DeleteModal';
import RequestModal from '../../modals/RequestModal';
import PreviewModal from '../../modals/PreviewModal';

import ModalToggle from '../../services/ModalToggle';
import Collapsible from '../../services/Collapsible';
import ResponseTabContent from '../../services/ResponseTabs/Content';
import { deepClone, objectToMappable } from '../../../utils/data';
import { validate } from '../../../utils/conditions';

function getVariants( button, variant, outline, subtle ) {
	const buttonVariant = ( 'string' === typeof button ) ? button : variant;
	const isLink = ( ! buttonVariant || 'link' === buttonVariant );

	return {
		variant: isLink ? variant : buttonVariant,
		button: button ? buttonVariant : false,
		icon: isLink ? variant : null,
		outline, subtle
	}
}

function createTrigger( action, variants, elemProps = {}, dropdown = false ) {
	let iconClasses = '';

	let trigger = action.label ?? action.action;
	if ( dropdown ) {
		trigger = <Label as="span" icon={ action.icon }>{ trigger }</Label>;
	} else if ( action.icon ) {
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

	if ( ! variants.button ) {
		return trigger;
	}

	if ( dropdown ) {
		return (
			<DropdownItem variant={ variants.button } { ...elemProps } key={ elemProps.key ?? undefined }>
				{ trigger }
			</DropdownItem>
		)
	}

	return <Button variant={ variants.button } outline={ variants.outline } subtle={ variants.subtle } { ...elemProps } key={ elemProps.key ?? undefined }>{ trigger }</Button>;
}

export default function Actions( props ) {
	const routes = useGlobal( 'routes' );
	const { t } = useTranslation();

	const {
		actions = {},
		callbacks = {},
		item = props.entity,
		type,
		variant = type,
		outline,
		view,
		buttons = ( 'grouped' === view || 'dropdown' === view || 'buttons' === view ),
		subtle = true,
		dropdownVariant = variant ?? 'secondary',
		dropdownTrigger,
	} = props;

	let primaryElements = [];
	let dropdownElements = [];

	let actionElements = objectToMappable( actions, 'action', 'label' ).map( ( action, index ) => {

		if ( 'function' === typeof action.label ) {
			return action.label( props );
		}

		if ( 'string' === typeof action ) {
			action = {
				action: action,
				button: buttons,
				dropdown: false,
			};
		} else if ( 'boolean' === typeof action.label ) {
			if ( ! action.label ) {
				return;
			}
			action = {
				action: action.action,
				button: buttons,
				dropdown: false,
			}
		} else {
			// Remove ref.
			action = deepClone( action );
		}

		if ( ! action.action ) {
			return;
		}

		if ( action.conditions && ! validate( action.conditions, item ) ) {
			return;
		}

		if ( ! action.type ) {
			action.type = type;
		}

		if ( item ) {
			action.id = item.id;
			action.name = item.name;
		}

		if ( ! action.callback && callbacks.hasOwnProperty( action.action ) ) {
			action.callback = callbacks[ action.action ];
		}

		const variants = getVariants(
			action.button ?? buttons,
			action.variant ?? variant,
			action.outline ?? outline,
			action.subtle ?? subtle
		);

		switch ( action.action ) {
			case 'create':
				const createAction = ( ! variants.button && ! action.icon ) ? { ...action, icon: 'add' } : action;
				return (
					<EntityModal key={ action.action } action="create" savable { ...action }>
						{ createTrigger( createAction, variants, {}, action.dropdown ) }
					</EntityModal>
				)

			case 'edit':
				if ( ! item ) {
					return null;
				}
				return (
					<EntityModal key={ action.action } entity={ item } savable { ...action }>
						{ createTrigger( { ...action, icon: action.icon ?? 'edit' }, variants, {}, action.dropdown ) }
					</EntityModal>
				)

			case 'export':
				if ( ! item ) {
					return null;
				}
				return (
					<ExportModal key={ action.action } entity={ item } { ...action }>
						{ createTrigger( { ...action, icon: action.icon ?? 'export' }, variants, {}, action.dropdown ) }
					</ExportModal>
				)

			case 'delete':
			case 'remove':
			case 'trash':
				const deleteVariant = action.variant ?? 'danger';
				return (
					<DeleteModal key={ action.action } entity={ item } { ...action }>
						{ ( 'link' === variants.button )
							? <Button variant="link"><Icon icon={ action.icon ?? 'delete' } className={ "link-" + deleteVariant } /></Button>
							: variants.button && createTrigger( { ...action, icon: action.icon ?? 'delete' }, { ...variants, button: deleteVariant }, {}, action.dropdown )
						}
					</DeleteModal>
				)

			case 'restore':
			case 'hide':
			case 'show':
				const trigger = createTrigger( { ...action, icon: action.icon ?? action.action }, variants, {}, action.dropdown );
				return React.cloneElement( trigger, { key: action.action, onClick: () => { action.callback( props.entity ?? props.item ?? props.id ) } } );

			case 'enable':
			case 'disable':
				if ( ! item ) {
					return null;
				}
				if ( props.entity || item._entity ) {
					return (
						<ConfirmModal key={ action.action } entity={ item } { ...action } callbackProps={ item }>
							{ createTrigger( { ...action, icon: action.icon ?? action.action }, variants, {}, action.dropdown ) }
						</ConfirmModal>
					)
				}
				return (
					<FormCheck
						key={ action.action }
						aria-label={ 'disable' === action.action ? t('Disable') : t('Enable') }
						className="mt-n1 no-label"
						type="switch"
						defaultChecked={ ! ( item._disabled ?? false ) }
						onClick={ action.callback }
					/>
				)

			case 'config':
				if ( action.preview ) {
					const previewProps = true === action.preview ? {} : action.preview;
					return (
						<PreviewModal
							key={ action.action }
							title={ previewProps.title }
							icon={ previewProps.icon ?? action.icon ?? "config" }
							entity={ previewProps.entity ?? item }
							type={ previewProps.type ?? action.type ?? type }
							config={ previewProps.config ?? action.value ?? ( item?.config ?? {} ) }
							fields={ action.fields }
							onSave={ action.callback }
							{ ...previewProps }
						>
								{ createTrigger( action, variants, {}, action.dropdown ) }
						</PreviewModal>
					);
				}
				return (
					<ModalToggle key={ action.action } trigger={ createTrigger( action, variants, {}, action.dropdown ) } />
				);

			case 'request':
				if ( ! item ) {
					return null;
				}
				return (
					<RequestModal key={ action.action + action.request } { ...action } callbacks={ callbacks } entity={ item } action={ action.request }>
						{ createTrigger( action, variants, {}, action.dropdown ) }
					</RequestModal>
				)

			case 'modal':
				return (
					<ModalToggle key={ action.action + index } trigger={ createTrigger( action, variants, {}, action.dropdown ) } raw modalProps={ { size: "lg", expandable: true } }>
						{
							action.title && <Modal.Header closeButton expandButton>
								<Modal.Title>
									{ action.icon && <Icon icon={action.icon} variant={ variants.icon } className="me-3" /> }
									{ action.title }
								</Modal.Title>
							</Modal.Header>
						}
						<Modal.Body>
							{ action.content || <ResponseTabContent name="" content={ action.content ?? item } /> }
						</Modal.Body>
					</ModalToggle>
				)

			case 'link':
				if ( ! item ) {
					return null;
				}
				const link = action.href ?? routes.get( 'entities.'+type, item );
				const icon = action.icon ?? 'folder-symlink-fill';
				const linkIconClasses = ( variants.icon ? ' link-' + variants.icon : '' );
				const _key = action.action + index;
				return link && ( variants.button
					? createTrigger( { ...action, icon: icon }, variants, { href: link, key: _key }, action.dropdown )
					: <a key={ _key } href={ link }><Icon icon={ icon } className={ linkIconClasses + ' icon-btn' } /></a>
				)

			default:
				if ( ! action.action || ( ! action.callback && ! action.component ) ) {
					return null;
				}
				return <React.Fragment key={ action.action + index }>
					{ action.callback && createTrigger( action, variants, { onClick: action.callback, key: action.action + index }, action.dropdown ) }
					{ action.component }
				</React.Fragment>;
		}
	} );

	// Split into primary and dropdown groups.
	actionElements.forEach( ( el, i ) => {
		const action = objectToMappable( actions, 'action', 'label' )[ i ];
		if ( ! el ) {
			return;
		}
		if ( action && true === action.dropdown ) {
			dropdownElements.push( el );
		} else {
			primaryElements.push( el );
		}
	} );

	actionElements = primaryElements;

	// Append dropdown if any actions are marked for it.
	if ( dropdownElements.length ) {
		const trigger = dropdownTrigger
			? dropdownTrigger
			: <Icon icon="toolbar-menu" />;

		actionElements.push(
			<DropdownButton
				title={ trigger }
				variant={ dropdownVariant.startsWith( 'outline-' ) ? dropdownVariant : 'outline-' + dropdownVariant }
				className={ subtle ? 'btn-subtle' : '' }
				key="dropdown"
			>
				{ dropdownElements }
			</DropdownButton>
		);
	}

	switch ( view ) {
		case 'grouped':
			actionElements = buttons ? <ButtonGroup>{ actionElements }</ButtonGroup> : <HStack gap={ 3 } className="d-inline-flex">{ actionElements }</HStack>;
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
								<Button subtle variant={ getVariants( buttons, variant ).button } outline={ outline }>
									<Icon icon="toolbar-menu"/>
								</Button>
								:
								<Icon icon="toolbar-menu" className="p-2"/>
						}
						className={ "position-absolute top-0 end-100 z-2" + ( buttons ? ' mt-n2' : '' ) }
						dimension="width"
					>
						<HStack className={ "bg-body p-2" + ( buttons ? '' : ' px-3' ) } gap={ buttons ? 2 : 3 }>
							{ actionElements }
						</HStack>
					</Collapsible>
				</div>
			);
		break;
		case 'buttons':
		case 'hstack':
			actionElements = <HStack gap={ buttons ? 2 : 3 } className="d-inline-flex">{ actionElements }</HStack>;
		break;
		case 'vstack':
			actionElements = <VStack gap={ buttons ? 2 : 3 } className="d-inline-flex">{ actionElements }</VStack>;
			break;
		default:
			// No wrapper by default.
		break;
	}

	return actionElements;
}

Actions.propTypes = {
	actions: PropTypes.object,
	callbacks: PropTypes.object,
	item: PropTypes.object,
	entity: PropTypes.object,
	type: PropTypes.string,
	variant: PropTypes.string,
	outline: PropTypes.bool,
	view: PropTypes.string,
	buttons: PropTypes.oneOfType( [ PropTypes.bool, PropTypes.string ] ),
	subtle: PropTypes.bool,
	dropdownVariant: PropTypes.string,
	dropdownTrigger: PropTypes.oneOfType( [ PropTypes.node, PropTypes.string ] ),
};
