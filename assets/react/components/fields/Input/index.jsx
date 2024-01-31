import React, { useCallback, useContext, useState } from 'react';
import { Button, FloatingLabel, Form, InputGroup } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';
import Help from '../../form/Help';
import Description from '../../form/Description';
import Tags from '../../services/Tags';
import { createRefId } from '../../../utils/globals';
import { hasTag } from '../../../utils/tags';

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

export default function Input( props ) {
	const {
		label,
		attr = {},
		id = attr.id ?? createRefId(),
		onChange,
		taggable,
		type,
		prefix,
		postfix,
	} = props;

	const isMultiline = useCallback( ( value ) => {
		return 'string' === typeof value && -1 !== value.indexOf( "\n" );
	}, [] );

	const tags = taggable && useContext( TagsContext );
	const [ isTag, setIsTag ] = useState( ( taggable && hasTag( props.value ) ) );
	const [ multiline, setMultiline ] = useState( 'auto' === props.multiline ? isMultiline( props.value ?? props.default ) : props.multiline ?? false );

	const handleUpdate = useCallback( ( value ) => {
		onChange( value );
	}, [ onChange, id, props.name ] );

	const handleChange = useCallback( ( e ) => {
		let newValue = e.target.value;
		if ( ! hasTag( newValue ) ) {
			setIsTag( false );
		}

		if ( 'auto' === props.multiline ) {
			if ( e.key && e.key === 'Enter' ) {
				if ( ! multiline ) {
					setMultiline( true );
					newValue += "\n";
				}
			} else {
				setMultiline( isMultiline( newValue ) );
			}
		}

		handleUpdate( newValue );

	}, [ onChange, id, props.name, props.multiline ] );

	const handlePaste = useCallback( ( e ) => {
		if ( ! multiline ) {
			let newValue = e.clipboardData.getData('Text');
			if ( isMultiline( newValue ) ) {
				setMultiline( true );
				handleUpdate( newValue );
			}
		}
	}, [ handleUpdate ] );

	const onInsert = useCallback( value => {
		// @todo insert at cursor.
		setIsTag( true );
		handleUpdate( props.value + value );
	}, [ handleUpdate ] );

	if ( props.textarea || multiline ) {
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
						style={ 'password' === type ? { color: 'transparent', textShadow: '0 0 8px rgba(0,0,0,0.5)' } : {} }
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
				{ prefix &&
				    <InputGroup.Text>{ prefix }</InputGroup.Text>
				}
				<Control
					{ ...attr }
					id={ id }
					label={ label }
					type={ isTag ? 'text' : type }
					placeholder={ props.placeholder ?? attr.placeholder ?? ' ' }
					required={ props.required ?? attr.required }
					value={ props.value ?? props.default ?? '' }
					onChange={ handleChange }
					onKeyDown={ handleChange }
					onPaste={ handlePaste }
				/>
				{ postfix &&
					<InputGroup.Text>{ postfix }</InputGroup.Text>
				}
				{ tags &&
					<Tags tags={ tags } callback={ onChange } trigger={ <InputGroup.Text role="button"><span className="bi bi-braces" /></InputGroup.Text> } />
				}
			</InputGroup>
			{ props.description && <Description text={ props.description } id={ id } /> }
		</div>
	)
}
