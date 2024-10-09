import React, { useCallback, useContext } from 'react';
import { ListGroup } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';

import { HStack } from '../../partials/Stack';
import Icon from '../../partials/Icon';
import Badge from '../../partials/Badge';

import EntityModal from '../../modals/EntityModal';
import { deepClone, objectMerge, objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditions';
import { parseTag, parseTagsObject } from '../../../utils/tags';

export default function Entities( props ) {
	const {
		callbacks,
		item,
		prop,
		type,
		tags = {},
		typeLabel,
		multi = true,
		inline = false,
		variant = inline ? '' : 'flush',
	} = props;

	const tagsContext = useContext( TagsContext );

	const fetchTags = useCallback( () => {
		if ( ! item._entity ) {
			return tagsContext;
		}
		return objectMerge(
			deepClone( tagsContext ),
			parseTagsObject( tags, { _entity: item } )
		)
	}, [ item, tagsContext ] );

	const value = parseTag( prop, item );

	if ( ! value ) {
		return;
	}

	if ( isEmpty( value ) ) {
		return props.notfound ?? '--';
	}

	let relations = [];
	if ( multi ) {
		relations = objectToMappable( value, 'key', 'label' );
	} else {
		relations = [ value ];
	}

	let listClasses = 'small' + ( inline ? ' flex-wrap border-start' : '' ) + ( 'flush' === variant ? ' ms-n2' : '' );

	let listItemClasses = 'd-flex justify-content-between align-items-center gap-2 p-2';
	if ( 1 < relations.length ) {
		listItemClasses += ' py-1';
	}

	return (
		<TagsContext.Provider value={ fetchTags() }>
		<ListGroup variant={ variant } horizontal={ inline } className={ listClasses }>
			{
				relations.map( ( rel, index ) => {
					if ( 'object' !== typeof rel && item.hasOwnProperty( '_dependencies' ) ) {
						rel = item[ '_dependencies' ][ type + ':' + rel ] ?? rel;
					}
					const {
						_entity,
						id,
						name,
						label = name,
					} = rel;

					const entityType = _entity ?? type ?? '';

					if ( ! entityType ) {
						return (
							<ListGroup.Item key={ id + '' + index } className={ listItemClasses }>
								<span>{ label ?? '--' }</span>
								<Badge pill subtle bg="secondary">{ typeLabel ?? '' } #{ id }</Badge>
							</ListGroup.Item>
						)
					}

					return (
						<EntityModal key={ id + '' + index } type={ entityType.toLowerCase() } entity={ rel } action="edit" callback={ callbacks.edit ?? null } savable>
							<ListGroup.Item action className={ listItemClasses }>
								<span>{ label ?? '--' }</span>
								<Badge pill subtle bg={ entityType && entityType.toLowerCase() }>
									<HStack gap={1}>
										{ entityType && <Icon icon={ entityType.toLowerCase() } /> }
										{ typeLabel ?? entityType } #{ id }
									</HStack>
								</Badge>
							</ListGroup.Item>
						</EntityModal>
					);
				} )
			}
		</ListGroup>
		</TagsContext.Provider>
	)
}
