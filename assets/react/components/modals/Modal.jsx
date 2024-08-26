import React, { createContext, forwardRef, useCallback, useContext, useRef } from 'react';
import { createPortal } from 'react-dom';
import { Modal } from 'react-bootstrap';
import { ContainerContext } from '../../context/ContainerContext';
import useToggle from '../../hooks/useToggle';
import Icon from '../partials/Icon';


const ModalPortal = ( props ) => {
	const stopPropagation = useCallback( e => 'Escape' !== e.key && e.stopPropagation(), [] );
	return createPortal(
		<div
			className="d-none"
			onKeyDown={ stopPropagation }
			onClick={ stopPropagation }
			onFocus={ stopPropagation }
			onMouseOver={ stopPropagation }
			onDrag={ stopPropagation }
		>
			{ props.children }
		</div>,
		document.body
	)
}

const ExpandableContext = createContext( [] );

const ExpandableButton = forwardRef( ( props, ref ) => {
	const [ expanded, toggleExpanded, fullscreen ] = useContext( ExpandableContext );

	const {
		label,
		variant,
	} = props;

	let className = props.className ?? '';

	let icon = props.icon || (
		expanded
		? (
			fullscreen ? 'arrows-angle-contract' : 'arrows-collapse-vertical'
		)
		: (
			fullscreen ? 'arrows-angle-expand' : 'arrows-expand-vertical'
		)
	);

	className += ' icon-btn';
	className += 'white' === variant ? ' text-light' : '';

	return (
		<Icon
			ref={ ref }
			aria-label={ label }
			onClick={ toggleExpanded }
			icon={ icon }
			className={ className }
		/>
	);
} );

const ModalControl = ( props ) => {

	const [ expanded, toggleExpanded ] = useToggle( props.expanded );
	const override = {};
	if ( expanded && props.expandable ) {
		if ( 'fullscreen' === props.expandable ) {
			override.fullscreen = true;
		} else {
			override.dialogClassName = props.dialogClassName ? props.dialogClassName +
			                                                   ' modal-expanded' : 'modal-expanded';
		}
	}

	if ( ! props.hasOwnProperty( 'onEscapeKeyDown' ) && props.onHide ) {
		override.onEscapeKeyDown = props.onHide;
	}

	return (
		<ModalPortal>
			<ExpandableContext.Provider value={ [ expanded, toggleExpanded, 'fullscreen' === props.expandable ] }>
				<Modal
					{ ...props }
					{ ...override }
					expandable={ null }
				/>
			</ExpandableContext.Provider>
		</ModalPortal>
	);
};

const ModalBody = ( props ) => {
	const ref = useRef( null );
	const stopPropagation = useCallback( e => 'Escape' !== e.key && e.stopPropagation(), [] );

	const override = { ...props };
	delete override.expandButton;
	delete override.expandLabel;
	delete override.expandVariant;

	const expandToggle = props.expandButton && (
		<ExpandableButton
			className="position-absolute p-0 px-1 end-0 top-0 bg-body"
			label={ props.expandLabel }
			variant={ props.expandVariant }
		/>
	);

	if ( expandToggle ) {
		override.className += ' pt-4';
	}

	return (
		<ContainerContext.Provider value={ ref }>
			<Modal.Body
				ref={ ref }
				onKeyDown={ stopPropagation }
				onClick={ stopPropagation }
				onFocus={ stopPropagation }
				onMouseOver={ stopPropagation }
				{ ...override }
			/>
			{ expandToggle }
		</ContainerContext.Provider>
	);
};

const ModalHeader = ( props ) => {
	const override = { ...props };
	delete override.expandButton;
	delete override.expandLabel;
	delete override.expandVariant;

	const expandToggle = props.expandButton && (
		<ExpandableButton
			className={ props.closeButton ? 'position-absolute p-2 end-0 me-5' : 'position-absolute p-2 end-0' }
			label={ props.expandLabel }
			variant={ props.expandVariant }
		/>
	);

	return (
		<Modal.Header { ...override }>
			{ props.children }
			{ expandToggle }
		</Modal.Header>
	);
};

ModalControl.Dialog = Modal.Dialog;
ModalControl.Header = ModalHeader;
ModalControl.Title = Modal.Title;
ModalControl.Body = ModalBody;
ModalControl.Footer = Modal.Footer;
ModalControl.ExpandableButton = ExpandableButton;
ModalControl.propTypes = Modal.propTypes;
ModalControl.contextTypes = Modal.contextTypes;

export default ModalControl;
