import React, { useCallback } from 'react';
import { FloatingLabel, Form, InputGroup } from 'react-bootstrap';
import Help from '../../form/Help';
import Description from '../../form/Description';

export default function Text( props ) {
	const {
		label,
		attr,
		id = attr.id ?? createRefId(),
		onChange,
	} = props;

	const handleChange = useCallback( ( e ) => {
		onChange( e.target.value );
	}, [ onChange, id, props.name ] );

	if ( props.textarea || props.multiline ) {
		return (
			<div className="flex-grow-1">
				<InputGroup>
					{ props.help && <Help id={ id } text={ props.help } inputGroup={ true } /> }
					<FloatingLabel label={ label }>
						<Form.Control
							{ ...attr }
							as="textarea"
							placeholder={ props.placeholder ?? ' ' }
							value={ props.value ?? props.default ?? '' }
							onChange={ handleChange }
						/>
					</FloatingLabel>
				</InputGroup>
				{ props.description && <Description text={ props.description } id={ id } /> }
			</div>
		)
	}

	return (
		<div className="flex-grow-1">
			<InputGroup>
				{ props.help && <Help id={ id } text={ props.help } inputGroup={ true } /> }
				<FloatingLabel label={ label }>
					<Form.Control
						{ ...attr }
						placeholder={ props.placeholder ?? ' ' }
						value={ props.value ?? props.default ?? '' }
						onChange={ handleChange }
					/>
				</FloatingLabel>
			</InputGroup>
			{ props.description && <Description text={ props.description } id={ id } /> }
		</div>
	)
}
