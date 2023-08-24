import React from 'react';
import { ListGroup } from 'react-bootstrap';
import EntityModal from '../../modals/EntityModal';
import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';
import { parseTag } from '../../../utils/globals';

export default function Entities( props ) {
	const {
		callbacks,
		item,
		prop,
		type,
		typeLabel,
		multi = true,
	} = props;

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
								{ label ?? '--' }
								<span className={ "badge rounded-pill text-bg-secondary" }>{ typeLabel ?? '' } #{ id }</span>
							</ListGroup.Item>
						)
					}

					return (
						<EntityModal key={ id + '' + index } type={ entityType.toLowerCase() } entity={ rel } action="edit" callback={ callbacks.edit ?? null }>
							<ListGroup.Item action className={ listItemClasses }>
								{ label ?? '--' }
								<span className={ "badge rounded-pill" + ( entityType && " text-bg-" + entityType.toLowerCase() ) }>{ typeLabel ?? entityType } #{ id }</span>
							</ListGroup.Item>
						</EntityModal>
					);
				} )
			}
		</ListGroup>
	)
}
