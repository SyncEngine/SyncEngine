import React from 'react';
import { Stack } from "react-bootstrap";

export default function Header( props ) {

	const {
		label,
		description,
	} = props;

	return (
		<Stack className={ ( props._disabled ?? false ) ? 'opacity-50' : '' }>
			<span>
				{ label }
			</span>
			{ description &&
			  <small className="text-secondary">{ description }</small>
			}
		</Stack>
	);
}
