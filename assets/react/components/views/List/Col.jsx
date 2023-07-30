import React from 'react';
import Block from '../Blocks';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

export default function ListCol( props ) {
	const {
		column,
		item,
		content,
		blockType = column.block ?? column.key ?? column.name ?? column,
	} = props;

	if ( 'placeholder' === item ) {
		return <LoadingPlaceholder/>
	}

	return (
		<div>
			<Block { ...props } block={ blockType } content={ content } args={ column } />
		</div>
	);
}
