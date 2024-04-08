import React, { createContext, forwardRef, useCallback, useContext, useRef } from 'react';
import { Modal } from 'react-bootstrap';
import { ContainerContext } from '../../context/ContainerContext';
import useToggle from '../../hooks/useToggle';

const ExpandableContext = createContext( [] );

const ExpandableButton = forwardRef( ( props, ref ) => {
	const [ expanded, toggleExpanded, fullscreen ] = useContext( ExpandableContext );

	const {
		label,
		variant,
	} = props;

	let className = props.className ?? 'position-absolute p-2 end-0';

	let icon = props.icon || 'bi bi-' + (
		expanded
		? (
			fullscreen ? 'arrows-angle-contract' : 'arrows-collapse-vertical'
		)
		: (
			fullscreen ? 'arrows-angle-expand' : 'arrows-expand-vertical'
		)
	);

	className += ' icon-btn ' + icon;
	className += props.closeButton ? ' me-5' : '';
	className += 'white' === variant ? ' text-light' : '';

	return (
		<span
			ref={ ref }
			aria-label={ label }
			onClick={ toggleExpanded }
			className={ className }
		/>
	);
} );

const ModalControl = ( props ) => {
	const stopPropagation = useCallback( e => 'Escape' !== e.key && e.stopPropagation(), [] );

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

	if ( !props.hasOwnProperty( 'onEscapeKeyDown' ) && props.onHide ) {
		override.onEscapeKeyDown = props.onHide;
	}

	return (
		<div
			className="d-none"
			onKeyDown={ stopPropagation }
			onClick={ stopPropagation }
			onFocus={ stopPropagation }
			onMouseOver={ stopPropagation }
		>
			<ExpandableContext.Provider value={ [ expanded, toggleExpanded, 'fullscreen' === props.expandable ] }>
				<Modal
					{ ...props }
					{ ...override }
					expandable={ null }
				/>
			</ExpandableContext.Provider>
		</div>
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
