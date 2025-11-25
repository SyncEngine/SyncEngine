import React, { useContext, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

import Entities from '../Entities';
import Button from '../../partials/Button';
import Icon from '../../partials/Icon';
import { HStack, VStack } from '../../partials/Stack';

import { ParentContext } from '../../../context/ParentContext';
import { TagsContext } from '../../../context/TagsContext';

import { deepClone, mapGetIndex } from '../../../utils/data';
import { isArray, isScalar } from '../../../utils/conditions';
import { Tab } from 'react-bootstrap';

export default function Sequence( props ) {
	const { t } = useTranslation();

	const [ sidebar, setSidebar ] = useState( null );
	const [ activeRef, setActiveRef ] = useState( null );
	const context = useContext( ParentContext );
	const tagsContext = useContext( TagsContext );

	const {
		entity: stepEntity = 'routine',
		onChange
	} = props;

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

	const handleUpdate = ( entities ) => {
		if ( ! isArray( entities ) ) {
			return entities;
		}
		props.onChange(
			deepClone( entities ).map( entity => {
				entity[ stepEntity ] = entity.id;
				delete entity.id;
				return entity;
			} )
		);
	}

	const parseValue = ( entities ) => {
		if ( ! isArray( entities ) ) {
			return entities;
		}
		return deepClone( entities ).map( entity => {
			if ( isScalar( entity ) ) {
				return entity;
			}
			entity.id = entity[ stepEntity ];
			delete entity[ stepEntity ];
			return entity;
		} );
	}

	const buildTagsContext = ( tags, activeIndex, entities, entityCallbacks ) => {
		const stepsContext = {};

		for ( let i = 0; i < entities.length; i ++ ) {
			if ( i === activeIndex ) {
				break;
			}

			let row = entities[ i ];

			stepsContext[ row._ref ] = {
				label: String( i + 1 ),
				_tag: row._ref,
				_children: entityCallbacks.get( row.id )?._step?.tags || {},
			};
		}

		return {
			step: deepClone( stepsContext ),
			...deepClone( tagsContext ),
		}
	}

	const initSidebar = ( e, context ) => {

		const {
			_ref,
			entity,
			entityCallbacks,
			entities,
			toolbar,
		} = context;

		const activeIndex = mapGetIndex( entities, _ref, '_ref' );
		const tags = buildTagsContext( tagsContext, activeIndex, entities, entityCallbacks );

		setActiveRef( _ref );
		setSidebar(
			<VStack className="w-100 overflow-auto">
				<HStack className="justify-content-between sticky-top bg-body p-3 border border-1 border-input mb-2">
					<span className="h4 m-0">{ activeIndex + 1 }: { entity.name }</span>
					<Button variant="outline-secondary" onClick={ handleClose }>
						<Icon icon="close" className="me-2" />
						{ t('Close editor') }
					</Button>
				</HStack>
				<TagsContext.Provider value={ tags }>
					{ toolbar.actions.config.fields }
				</TagsContext.Provider>
			</VStack>
		);
	}

	const itemActions = ( actions, context ) => {
		if ( ! context.entityType && ! context.entity ) {
			return actions;
		}

		const edit = {
			action: 'edit',
			label: t('Edit'),
			entity: context.entity,
			type: context.entityType,
			callback: context.callbacks.edit,
			savable: true,
		};

		return { edit: edit, ...actions };
	}

	const entitiesComponent = (
		<Entities
			config={ 'entity:_step.fields' }
			className="sequence"
			{ ...props }
			entity={ stepEntity }
			value={ parseValue( props.value ) }
			onChange={ handleUpdate }
			onClick={ initSidebar }
			itemActions={ itemActions }
			itemHeader={ ( headerComponent, context ) => {
				if ( ! headerComponent?.props?.columns || context.loading ) {
					return headerComponent;
				}

				const activeIndex = mapGetIndex( context.entities, context.item._ref, '_ref' );
				const tags = buildTagsContext( tagsContext, activeIndex, context.entities, context.entityCallbacks );
				const position = activeIndex + 1;
				const columns = headerComponent.props.columns;

				headerComponent = React.cloneElement( headerComponent, {
					columns: { ...columns, info: { ...columns.info, prefix: position + ': ' } },
				} );

				return <TagsContext.Provider value={ tags }>{ headerComponent }</TagsContext.Provider>;
			} }
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

Sequence.propTypes = {
	...Entities.propTypes,
}
