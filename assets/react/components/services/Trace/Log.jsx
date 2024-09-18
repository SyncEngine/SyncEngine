import React, { useCallback, useContext } from 'react';
import Code from '../../fields/Code';
import RequestModal from '../../modals/RequestModal';
import Button from '../../partials/Button';
import { EntityContext } from '../../../context/EntityContext';
import { TraceIteratorContext } from './Container';
import Icon from '../../partials/Icon';
import { useTranslation } from 'react-i18next';

export default function TraceLog( props ) {
	const { t } = useTranslation();

	const entity = useContext( EntityContext );
	const iterator = useContext( TraceIteratorContext ) ?? {};

	const removeRefs = useCallback( ( data ) => {
		if ( Array.isArray( data ) ) {
			return data.map( item => removeRefs( item ) );
		}
		if ( 'object' !== typeof data ) {
			return data;
		}
		data = { ...data };

		if ( data.hasOwnProperty( 'trace' ) ) {
			data.trace = removeRefs( data.trace );
		}

		// Remove generated keys.
		for ( const key in data ) {
			if ( data.hasOwnProperty( key ) && key.startsWith('_') ) {
				delete data[ key ];
			}
		}

		return data;
	} )

	const safeData = removeRefs( props.data );

	return (
		<div>
			{ props.data.file &&
			    <RequestModal
					title={ props.data.title + ' ' + props.data.file }
					confirm={ false }
					type="trace"
					entity={ entity }
					action="view"
					params={ { id: 'entityId', file: props.data.file, iteration: iterator.current, info: safeData } }
			    >
				    <Button size="sm" outline variant="info" className="position-absolute end-0 top-0 z-1" title={ t( 'View full log' ) }>
					    <Icon icon="view" />
					</Button>
			    </RequestModal>
			}
			<Code value={ JSON.stringify( safeData, null, 2 ) } language="json" readOnly />
		</div>
	);
}
