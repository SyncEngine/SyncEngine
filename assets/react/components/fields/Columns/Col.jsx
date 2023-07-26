import React from 'react';
import { Form, Col } from 'react-bootstrap';

import { objectToMappable } from "../../../utils/data";

export default function ColumnsCol( props ) {
	const {
		value,
		predefined,
		nest = false,
		onChange,
	} = props;

	// @todo Implement param nesting.

	return (
		<Col>
			{ ( predefined && 'object' !== typeof value ) &&
			  <Form.Select
				  aria-label=""
				  value={ value }
				  onChange={ ( event ) => { onChange( event.target.value ) } }
			  >
				  {
					  objectToMappable( predefined, 'value', 'label' ).map( ( paramTypeKey, keyIndex ) => {
						  return <option key={ keyIndex } value={ paramTypeKey.value }>{ paramTypeKey.label ?? paramTypeKey.value }</option>
					  } )
				  }
			  </Form.Select>
			}
			{ ! predefined &&
			  <Form.Control
				  column="text"
				  value={ ( 'object' === typeof value ) ? JSON.stringify( value ) : value }
				  onChange={ ( event ) => { onChange( event.target.value ) } }
			  />
			}
		</Col>
	);
}
