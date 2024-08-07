import React from 'react';
import { Form, ToggleButton } from 'react-bootstrap';


export default function CheckSingle( props ) {

	if ( props.button ) {
		return (
			<ToggleButton { ...props } type={ 'checkbox' } label={ null }>
				{ props.label }
			</ToggleButton>
		)
	}

	return (
		<Form.Check { ...props } />
	);
}
