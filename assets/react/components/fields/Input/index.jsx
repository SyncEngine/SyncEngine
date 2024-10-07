import React, { useCallback, useContext, useState } from 'react';
import { any, array, bool, func, object, oneOfType, string } from 'prop-types';
import { Button, FloatingLabel, Form, InputGroup } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';
import Help from '../../form/Help';
import Description from '../../form/Description';
import Tags from '../../services/Tags';
import { createRefId } from '../../../utils/globals';
import { hasTag } from '../../../utils/tags';
import { isFieldEditable, isMultiline } from '../../../utils/conditions';
import Icon from '../../partials/Icon';

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
	const editable = isFieldEditable( props );

	const {
		label,
		attr = {},
		id = attr.id ?? createRefId(),
		onChange,
		type,
		prefix,
		postfix,
		taggable = false,
	} = props;

	const tagsContext = useContext( TagsContext );
	const tags = ( editable && taggable ) && tagsContext;
	const [ isTag, setIsTag ] = useState( ( taggable && hasTag( props.value ) ) );
	const [ multiline, setMultiline ] = useState( 'auto' === props.multiline ? isMultiline( props.value ?? props.default ) : props.multiline ?? false );

	const handleUpdate = useCallback( ( value ) => {
		if ( ! editable ) { return; }
		onChange( value );
	}, [ onChange, editable ] );

	const handleChange = useCallback( ( e ) => {
		if ( ! editable ) { return; }
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

	}, [ handleUpdate, props.multiline, editable ] );

	const handlePaste = useCallback( ( e ) => {
		if ( ! editable ) { return; }
		if ( ! multiline ) {
			let newValue = e.clipboardData.getData('Text');
			if ( isMultiline( newValue ) ) {
				setMultiline( true );
				handleUpdate( newValue );
			}
		}
	}, [ handleUpdate, editable ] );

	const onInsert = useCallback( value => {
		if ( ! editable ) { return; }
		// @todo insert at cursor.
		setIsTag( true );
		handleUpdate( props.value + value );
	}, [ handleUpdate, editable ] );

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
						disabled={ props.disabled ?? attr.disabled }
						readOnly={ ! editable || ( props.readOnly ?? props.readonly ?? attr.readOnly ?? attr.readonly ) }
						style={ 'password' === type ? { color: 'transparent', textShadow: '0 0 8px rgba(0,0,0)' } : {} }
					/>
					{ tags &&
						<Tags tags={ tags } callback={ onInsert } trigger={ <Button variant="outline-secondary" size="sm" className="position-absolute top-0 end-0"><Icon icon="tag" /></Button> } />
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
					disabled={ props.disabled ?? attr.disabled }
					readOnly={ ! editable || ( props.readOnly ?? props.readonly ?? attr.readOnly ?? attr.readonly ) }
					onChange={ handleChange }
					onKeyDown={ handleChange }
					onPaste={ handlePaste }
				/>
				{ postfix &&
					<InputGroup.Text>{ postfix }</InputGroup.Text>
				}
				{ tags &&
					<Tags tags={ tags } callback={ onChange } trigger={ <InputGroup.Text role="button"><Icon icon="tag" /></InputGroup.Text> } />
				}
			</InputGroup>
			{ props.description && <Description text={ props.description } id={ id } /> }
		</div>
	)
}

Input.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	label: oneOfType( [ string, object ] ),
	icon: string,
	description: string,
	placeholder: string,
	help: oneOfType( [ string, object, array ] ),
	textarea: bool,
	prefix: any,
	postfix: any,
}
