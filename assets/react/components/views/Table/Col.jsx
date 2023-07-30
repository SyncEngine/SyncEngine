import React from 'react';
import Block from '../Blocks';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

export default function TableCol( props ) {
	const {
		column,
		item,
		content,
		blockType = column.block ?? column.key ?? column.name ?? column,
	} = props;

	let classes = '';

	if ( 'placeholder' === item ) {
		return <td><LoadingPlaceholder/></td>
	}

	switch ( blockType ) {
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
			<Block { ...props } block={ blockType } content={ content } args={ column } />
		</td>
	);
}
