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

	let relations = [];
	if ( multi ) {
		relations = objectToMappable( value, 'key', 'label' );
	} else {
		relations = [ value ];
	}

	return (
		<ListGroup className="small">
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
							<ListGroup.Item key={ id + '' + index } className="d-flex justify-content-between align-items-center gap-2">
								{ label ?? '--' }
								<span className={ "badge rounded-pill text-bg-secondary" }>{ typeLabel ?? '' } #{ id }</span>
							</ListGroup.Item>
						)
					}

					return (
						<EntityModal key={ id + '' + index } type={ entityType.toLowerCase() } id={ id } name={ label } action="edit" callback={ callbacks.edit ?? null }>
							<ListGroup.Item action className="d-flex justify-content-between align-items-center gap-2">
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
