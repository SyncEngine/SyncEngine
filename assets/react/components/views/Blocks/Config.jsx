import React from 'react';
import { objectToMappable } from '../../../utils/data';
import { ListGroup } from 'react-bootstrap';

export default function Config( props ) {
	const {
		item,
		prop,
		type,
	} = props;

	if ( ! item.config || ! item.config.hasOwnProperty( prop ) ) {
		return;
	}

	const list = objectToMappable( item.config[ prop ], 'key', 'label' );

	return (
		<ListGroup horizontal={ ! type }>
			{
				list.map( ( value, index ) => {

					const {
						_class,
						label,
						name,
					} = value;

					return (
						<ListGroup.Item key={ index }>
							{ label ?? name ?? '--' }
							{ type &&
								<span className={ "badge rounded-pill" + ( type && " text-bg-" + type.toLowerCase() ) }>{ type } { _class }</span>
							}
						</ListGroup.Item>
					);
				} )
			}
		</ListGroup>
	)
}
