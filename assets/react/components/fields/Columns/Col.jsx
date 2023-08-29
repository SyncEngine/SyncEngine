import React from 'react';
import { Form, Col } from 'react-bootstrap';

import { objectToMappable } from "../../../utils/data";

export default function ColumnsCol( props ) {
	const {
		value,
		choices,
		nest = false,
		onChange,
	} = props;

	// @todo Implement param nesting.

	return (
		<Col>
			{ ( choices && 'object' !== typeof value ) &&
			  <Form.Select
				  aria-label=""
				  value={ value }
				  onChange={ ( event ) => { onChange( event.target.value ) } }
			  >
				  {
					  objectToMappable( choices, 'value', 'label' ).map( ( paramTypeKey, keyIndex ) => {
						  return <option key={ keyIndex } value={ paramTypeKey.value }>{ paramTypeKey.label ?? paramTypeKey.value }</option>
					  } )
				  }
			  </Form.Select>
			}
			{ ! choices &&
			  <Form.Control
				  column="text"
				  value={ ( 'object' === typeof value ) ? JSON.stringify( value ) : value }
				  onChange={ ( event ) => { onChange( event.target.value ) } }
			  />
			}
		</Col>
	);
}
