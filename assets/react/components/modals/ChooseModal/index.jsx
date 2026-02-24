import React, { cloneElement, useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import Modal from '../Modal';
import Icon from '../../partials/Icon';
import { objectToMappable } from '../../../utils/data';
import { isEmpty, isFunction } from '../../../utils/conditions';
import { suppress } from '../../../utils/events';
import Button from '../../partials/Button';

export function getTriggerProps( trigger, callback ) {
	const props = ( Array.isArray( trigger ) ? trigger : [ trigger ] ).map( prop => {
		switch ( prop ) {
			case 'click':
				prop = 'onClick';
				break;
			case 'change':
				prop = 'onChange';
				break;
			case 'hover':
			case 'onHover':
				prop = 'onMouseOver';
				break;
			case 'focus':
				prop = 'onFocus';
				break;
		}
		return [ prop, callback ];
	} );

	return Object.fromEntries( props )
}

export default function ChooseModal( props ) {
	const { t } = useTranslation();
	const [ open, setOpen ] = useState( isEmpty( props.children ) );

	const {
		header = '',
		text = '',
		cancel = t('Cancel'),
		trigger = 'onClick',
		choices = [],
		onClose,
		onOpen,
	} = props;

	const handleClose = useCallback( ( e ) => {
		suppress( e );
		setOpen(false);
		if ( isFunction( onClose ) ) {
			onClose();
		}
	}, [ setOpen ] );

	const handleOpen = useCallback( ( e ) => {
		suppress( e );
		setOpen(true);
		if ( isFunction( onOpen ) ) {
			onOpen();
		}
	}, [ setOpen ] );

	const getTrigger = () => {
		if ( isEmpty( props.children ) ) {
			return;
		}
		return typeof props.children === 'function' ? props.children( getTriggerProps( trigger, handleOpen ) ) : cloneElement( props.children, getTriggerProps( trigger, handleOpen ) );
	}

	return (
		<>
			{ getTrigger() }
			<Modal show={ open } onHide={ handleClose } centered scrollable>
				{ header &&
					<Modal.Header closeButton>{ header }</Modal.Header>
				}
				{ text &&
					<Modal.Body>{ text }</Modal.Body>
				}
				<Modal.Footer>
					{ cancel &&
						<Button variant="secondary" outline onClick={ handleClose } autoFocus>
							{ cancel }
						</Button>
					}
					{ objectToMappable( choices, 'key' ).map( ( option, index ) => {
						const {
							label,
							icon,
							variant,
							callback,
						} = option;

						const onClick = ( e ) => { callback( option.callbackProps ); handleClose( e ); };

						return (
							<Button key={ option.key ?? index } variant={ variant } onClick={ onClick }>
								{ icon && <Icon icon={ icon } className="me-2" /> }
								{ label }
							</Button>
						)
					} ) }
				</Modal.Footer>
			</Modal>
		</>
	);
}
