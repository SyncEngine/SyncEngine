import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Popover, Stack } from 'react-bootstrap';

import useSecrets from '../hooks/useSecrets';

import LoadingPlaceholder from '../components/partials/Loading/Placeholder';
import ListView from '../components/views/List';

import CopyToClipboard from '../components/partials/CopyToClipboard';
import { objectToMappable } from '../utils/data';
import useToggle from '../hooks/useToggle';
import OverlayToggle from '../components/services/OverlayToggle';

const VaultController = ( props ) => {
	const { t } = useTranslation();
	const [ secrets, callbacks, loading ] = useSecrets();

	if ( loading ) {
		return <LoadingPlaceholder/>;
	}

	return (
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
						reveal: ( props ) => {
							return <RevealAction key="reveal" { ...props } callback={ callbacks.reveal } />;
						},
						remove: 'remove'
					}
				}
			} }
			items={ objectToMappable( secrets, 'name', 'value' ) }
		/>
	);
}

const RevealAction = ( props ) => {
	const [ value, setValue ] = useState( null );

	const {
		item,
		callback,
	} = props;

	const reveal = useCallback( async ( item ) => {
		let secret = await callback( item );
		if ( 'object' === typeof secret ) {
			secret = JSON.stringify( secret );
		}
		setValue( secret );
	}, [ callback ] );

	return (
		<OverlayToggle
			onShow={ () => reveal( item.name ) }
			onHide={ () => setValue( null ) }
			trigger={ <div><Button><span className="bi bi-key"/></Button></div> }
		>
			{ value }
		</OverlayToggle>
	);
}

export default VaultController;
