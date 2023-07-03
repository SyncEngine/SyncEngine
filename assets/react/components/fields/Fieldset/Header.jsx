import React from 'react';
import { Stack } from "react-bootstrap";

export default function Header( props ) {

	const {
		label,
		description,
		row,
	} = props;

	return (
		<Stack className={ ( row._disabled ) ? 'opacity-50' : '' }>
			<span>
				{ label }
			</span>
			{ description &&
			  <small className="text-secondary">{ description }</small>
			}
		</Stack>
	);
}
