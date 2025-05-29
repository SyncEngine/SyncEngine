import React, { useCallback, useContext, useRef, useState } from 'react';
import { any, array, bool, func, object, oneOfType, string } from 'prop-types';
import { Button, FloatingLabel, Form, InputGroup } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';
import Help from '../../form/Help';
import Description from '../../form/Description';
import Tags from '../../services/Tags';
import { createRefId } from '../../../utils/globals';
import { hasTag } from '../../../utils/tags';
import { isEmpty, isFieldEditable, isFunction, isMultiline, isObject, isString } from '../../../utils/conditions';
import Icon from '../../partials/Icon';
import { useTranslation } from 'react-i18next';
import OverlayToggle from '../../services/OverlayToggle';
import { mergeClassNames } from '../../../utils/props';
import TaggableInput from '../../form/Input/TaggableInput';
import useGlobal from '../../../hooks/useGlobal';

const Control = ( props ) => {
	const { t } = useTranslation();
	const app = useGlobal();
	const inputProps = { ...props };

	delete inputProps.taggable;
	delete inputProps.controlRef;

	/**
	 * @todo Create input/field type name that shows this error because it's redundant on labels and actual text input.
	 * Suggestions: "tag" or "key"
	 */

	let warning = false;
	if ( props.value && isString( props.value ) && ( props.value.startsWith( ' ' ) || props.value.endsWith( ' ' ) ) ) {
		inputProps.className = mergeClassNames( props.className, 'border-warning' );
		// @todo Fix annoying focus issue.
		//warning = t('Value contains leading/trailing spaces which can potentially cause unexpected behavior.');
	}

	let control;
	if ( props.taggable && app.__experimental ) {
		control = <>
			<Form.Control { ...inputProps } className={ "d-none" } />
			<TaggableInput { ...inputProps } controlRef={ props.controlRef } />
		</>;
	} else {
		control = <Form.Control { ...inputProps } />;
	}

	if ( warning ) {
		control = (
			<OverlayToggle
				trigger="hover blur"
				overlay={ <><Icon icon="warning" /> { warning }</> }
				id={ props.id + '-notice' }
			>
				{ control }
			</OverlayToggle>
		)
	}

	if ( inputProps.label ) {
		return (
			<FloatingLabel label={ inputProps.label }>
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
		wrap = ! isEmpty( props.description ),
	} = props;

	const tagsContext = useContext( TagsContext );
	const controlRef = useRef( {} );
	const tags = ( editable && taggable ) && tagsContext;
	const [ isTag, setIsTag ] = useState( ( taggable && hasTag( props.value ) ) );
	const [ multiline, setMultiline ] = useState( 'auto' === props.multiline ? isMultiline( props.value ?? props.default ) : props.multiline ?? false );

	const handleUpdate = useCallback( ( value ) => {
		if ( ! editable ) { return; }
		onChange( value );
	}, [ onChange, editable ] );

	const handleChange = useCallback( ( e ) => {
		if ( ! editable ) { return; }
		let newValue = ( isObject( e ) ) ? e.target.value : e;
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

	const handleKeyDown = useCallback( ( e ) => {
		if ( 'auto' === props.multiline && 'Enter' === e.key ) {
			handleChange( e );
		}
	}, [ handleChange, props.multiline ] );

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
		if ( isFunction( controlRef.current?.callbacks?.insert ) ) {
			controlRef.current.callbacks.insert( ( props.value || '' ) + value );
		}
	}, [ handleUpdate, editable ] );

	let component;
	if ( props.textarea || multiline ) {
		component = (
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
					taggable={ editable && taggable }
					controlRef={ controlRef.current }
				/>
				{ tags &&
					<Tags tags={ tags } callback={ onInsert } trigger={ <Button variant="outline-secondary" size="sm" className="position-absolute top-0 end-0"><Icon icon="tag" /></Button> } />
				}
			</InputGroup>
		)
	} else  {
		component = (
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
					onKeyDown={ handleKeyDown }
					onPaste={ handlePaste }
					taggable={ editable && taggable }
					controlRef={ controlRef.current }
				/>
				{ postfix &&
					<InputGroup.Text>{ postfix }</InputGroup.Text>
				}
				{ tags &&
					<Tags tags={ tags } callback={ onInsert } trigger={ <InputGroup.Text role="button"><Icon icon="tag" /></InputGroup.Text> } />
				}
			</InputGroup>
		)
	}

	if ( wrap || props.description ) {
		return (
			<div className="flex-grow-1">
				{ component }
				{ props.description && <Description text={ props.description } id={ id } /> }
			</div>
		)
	}

	return component;
}

Input.propTypes = {
	onChange: func,
	id: string,
	value: any,
	default: any,
	required: bool,
	disabled: bool,
	readonly: bool,
	editable: bool,
	taggable: bool,
	label: oneOfType( [ string, object ] ),
	icon: string,
	description: string,
	placeholder: string,
	help: oneOfType( [ string, object, array ] ),
	textarea: bool,
	prefix: any,
	postfix: any,
}
