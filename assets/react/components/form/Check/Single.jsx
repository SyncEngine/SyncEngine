import React from 'react';
import { Form, ToggleButton } from 'react-bootstrap';
import { getCheckButtonVariant } from './';
import Label from '../Label';

export default function CheckSingle( props ) {

	if ( props.button ) {
		return (
			<ToggleButton { ...props } type={ 'checkbox' } label={ null } button={ null } variant={ getCheckButtonVariant( props ) }>
				<Label>{ props }</Label>
			</ToggleButton>
		)
	}

	return (
		<Form.Check { ...props } />
	);
}
