import React from 'react';
import { Stack } from "react-bootstrap";
import Info from '../../views/Blocks/Info';
import ListRow from '../../views/List/Row';

export default function Header( props ) {

	const classes = 'justify-content-center ' + ( ( props._disabled ?? false ) ? 'opacity-50' : '' );

	if ( props.columns ) {
		return (
			<Stack direction="horizontal" className={ classes }>
				<ListRow { ...props } />
			</Stack>
		)
	}

	return (
		<Info className={ classes } item={ props.item ?? { ...props } } />
	);
}
