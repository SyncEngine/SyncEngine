import React, { cloneElement, useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from "react-bootstrap";
import Modal from '../Modal';

export default function ConfirmModal( props ) {
	const { t } = useTranslation();
	const [ open, setOpen ] = useState( false );

	const {
		header = '',
		text = '',
		confirm = t('Confirm'),
		cancel = t('Cancel'),
		trigger = 'onClick',
		callback,
		callbackProps,
		variant,
		icon,
	} = props;

	const handleClose = useCallback( ( e ) => {
		if ( e && 'function' === typeof e.preventDefault ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(false);
	}, [] );

	const handleOpen = useCallback( ( e ) => {
		if ( e && 'function' === typeof e.preventDefault ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(true);
	}, [] );

	const handleConfirm = useCallback( ( e ) => {
		callback( callbackProps );
		handleClose( e );
	}, [ callback ] );

	const getTriggerProps = () => {
		const props = Array.isArray( trigger ) ? trigger : [ trigger ].map( prop => {
			switch ( prop ) {
				case 'click':
					prop = 'onClick';
					break;
				case 'change':
					prop = 'onChange';
					break;
				case 'hover':
					prop = 'onHover';
					break;
				case 'focus':
					prop = 'onFocus';
					break;
			}
			return [ prop, handleOpen ];
		} );

		return Object.fromEntries( props )
	}

	return (
		<>
			{ typeof props.children === 'function' ? props.children( getTriggerProps() ) : cloneElement( props.children, getTriggerProps() ) }
			<Modal show={ open } onHide={ handleClose } centered scrollable>
				{ header &&
				  <Modal.Header closeButton>{ header }</Modal.Header>
				}
				{ text &&
					<Modal.Body>{ text }</Modal.Body>
				}
				<Modal.Footer>
					<Button variant="outline-secondary" onClick={ handleClose } autoFocus>
						{ cancel }
					</Button>
					<Button variant={ variant } onClick={ handleConfirm }>
						{ icon && <span className={ 'bi me-2 ' + icon } /> }
						{ confirm }
					</Button>
				</Modal.Footer>
			</Modal>
		</>
	);
}
