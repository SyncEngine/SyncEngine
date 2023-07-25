import React from 'react';
import { Placeholder } from 'react-bootstrap';
import Block from '../Blocks';

export default function TableCol( props ) {
	const {
		column,
		item,
		content,
	} = props;

	let classes = '';

	if ( 'placeholder' === item ) {
		return (
			<td>
				<Placeholder animation="glow">
					<Placeholder xs={12} size="lg" />
				</Placeholder>
			</td>
		)
	}

	switch ( column.key ?? column.name ?? column ) {
		case 'ref':
		case 'id':
			classes += 'text-start table-cell-shrink';
			break;
		case 'actions':
			classes += 'text-end table-cell-shrink';
			break;
	}

	return (
		<td className={ classes }>
			<Block { ...props } block={ column.key ?? column.name ?? column } content={ content } actions={ column.actions } />
		</td>
	);
}
