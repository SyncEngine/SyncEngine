import React, { createContext, forwardRef, useCallback, useContext, useRef } from 'react';
import { createPortal } from 'react-dom';
import { Modal } from 'react-bootstrap';
import { ContainerContext } from '../../context/ContainerContext';
import useToggle from '../../hooks/useToggle';
import Icon from '../partials/Icon';

function preventBubbling( e ) {
	'Escape' !== e.key && e.stopPropagation();
}

let portalSandbox = document.getElementById( '_portalSandbox' );
if ( ! portalSandbox ) {
	portalSandbox = document.createElement( 'div' );
	portalSandbox.id = '_portalSandbox';
	portalSandbox.classList.add( 'd-none' );
	document.body.append( portalSandbox );
}

const ModalSandboxPortal = ( props ) => {
	return createPortal(
		<div
			className="d-none"
			onKeyDown={ preventBubbling }
			onClick={ preventBubbling }
			onFocus={ preventBubbling }
			onMouseOver={ preventBubbling }
			onDrag={ preventBubbling }
		>
			{ props.children }
		</div>,
		portalSandbox
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
			fullscreen ? 'size-contract' : 'size-contract-x'
		)
		: (
			fullscreen ? 'size-expand' : 'size-expand-x'
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
		<ModalSandboxPortal>
			<ExpandableContext.Provider value={ [ expanded, toggleExpanded, 'fullscreen' === props.expandable ] }>
				<Modal
					{ ...props }
					{ ...override }
					expandable={ null }
				/>
			</ExpandableContext.Provider>
		</ModalSandboxPortal>
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

	if ( ! override.style ) {
		override.style = {};
	}
	// Make modal body behave as a new canvas/viewport.
	override.style.transform = 'translate(0,0)';

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
