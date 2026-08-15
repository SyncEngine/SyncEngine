import React, { cloneElement, useCallback, useState } from 'react';
import PropTypes from 'prop-types';
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
	const [ error, setError ] = useState( null );

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
		setError( null );
		if ( isFunction( onClose ) ) {
			onClose();
		}
	}, [ setOpen, onClose ] );

	const handleOpen = useCallback( ( e ) => {
		suppress( e );
		setOpen(true);
		setError( null );
		if ( isFunction( onOpen ) ) {
			onOpen();
		}
	}, [ setOpen, onOpen ] );

	const getErrorFromResult = ( result ) => {
		if ( ! result ) {
			return null;
		}
		if ( 'string' === typeof result ) {
			return result;
		}
		if ( 'object' === typeof result ) {
			if ( result.error ) {
				return result.error;
			}
			if ( result.errors ) {
				return Array.isArray( result.errors ) ? result.errors.join( '\n' ) : String( result.errors );
			}
		}
		return null;
	};

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
				{ error &&
				  <Modal.Body><div className="alert alert-danger m-0">{ error }</div></Modal.Body>
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

						const onClick = async ( e ) => {
							setError( null );
							try {
								const result = await callback( option.callbackProps );
								const errorMsg = getErrorFromResult( result );
								if ( errorMsg ) {
									setError( errorMsg );
									return;
								}
							} catch ( thrown ) {
								setError( thrown?.message ?? String( thrown ) );
								return;
							}
							handleClose( e );
						};

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

ChooseModal.propTypes = {
	header: PropTypes.node,
	text: PropTypes.string,
	cancel: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
	trigger: PropTypes.oneOfType( [ PropTypes.string, PropTypes.array ] ),
	choices: PropTypes.array,
	onClose: PropTypes.func,
	onOpen: PropTypes.func,
	children: PropTypes.node,
};
