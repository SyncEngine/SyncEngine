import React, { useCallback, useContext, useEffect, useRef } from 'react';

import Fields from '../components/form/Fields';
import BlueprintControl from '../components/form/Blueprint';

import { TagsContext } from '../context/TagsContext';
import { EntityContext } from '../context/EntityContext';
import { ParentContext } from '../context/ParentContext';

import { publish, subscribe, unsubscribe } from '../utils/events';
import { deepClone, objectMerge } from '../utils/data';
import { parseTagsObject } from '../utils/tags';

export default function ConfigController( props ) {
	const {
		value,
		prop = 'config',
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
			parseTagsObject( deepClone( args.tags ), { _entity: entity } ) ?? {}
		)
	}, [ parentContext, args.tags, entity ] );

	const tags = useRef( fetchTags() );

	const update = ( newValue ) => {
		onChange( newValue );

		/**
		 * Override config of current entity while keeping the object ref.
		 * This will update the entity config for its scope but nog as the global entity.
		 * Only after updating the entity will it update the global entity registry.
		 * (Fixes preview mode for unsaved changed.)
		 */
		entity[ prop ] = newValue;

		// Update tags context.
		tags.current = objectMerge(
			tags.current,
			deepClone( args.tags ),
			parseTagsObject( deepClone( args.tags ), { _entity: entity } ) ?? {}
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

	let formComponent;
	if ( entity._supports && entity._supports.blueprints ) {
		formComponent = <BlueprintControl manualFields={ fields } value={ value } onChange={ update } entity={ entity } />;
	} else {
		formComponent = <Fields gap={3} name={ entity.ref } fields={ fields } value={ value } onChange={ update } />
	}

	return (
		<EntityContext.Provider value={ entity }>
			<TagsContext.Provider value={ tags.current }>
				{ formComponent }
			</TagsContext.Provider>
		</EntityContext.Provider>
	)
}
