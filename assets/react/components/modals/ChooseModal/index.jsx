import React, { cloneElement, useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from 'react-bootstrap';
import Modal from '../Modal';
import Icon from '../../partials/Icon';
import { objectToMappable } from '../../../utils/data';
import { getTriggerProps } from '../ConfirmModal';
import { isEmpty } from '../../../utils/conditions';

export default function ChooseModal( props ) {
	const { t } = useTranslation();
	const [ open, setOpen ] = useState( isEmpty( props.children ) );

	const {
		header = '',
		text = '',
		cancel = t('Cancel'),
		trigger = 'onClick',
		choices = [],
	} = props;

	const handleClose = useCallback( ( e ) => {
		if ( e && 'function' === typeof e.preventDefault ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(false);
	}, [ setOpen ] );

	const handleOpen = useCallback( ( e ) => {
		if ( e && 'function' === typeof e.preventDefault ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setOpen(true);
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
					<Button variant="outline-secondary" onClick={ handleClose } autoFocus>
						{ cancel }
					</Button>
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
