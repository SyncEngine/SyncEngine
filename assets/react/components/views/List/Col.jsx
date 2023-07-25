import React from 'react';
import { Placeholder } from 'react-bootstrap';
import Block from '../Blocks';

export default function ListCol( props ) {
	const {
		column,
		item,
		content,
	} = props;

	if ( 'placeholder' === item ) {
		return (
			<Placeholder animation="glow">
				<Placeholder xs={12} size="lg" />
			</Placeholder>
		)
	}

	return (
		<div>
			<Block { ...props } block={ column.key ?? column.name ?? column } content={ content } args={ column } />
		</div>
	);
}
