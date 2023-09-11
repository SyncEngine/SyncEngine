import React, { useEffect } from 'react';
import { Stack } from "react-bootstrap";
import Fields from "../components/form/Fields";
import { publish, subscribe, unsubscribe } from '../utils/events';
import { TagsContext } from '../context/TagsContext';

export default function ConfigController( props ) {

	const {
		value,
		args = {},
		element,
		onChange,
	} = props;

	const {
		fields,
	} = args;

	const form = element && element.closest( 'form' );

	const parseTags = ( tags ) => {
		for ( const tag in tags ) {
			if ( tags.hasOwnProperty( tag ) ) {
				if ( 'object' === typeof tags[ tag ] ) {
					tags[ tag ] = parseTags( tags[ tag ] );
					continue;
				}
				switch ( tags[ tag ] ) {
					case '_entity':
						tags[ tag ] = ( form && form.dataset.entity ) ? JSON.parse( form.dataset.entity ) : '';
						break;
				}
			}
		}
		return tags;
	}

	const update = ( newValue ) => {
		onChange( newValue );

		publish( 'updateConfig', {
			id: element.closest( 'form' ).id,
			value: newValue
		} );
	}

	useEffect( () => {
		function requestConfig( e ) {
			if ( element.closest( 'form' ).id === e.detail.id ) {
				publish( 'updateConfig', {
					id: element.closest( 'form' ).id,
					value: value
				} );
			}
		}

		subscribe( 'requestConfig', requestConfig );
		return function cleanup() {
			unsubscribe( 'requestConfig', requestConfig );
		}
	}, [ element, value ] );

	return (
		<TagsContext.Provider value={ parseTags( args.tags ?? {} ) }>
			<Stack className="mt-2">
				<Fields fields={ fields } value={ { ...value } } onChange={ update } />
			</Stack>
		</TagsContext.Provider>
	)
}
