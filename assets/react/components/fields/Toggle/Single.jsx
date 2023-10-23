import React, { useCallback } from 'react';
import { Form } from 'react-bootstrap';
import { isEmpty } from '../../../utils/conditionals';
import { createRefId } from '../../../utils/globals';
import Description from '../../form/Description';
import Help from '../../form/Help';

export default function ToggleSingle( props ) {

	const {
		attr = {},
		id = attr.id ?? createRefId(),
		label,
		type,
		onChange,
	} = props;

	const description = props.description && <Description text={ props.description } id={ id } />;

	const handleCheck = useCallback( ( e ) => {
		onChange( e.target.checked );
	}, [ onChange, id, props.name ] );

	return (
		<div>
			<Form.Check
				{ ...attr }
				id={ id }
				onChange={ handleCheck }
				label={ <><span>{ label }</span>{ props.help && <Help text={ props.help } id={ id } /> }</> }
				checked={ ! isEmpty( props.value ?? props.default ) }
				type={ ( 'switch' === type || 'toggle' === type ) ? 'switch' : 'checkbox' }
			/>
			{ description }
		</div>
	);
}
