import React, { createContext, useCallback, useContext, useRef } from 'react';
import { Modal } from 'react-bootstrap';
import { ContainerContext } from '../../context/ContainerContext';
import useToggle from '../../hooks/useToggle';

const ExpandedToggleContext = createContext( [] );

const ModalControl = ( props ) => {
	const stopPropagation = useCallback( e => 'Escape' !== e.key && e.stopPropagation(), [] );

	const [ expanded, toggleExpanded ] = useToggle( props.expanded );
	const override = {};
	if ( expanded && props.expandable ) {
		if ( 'fullscreen' === props.expandable ) {
			override.fullscreen = true;
		} else {
			override.dialogClassName = props.dialogClassName ? props.dialogClassName + ' modal-expanded' : 'modal-expanded';
		}
	}

	if ( ! props.hasOwnProperty( 'onEscapeKeyDown' ) && props.onHide ) {
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
			<ExpandedToggleContext.Provider value={ [ expanded, toggleExpanded, 'fullscreen' === props.expandable ] }>
				<Modal
					{ ...props }
					{ ...override }
					expandable={ null }
				/>
			</ExpandedToggleContext.Provider>
		</div>
	);
};

const ModalBody = ( props ) => {
	const ref = useRef( null );
	const stopPropagation = useCallback( e => 'Escape' !== e.key && e.stopPropagation(), [] );

	return (
		<ContainerContext.Provider value={ ref }>
			<Modal.Body
				ref={ ref }
				onKeyDown={ stopPropagation }
				onClick={ stopPropagation }
				onFocus={ stopPropagation }
				onMouseOver={ stopPropagation }
				{ ...props }
			/>
		</ContainerContext.Provider>
	);
}

const ModalHeader = ( props ) => {
	const [ expanded, toggleExpanded, fullscreen ] = useContext( ExpandedToggleContext );

	const override = { ...props };
	delete override.expandButton;
	delete override.expandLabel;
	delete override.expandVariant;

	const {
		expandButton,
		expandLabel,
		expandVariant,
	} = props;

	const expandToggle = expandButton && (
		<span
			aria-label={ expandLabel }
			onClick={ toggleExpanded }
			className={
				'position-absolute p-2 end-0 icon-btn bi bi-'
				+ ( expanded
					? ( fullscreen ? 'arrows-angle-contract' : 'arrows-collapse-vertical' )
					: ( fullscreen ? 'arrows-angle-expand' : 'arrows-expand-vertical' ) )
				+ ( props.closeButton ? ' me-5' : '' )
				+ ( 'white' === expandVariant ? ' text-light' : '' )
			}
		/>
	);

	return (
		<Modal.Header { ...override }>
			{ props.children }
			{ expandToggle }
		</Modal.Header>
	)
}

ModalControl.Dialog = Modal.Dialog
ModalControl.Header = ModalHeader
ModalControl.Title = Modal.Title
ModalControl.Body = ModalBody
ModalControl.Footer = Modal.Footer
ModalControl.propTypes = Modal.propTypes
ModalControl.contextTypes = Modal.contextTypes

export default ModalControl;
