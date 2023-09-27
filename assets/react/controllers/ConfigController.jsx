import React, { useCallback, useContext, useEffect } from 'react';
import { Stack } from "react-bootstrap";
import Fields from "../components/form/Fields";
import { TagsContext } from '../context/TagsContext';
import { EntityContext } from '../context/EntityContext';
import { publish, subscribe, unsubscribe } from '../utils/events';
import { objectMerge } from '../utils/data';
import { ParentContext } from '../context/ParentContext';

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

	// @todo useContext( TagsContext )

	const form = element && element.closest( 'form' );
	const entity = ( form && form.dataset.entity ) ? JSON.parse( form.dataset.entity ) : null;
	const parentContext = useContext( ParentContext );

	const parseTags = ( tags ) => {
		for ( const tag in tags ) {
			if ( tags.hasOwnProperty( tag ) ) {
				if ( 'object' === typeof tags[ tag ] ) {
					tags[ tag ] = parseTags( tags[ tag ] );
					continue;
				}
				switch ( tags[ tag ] ) {
					case '_entity':
						tags[ tag ] = entity ?? '';
						break;
				}
			}
		}

		return tags;
	}

	const fetchTags = useCallback( () => {
		return objectMerge(
			parentContext.tags ?? {},
			parseTags( args.tags ) ?? {}
		)
	}, [ parentContext, args.tags ] );

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
		<EntityContext.Provider value={ entity }>
			<TagsContext.Provider value={ fetchTags() }>
				<Stack className="mt-2">
					<Fields fields={ fields } value={ { ...value } } onChange={ update } />
				</Stack>
			</TagsContext.Provider>
		</EntityContext.Provider>
	)
}
