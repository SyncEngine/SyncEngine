import React, { useCallback, useContext, useState } from 'react';
import { Form, Col, InputGroup } from 'react-bootstrap';

import { objectToMappable } from "../../../utils/data";
import { TagsContext } from '../../../context/TagsContext';
import Tags from '../../partials/Tags';

export default function ColumnsCol( props ) {
	const {
		value,
		choices = {},
		customizable = false,
		taggable = false,
		nest = false,
		onChange,
	} = props;

	const tags = taggable ? useContext( TagsContext ) : null;

	const [ custom, setCustom ] = useState( ( choices ) ? ( customizable && value && 'object' !== typeof value && ! choices.hasOwnProperty( value ) ) : true );

	const toggleCustom = () => setCustom( ! custom );

	const update = useCallback(
		( event ) => { onChange( event.target.value ) },
		[ onChange ]
	);

	// @todo Implement param nesting.

	const field = ( custom || ( ! choices && 'object' !== typeof value ) ) ?
		<>
			<Form.Control
				column="text"
				placeholder={ props.placeholder ?? null }
				value={ ( 'object' === typeof value ) ? JSON.stringify( value ) : value }
				onChange={ update }
			/>
			{ tags &&
				<Tags tags={ tags } onClick={ onChange } trigger={ <InputGroup.Text><span className="bi bi-braces" /></InputGroup.Text> } />
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
