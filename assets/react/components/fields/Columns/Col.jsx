import React, { useCallback, useState } from 'react';
import { Form, Col, InputGroup } from 'react-bootstrap';

import { objectToMappable } from "../../../utils/data";

export default function ColumnsCol( props ) {
	const {
		value,
		choices = {},
		customizable = false,
		nest = false,
		onChange,
	} = props;

	const [ custom, setCustom ] = useState( ( choices ) ? ( customizable && value && 'object' !== typeof value && ! choices.hasOwnProperty( value ) ) : true );

	const toggleCustom = () => setCustom( ! custom );

	const update = useCallback( ( event ) => { onChange( event.target.value ) }, [ onChange ] );

	// @todo Implement param nesting.

	const field = ( custom || ( ! choices && 'object' !== typeof value ) ) ?
		<Form.Control
			column="text"
			value={ ( 'object' === typeof value ) ? JSON.stringify( value ) : value }
			onChange={ update }
		/>
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
			{ ( customizable && choices && 'object' !== typeof value ) ?
				<InputGroup>
					{ field }
					<InputGroup.Text onClick={ toggleCustom } aria-label="Custom input">
						{ custom ? <span className="bi bi-view-list" /> : <span className="bi bi-input-cursor-text" /> }
					</InputGroup.Text>
				</InputGroup>
				:
				field
			}
		</Col>
	);
}
