import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, InputGroup, Popover, Stack } from 'react-bootstrap';

import useSecrets from '../hooks/useSecrets';

import LoadingPlaceholder from '../components/partials/Loading/Placeholder';
import ListView from '../components/views/List';

import CopyToClipboard from '../components/partials/CopyToClipboard';
import { objectToMappable } from '../utils/data';
import useToggle from '../hooks/useToggle';
import OverlayToggle from '../components/services/OverlayToggle';
import Text from '../components/fields/Text';

const VaultController = ( props ) => {
	const { t } = useTranslation();
	const [ secrets, callbacks, loading ] = useSecrets();

	if ( ! secrets && loading ) {
		return <LoadingPlaceholder/>;
	}

	return (
		<Stack className={ loading ? 'opacity-50' : '' }>
			<CreateAction callback={ callbacks.add } />
			<ListView
				callbacks={ callbacks }
				columns={ {
					info: {
						block: ( props ) => {
							const {
								item
							} = props;

							return <Stack direction="horizontal" gap={2}>
								<CopyToClipboard value={ '{{ vault.' + item.name + ' }}' } title={ t('Copy as tag') } />
								<span>{ item.name }</span>
							</Stack>;
						}
					},
					actions: {
						block: 'actions',
						actions: {
							edit: ( props ) => {
								return <EditAction key="ëdit" { ...props } callback={ callbacks.add } />
							},
							reveal: ( props ) => {
								return <RevealAction key="reveal" { ...props } callback={ callbacks.reveal } />;
							},
							remove: {
								action: 'remove',
								callback: ( item ) => callbacks.remove( item.name ),
								variant: 'outline-danger'
							}
						}
					}
				} }
				items={ objectToMappable( secrets, 'name', 'value' ) }
			/>
		</Stack>
	);
}

const CreateAction = ( props ) => {
	const { t } = useTranslation();
	const [ enabled, toggleEnabled, enable, disable ] = useToggle( false );
	const [ name, setKey ] = useState( '' );
	const [ value, setValue ] = useState( '' );

	const {
		callback,
	} = props;

	const create = async () => {
		const response = await callback( name, value );

		if ( Array.isArray( response ) && response.includes( name ) ) {
			setKey( '' );
			setValue( '' );
			disable();
		}
	}

	return <InputGroup className={ "p-3 " + ( enabled ? 'border bg-body' : 'justify-content-end' ) }>
		{ enabled &&
			<>
				<Text label={ t('Name') } value={ name } onChange={ setKey } />
				<Text label={ t('Value') } value={ value } onChange={ setValue } />
				{ ( value && name ) &&
					<InputGroup.Text role="button" onClick={ create }><span className="bi bi-check"/></InputGroup.Text>
				}
			</>
		}
		<Button variant="outline-primary" onClick={ toggleEnabled }>
			<span className={ "bi bi-" + ( enabled ? 'x-lg' : 'plus-lg' ) } />
		</Button>
	</InputGroup>;
}

const EditAction = ( props ) => {
	const [ enabled, toggleEnabled, enable, disable ] = useToggle( false );
	const [ value, setValue ] = useState( '' );

	const {
		item,
		callback,
	} = props;

	const update = async () => {
		const response = await callback( item.name, value );

		if ( Array.isArray( response ) && response.includes( item.name ) ) {
			setValue( '' );
			disable();
		}
	}

	return <InputGroup>
		{ enabled &&
		    <>
			    <Text value={ value } onChange={ setValue } />
			    <InputGroup.Text role="button" onClick={ update }><span className="bi bi-check"/></InputGroup.Text>
		    </>
		}
		<Button variant="outline-primary" onClick={ toggleEnabled }>
			<span className={ "bi bi-" + ( enabled ? 'x' : 'pencil-fill' ) } />
		</Button>
	</InputGroup>;
}

const RevealAction = ( props ) => {
	const [ value, setValue ] = useState( null );

	const {
		item,
		callback,
	} = props;

	const reveal = useCallback( async () => {
		let secret = await callback( item.name );
		if ( 'object' === typeof secret ) {
			secret = JSON.stringify( secret );
		}
		setValue( secret );
	}, [ item, callback, setValue ] );

	const hide = useCallback( () => { setValue( null ) }, [ setValue ] );

	return (
		<OverlayToggle
			onShow={ reveal }
			onHide={ hide }
			trigger={ <div><Button variant="outline-info"><span className="bi bi-key"/></Button></div> }
		>
			{ value }
		</OverlayToggle>
	);
}

export default VaultController;
