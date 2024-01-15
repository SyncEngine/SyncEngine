import React from 'react';
import { useTranslation } from 'react-i18next';
import { Stack } from 'react-bootstrap';

import useSecrets from '../hooks/useSecrets';

import LoadingPlaceholder from '../components/partials/Loading/Placeholder';
import ListView from '../components/views/List';

import CopyToClipboard from '../components/partials/CopyToClipboard';
import { objectToMappable } from '../utils/data';

export default function VaultController( props ) {
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
						remove: 'remove'
					}
				}
			} }
			items={ objectToMappable( secrets, 'name', 'value' ) }
		/>
	);
}
