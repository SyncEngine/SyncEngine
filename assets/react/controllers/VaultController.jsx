import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { InputGroup, Stack } from 'react-bootstrap';

import useToggle from '../hooks/useToggle';
import useSecrets from '../hooks/useSecrets';

import OverlayToggle from '../components/services/OverlayToggle';
import ListView from '../components/views/List';
import Input from '../components/fields/Input';

import LoadingPlaceholder from '../components/partials/Loading/Placeholder';
import CopyToClipboard from '../components/partials/CopyToClipboard';
import { objectToMappable } from '../utils/data';
import { isEmpty } from '../utils/conditions';
import Button from '../components/partials/Button';
import Icon from '../components/partials/Icon';

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
								variant: 'danger'
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
				<Input label={ t('Name') } value={ name } onChange={ setKey } />
				<Input label={ t('Value') } multiline="auto" value={ value } onChange={ setValue } />
				<Button onClick={ create } disabled={ ( ! value || ! name ) }>
					<Icon icon="check" /> { t('Create') }
				</Button>
			</>
		}
		<Button subtle variant="primary" onClick={ toggleEnabled }>
			<Icon icon={ enabled ? 'cross' : 'plus' } /> { enabled ? '' : t('Create') }
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
			    <Input value={ value } multiline="auto" onChange={ setValue } />
			    <Button onClick={ update } disabled={ isEmpty( value ) }><Icon icon="check" /></Button>
		    </>
		}
		<Button subtle variant="primary" onClick={ toggleEnabled }>
			<Icon icon={ enabled ? 'cross' : 'edit' } />
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
			overlay={ value }
			prewrap={ true }
		>
			<div><Button subtle variant="info"><Icon icon="key" /></Button></div>
		</OverlayToggle>
	);
}

export default VaultController;
