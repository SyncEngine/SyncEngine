import React, { useCallback, useContext, useEffect, useRef } from 'react';
import { Stack } from "react-bootstrap";

import Fields from "../components/form/Fields";

import { TagsContext } from '../context/TagsContext';
import { EntityContext } from '../context/EntityContext';
import { ParentContext } from '../context/ParentContext';

import { publish, subscribe, unsubscribe } from '../utils/events';
import { objectMerge, objectMergeDepth } from '../utils/data';
import { parseTagsObject } from '../utils/tags';

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

	if ( parentContext ) {
		if ( ! parentContext.scope ) {
			parentContext.scope = [];
		}
		parentContext.scope.push( entity );
	}

	const fetchTags = useCallback( () => {
		return objectMerge(
			parentContext.tags ?? {},
			parseTagsObject( structuredClone( args.tags ), { _entity: entity } ) ?? {}
		)
	}, [ parentContext, args.tags, entity ] );

	const tags = useRef( fetchTags() );

	const update = ( newValue ) => {
		onChange( newValue );

		// Update tags context.
		tags.current = objectMergeDepth(
			tags.current,
			2,
			structuredClone( args.tags ),
			parseTagsObject( structuredClone( args.tags ), { _entity: { ...entity, config: newValue } } ) ?? {}
		);

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
			<TagsContext.Provider value={ tags.current }>
				<Stack className="mt-2">
					<Fields fields={ fields } value={ { ...value } } onChange={ update } />
				</Stack>
			</TagsContext.Provider>
		</EntityContext.Provider>
	)
}
