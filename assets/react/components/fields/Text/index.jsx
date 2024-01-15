import React, { useCallback, useContext } from 'react';
import { Button, FloatingLabel, Form, InputGroup } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';
import Help from '../../form/Help';
import Description from '../../form/Description';
import Tags from '../../services/Tags';
import { createRefId } from '../../../utils/globals';

const Control = ( props ) => {
	const control = <Form.Control { ...props }/>

	if ( props.label ) {
		return (
			<FloatingLabel label={ props.label }>
				{ control }
			</FloatingLabel>
		)
	}

	return control;
}

export default function Text( props ) {
	const {
		label,
		attr = {},
		id = attr.id ?? createRefId(),
		onChange,
		taggable,
	} = props;

	const tags = taggable && useContext( TagsContext );

	const handleChange = useCallback( ( e ) => {
		onChange( e.target.value );
	}, [ onChange, id, props.name ] );

	const onInsert = useCallback( value => {
		// @todo insert at cursor.
		handleChange( props.value + value );
	}, [ handleChange ] );



	if ( props.textarea || props.multiline ) {
		return (
			<div className="flex-grow-1">
				<InputGroup>
					{ props.help && <Help id={ id } text={ props.help } inputGroup={ true } /> }
					<Control
						{ ...attr }
						id={ id }
						label={ label }
						as="textarea"
						placeholder={ props.placeholder ?? attr.placeholder ?? ' ' }
						required={ props.required ?? attr.required }
						value={ props.value ?? props.default ?? '' }
						onChange={ handleChange }
					/>
					{ tags &&
						<Tags tags={ tags } callback={ onInsert } trigger={ <Button variant="outline-secondary" size="sm" className="position-absolute top-0 end-0"><span className="bi bi-braces" /></Button> } />
					}
				</InputGroup>
				{ props.description && <Description text={ props.description } id={ id } /> }
			</div>
		)
	}

	return (
		<div className="flex-grow-1">
			<InputGroup>
				{ props.help && <Help id={ id } text={ props.help } inputGroup={ true } /> }
				<Control
					{ ...attr }
					id={ id }
					label={ label }
					placeholder={ props.placeholder ?? attr.placeholder ?? ' ' }
					required={ props.required ?? attr.required }
					value={ props.value ?? props.default ?? '' }
					onChange={ handleChange }
				/>
				{ tags &&
					<Tags tags={ tags } callback={ onChange } trigger={ <InputGroup.Text role="button"><span className="bi bi-braces" /></InputGroup.Text> } />
				}
			</InputGroup>
			{ props.description && <Description text={ props.description } id={ id } /> }
		</div>
	)
}
