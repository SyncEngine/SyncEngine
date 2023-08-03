import React from 'react';
import { Stack } from "react-bootstrap";
import Info from '../../views/Blocks/Info';

export default function Header( props ) {

	const {
		label,
		description,
	} = props;

	return (
		<Info
			className={ 'justify-content-center ' + ( ( props._disabled ?? false ) ? 'opacity-50' : '' ) }
			item={ props.item ?? { label: label, description: description } }
		/>
	);
}
