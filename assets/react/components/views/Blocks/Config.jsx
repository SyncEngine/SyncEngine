import React from 'react';
import { ListGroup } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';
import { parseTag } from '../../../utils/tags';

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

	const inline = props.inline ?? ! type;

	let list = [];

	if ( multi ) {
		list = objectToMappable( value, 'key', 'label' );
	} else {
		list = [ value ];
	}

	let listItemClasses = 'd-flex justify-content-between align-items-center gap-2 p-2';
	if ( 1 < list.length ) {
		listItemClasses += ' py-1';
	}

	return (
		<ListGroup horizontal={ inline } className={ "small" + ( inline && ' flex-wrap border-start' ) }>
			{
				list.map( ( value, index ) => {

					const label = value[ itemLabelProp ] ?? value.label ?? value._label ?? value.name ?? '--';
					const typeName = value[ itemTypeProp ] ?? value._class ?? '';

					return (
						<ListGroup.Item key={ index } className={ listItemClasses }>
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
