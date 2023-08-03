import React from 'react';
import { objectToMappable } from '../../../utils/data';
import { ListGroup } from 'react-bootstrap';
import { isEmpty } from '../../../utils/conditionals';
import { parseTag } from '../../../utils/globals';

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

	if ( ! item.config ) {
		return;
	}

	const value = parseTag( prop, item.config );

	if ( ! value ) {
		return;
	}

	if ( isEmpty( value ) ) {
		return props.notfound ?? '--';
	}

	let list = [];

	if ( multi ) {
		list = objectToMappable( value, 'key', 'label' );
	} else {
		list = [ value ];
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
