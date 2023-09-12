import React, { useCallback } from 'react';
import { Form } from 'react-bootstrap';
import { isEmpty } from '../../../utils/conditionals';
import { createRefId } from '../../../utils/globals';

export default function ToggleSingle( props ) {

	const {
		attr,
		id = attr.id ?? createRefId(),
		label,
		help,
		type,
		onChange,
	} = props;

	const description = props.description && (
		<Form.Text id={ 'desc_' + id } muted>
			{ props.description }
		</Form.Text>
	)

	const handleCheck = useCallback( ( e ) => {
		onChange( e.target.checked );
	}, [ onChange, id, props.name ] );

	return (
		<div>
			<Form.Check
				{ ...attr }
				onChange={ handleCheck }
				label={ <><span>{ label }</span>{ help }</> }
				checked={ ! isEmpty( props.value ?? props.default ) }
				type={ ( 'switch' === type || 'toggle' === type ) ? 'switch' : 'checkbox' }
			/>
			{ description }
		</div>
	);
}
