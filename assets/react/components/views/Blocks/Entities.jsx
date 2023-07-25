import React from 'react';
import { objectToMappable } from '../../../utils/data';
import { ListGroup } from 'react-bootstrap';
import EntityModal from '../../modals/EntityModal';
import { isEmpty } from '../../../utils/conditionals';

export default function Entities( props ) {
	const {
		callbacks,
		item,
		prop,
		type,
		typeLabel,
		multi = true,
	} = props;

	if ( ! item.hasOwnProperty( prop ) ) {
		return;
	}

	if ( isEmpty( item[ prop ] ) ) {
		return props.notfound ?? '--';
	}

	let relations = [];
	if ( multi ) {
		relations = objectToMappable( item[ prop ], 'key', 'label' );
	} else {
		relations = [ item[ prop ] ];
	}

	return (
		<ListGroup>
			{
				relations.map( rel => {
					console.log( rel );

					const {
						_entity,
						id,
						name,
					} = rel;

					const entityType = _entity ?? type ?? '';

					if ( ! entityType ) {
						return (
							<ListGroup.Item className="d-flex justify-content-between align-items-center gap-2">
								{ name ?? '--' }
								<span className={ "badge rounded-pill text-bg-secondary" }>{ typeLabel ?? '' } #{ id }</span>
							</ListGroup.Item>
						)
					}

					return (
						<EntityModal key={ id } type={ entityType } id={ id } name={ name } action="edit" callback={ callbacks.edit ?? null }>
							<ListGroup.Item action className="d-flex justify-content-between align-items-center gap-2">
								{ name ?? '--' }
								<span className={ "badge rounded-pill" + ( entityType && " text-bg-" + entityType.toLowerCase() ) }>{ typeLabel ?? entityType } #{ id }</span>
							</ListGroup.Item>
						</EntityModal>
					);
				} )
			}
		</ListGroup>
	)
}
