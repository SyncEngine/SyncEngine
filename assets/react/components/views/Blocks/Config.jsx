import React from 'react';
import { ListGroup } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditions';
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
		inline = ! type,
		variant = inline ? '' : 'flush',
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

	let listClasses = 'small' + ( inline ? ' flex-wrap border-start' : '' ) + ( 'flush' === variant ? ' ms-n2' : '' );

	let listItemClasses = 'd-flex justify-content-between align-items-center gap-2 p-2';
	if ( 1 < list.length ) {
		listItemClasses += ' py-1';
	}

	return (
		<ListGroup variant={ variant } horizontal={ inline } className={ listClasses }>
			{
				list.map( ( value, index ) => {

					const label = value[ itemLabelProp ] ?? value.label ?? value._label ?? value.name ?? '--';
					const typeName = value[ itemTypeProp ] ?? value._class ?? '';

					return (
						<ListGroup.Item key={ index } className={ listItemClasses }>
							<span>{ label }</span>
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
