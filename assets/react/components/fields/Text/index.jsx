import React, { useCallback, useContext } from 'react';
import { Button, FloatingLabel, Form, InputGroup } from 'react-bootstrap';
import Help from '../../form/Help';
import Description from '../../form/Description';
import { TagsContext } from '../../../context/TagsContext';
import Tags from '../../partials/Tags';

export default function Text( props ) {
	const {
		label,
		attr,
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
					<FloatingLabel label={ label }>
						<Form.Control
							{ ...attr }
							as="textarea"
							placeholder={ props.placeholder ?? ' ' }
							value={ props.value ?? props.default ?? '' }
							onChange={ handleChange }
						/>
					</FloatingLabel>
					{ tags &&
						<Tags tags={ tags } onClick={ onInsert } trigger={ <Button variant="outline-secondary" size="sm" className="position-absolute top-0 end-0 z-3"><span className="bi bi-braces" /></Button> } />
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
				<FloatingLabel label={ label }>
					<Form.Control
						{ ...attr }
						placeholder={ props.placeholder ?? ' ' }
						value={ props.value ?? props.default ?? '' }
						onChange={ handleChange }
					/>
				</FloatingLabel>
				{ tags &&
					<Tags tags={ tags } onClick={ onChange } trigger={ <InputGroup.Text><span className="bi bi-braces" /></InputGroup.Text> } />
				}
			</InputGroup>
			{ props.description && <Description text={ props.description } id={ id } /> }
		</div>
	)
}
