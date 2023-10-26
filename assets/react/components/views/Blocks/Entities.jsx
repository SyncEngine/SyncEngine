import React, { useCallback, useContext } from 'react';
import { ListGroup } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';

import EntityModal from '../../modals/EntityModal';

import { objectMerge, objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';
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
	} = props;

	const tagsContext = useContext( TagsContext );

	const fetchTags = useCallback( () => {
		if ( ! item._entity ) {
			return tagsContext;
		}
		return objectMerge(
			structuredClone( tagsContext ),
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

	const inline = props.inline ?? false;

	let relations = [];
	if ( multi ) {
		relations = objectToMappable( value, 'key', 'label' );
	} else {
		relations = [ value ];
	}

	let listItemClasses = 'd-flex justify-content-between align-items-center gap-2 p-2';
	if ( 1 < relations.length ) {
		listItemClasses += ' py-1';
	}

	return (
		<TagsContext.Provider value={ fetchTags() }>
		<ListGroup horizontal={ inline } className={ "small" + ( inline && ' flex-wrap border-start' ) }>
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
								<small>{ label ?? '--' }</small>
								<span className={ "badge rounded-pill text-bg-secondary" }>{ typeLabel ?? '' } #{ id }</span>
							</ListGroup.Item>
						)
					}

					return (
						<EntityModal key={ id + '' + index } type={ entityType.toLowerCase() } entity={ rel } action="edit" callback={ callbacks.edit ?? null }>
							<ListGroup.Item action className={ listItemClasses }>
								<small>{ label ?? '--' }</small>
								<span className={ "badge rounded-pill" + ( entityType && " text-bg-" + entityType.toLowerCase() ) }>{ typeLabel ?? entityType } #{ id }</span>
							</ListGroup.Item>
						</EntityModal>
					);
				} )
			}
		</ListGroup>
		</TagsContext.Provider>
	)
}
