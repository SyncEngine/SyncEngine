import React from 'react';
import { Form, ToggleButton } from 'react-bootstrap';
import { getCheckButtonVariant } from './';

export default function CheckSingle( props ) {

	if ( props.button ) {
		return (
			<ToggleButton { ...props } type={ 'checkbox' } label={ null } variant={ getCheckButtonVariant( props ) }>
				{ props.label }
			</ToggleButton>
		)
	}

	return (
		<Form.Check { ...props } />
	);
}
