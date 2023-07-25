import React from 'react';
import { objectToMappable } from '../../../utils/data';
import { ListGroup } from 'react-bootstrap';
import { ucfirst } from '../../../utils/globals';

export default function Config( props ) {
	const {
		item,
		prop,
		type,
		typeLabel,
	} = props;

	if ( ! item.config || ! item.config.hasOwnProperty( prop ) ) {
		return;
	}

	const list = objectToMappable( item.config[ prop ], 'key', 'label' );

	return (
		<ListGroup horizontal={ ! type }>
			{
				list.map( ( value, index ) => {

					console.log( value );

					const {
						_class,
						_label,
						label = _label,
						name,
					} = value;

					return (
						<ListGroup.Item key={ index } className="d-flex justify-content-between align-items-center gap-2">
							{ label ?? name ?? '--' }
							{ type &&
								<span className={ "badge rounded-pill" + ( type && " text-bg-" + type.toLowerCase() ) }>{ typeLabel ?? type }: { _class }</span>
							}
						</ListGroup.Item>
					);
				} )
			}
		</ListGroup>
	)
}
