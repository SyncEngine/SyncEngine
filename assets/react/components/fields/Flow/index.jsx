import React, { useContext, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

import Entities from '../Entities';
import Icon from '../../partials/Icon';
import { HStack, VStack } from '../../partials/Stack';
import { ParentContext } from '../../../context/ParentContext';
import { deepClone, mapGetIndex } from '../../../utils/data';
import { TagsContext } from '../../../context/TagsContext';

export default function Flow( props ) {
	const { t } = useTranslation();

	const [ sidebar, setSidebar ] = useState( null );
	const [ activeRef, setActiveRef ] = useState( null );
	const context = useContext( ParentContext );
	const tagsContext = useContext( TagsContext );

	const container = context?.container?.closest('.modal-content');

	useEffect( () => {
		if ( ! container ) {
			return;
		}

		if ( sidebar ) {
			container.classList.add('h-100');
		} else {
			container.classList.remove('h-100');
		}
		return () => {
			container.classList.remove('h-100');
		}
	}, [ sidebar, container ] );

	const handleClose = () => {
		setActiveRef( null );
		setSidebar( null );
	}

	const initSidebar = ( e, context ) => {

		const {
			_ref,
			type,
			entity,
			callbacks,
			entityCallbacks,
			entities,
			toolbar,
		} = context;

		const activeIndex = mapGetIndex( entities, _ref, '_ref' );
		const stepsContext = {};

		for ( let i = 0; i < entities.length; i ++ ) {
			if ( i === activeIndex ) {
				break;
			}

			let row = entities[ i ];

			stepsContext[ row._ref ] = entityCallbacks.get( row.id )._flowTags || {};
		}

		const tags = {
			step: deepClone( stepsContext ),
			...deepClone( tagsContext ),
		}

		setActiveRef( _ref );
		setSidebar(
			<VStack className="w-100 overflow-auto">
				<HStack className="justify-content-between sticky-top bg-body p-3 border border-1 border-input mb-2">
					<span className="h4 m-0">{ entity.name }</span>
					<Icon className="icon-btn" icon="close" onClick={ handleClose } />
				</HStack>
				<TagsContext.Provider value={ tags }>
					{ toolbar.actions.config.fields }
				</TagsContext.Provider>
			</VStack>
		);
	}

	const itemActions = ( actions, item, type, entity, callbacks ) => {
		if ( ! type && ! entity ) {
			return actions;
		}

		const edit = {
			action: 'edit',
			label: t('Edit'),
			entity: entity,
			type: type,
			callback: callbacks.edit,
			savable: true,
		};
		return { edit: edit, ...actions };
	}

	const entitiesComponent = (
		<Entities
			entity={ "step" }
			config={ "entity:_flowConfig" }
			{ ...props }
			onClick={ initSidebar }
			itemActions={ itemActions }
			activeKey={ activeRef }
		/>
	);

	if ( sidebar ) {
		return (
			<div className="position-fixed top-0 start-0 bottom-0 end-0 overflow-hidden bg-body-tertiary z-3">
				<HStack className="align-items-center h-100 p-3" gap={3}>
					<VStack className="align-self-center w-100 mh-100 overflow-auto">
						{ entitiesComponent }
					</VStack>
					{ sidebar }
				</HStack>
			</div>
		);
	}

	return entitiesComponent;
}

Flow.propTypes = {
	...Entities.propTypes,
}
