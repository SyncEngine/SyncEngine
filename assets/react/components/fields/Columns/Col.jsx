import React, { useCallback, useContext, useEffect, useState } from 'react';
import { Form, Col, InputGroup } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';
import Tags from '../../services/Tags';
import { objectToMappable } from "../../../utils/data";

export default function ColumnsCol( props ) {
	const {
		value,
		choices = {},
		customizable = false,
		taggable = false,
		nest = false,
		onChange,
	} = props;

	const tags = taggable && useContext( TagsContext );

	const [ custom, setCustom ] = useState( ( choices ) ? ( customizable && value && 'object' !== typeof value && ! choices.hasOwnProperty( value ) ) : true );
	const [ multiline, setMultiline ] = useState( false );

	useEffect( () => {
		// Set custom on change of choices.
		setCustom( ( choices ) ? ( customizable && value && 'object' !== typeof value && ! choices.hasOwnProperty( value ) ) : true );
	}, [ choices ] );

	const toggleCustom = () => customizable && setCustom( ! custom );

	const update = useCallback(
		( event ) => { onChange( event.target.value.replace( "\n", '' ) ) },
		[ onChange ]
	);

	// @todo Implement param nesting.

	const field = ( custom || ( ! choices && 'object' !== typeof value ) ) ?
		<>
			<Form.Control
				column="text"
				as={ multiline ? "textarea" : undefined }
				placeholder={ props.placeholder ?? null }
				value={ ( 'object' === typeof value ) ? JSON.stringify( value ) : value }
				onChange={ update }
			/>
			{ value.length > 50 &&
				<InputGroup.Text onClick={ () => { setMultiline( ! multiline ) } } >
					{ multiline ?
						<span className="bi bi-input-cursor" />
						:
						<span className="bi bi-textarea-resize" />
					}
				</InputGroup.Text>
			}
			{ tags &&
				<Tags tags={ tags } callback={ onChange } trigger={ <InputGroup.Text><span className="bi bi-braces" /></InputGroup.Text> } />
			}
		</>
		:
		<Form.Select
			aria-label=""
			value={ value }
			onChange={ update }
		>
			{
				objectToMappable( choices, 'value', 'label' ).map( ( paramTypeKey, keyIndex ) => {
					return <option key={ keyIndex } value={ paramTypeKey.value }>{ paramTypeKey.label ?? paramTypeKey.value }</option>
				} )
			}
		</Form.Select>;

	return (
		<Col>
			<InputGroup>
			{ ( customizable && choices && 'object' !== typeof value ) ?
				<>
					{ field }
					<InputGroup.Text onClick={ toggleCustom } aria-label="Custom input">
						{ custom ? <span className="bi bi-view-list" /> : <span className="bi bi-input-cursor-text" /> }
					</InputGroup.Text>
				</>
				:
				field
			}
			</InputGroup>
		</Col>
	);
}
