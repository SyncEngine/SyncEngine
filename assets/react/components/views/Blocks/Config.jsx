import React from 'react';
import { objectToMappable } from '../../../utils/data';
import { ListGroup } from 'react-bootstrap';
import { isEmpty } from '../../../utils/conditionals';

export default function Config( props ) {
	const {
		item,
		prop,
		type,
		typeLabel,
		itemLabelProp,
		itemTypeProp,
		multi = true,
	} = props;

	if ( ! item.config || ! item.config.hasOwnProperty( prop ) ) {
		return;
	}

	if ( isEmpty( item.config[ prop ] ) ) {
		return props.notfound ?? '--';
	}

	let list = [];

	if ( multi ) {
		list = objectToMappable( item.config[ prop ], 'key', 'label' );
	} else {
		list = [ item.config[ prop ] ];
	}

	return (
		<ListGroup horizontal={ ! type }>
			{
				list.map( ( value, index ) => {

					const label = value[ itemLabelProp ] ?? value.label ?? value._label ?? value.name ?? '--';
					const typeName = value[ itemTypeProp ] ?? value._class ?? '';

					return (
						<ListGroup.Item key={ index } className="d-flex justify-content-between align-items-center gap-2">
							{ label }
							{ type &&
								<span className={ "badge rounded-pill" + ( type && " text-bg-" + type.toLowerCase() ) }>{ typeLabel ?? type }: { typeName }</span>
							}
						</ListGroup.Item>
					);
				} )
			}
		</ListGroup>
	)
}
