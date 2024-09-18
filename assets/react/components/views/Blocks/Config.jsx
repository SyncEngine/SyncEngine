import React from 'react';
import { ListGroup } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import { isEmpty, isObject } from '../../../utils/conditions';
import { parseTag } from '../../../utils/tags';
import BadgeControl from './Badge';
import Value from './Value';

export default function Config( props ) {
	const {
		item,
		prop,
		type,
		label,
		multi = true,
		inline = ! type,
		badge = false,
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

					const itemLabel = isObject( label ) ? <Value { ...label } item={ value } /> : value[ label ] ?? value.label ?? value._label ?? value.name ?? '--';

					return (
						<ListGroup.Item key={ index } className={ listItemClasses }>
							<span>{ itemLabel }</span>
							{ badge &&
								<BadgeControl type={ type } { ...( isObject( badge ) ? badge : { label: badge } ) } item={ value } />
							}
						</ListGroup.Item>
					);
				} )
			}
		</ListGroup>
	)
}
