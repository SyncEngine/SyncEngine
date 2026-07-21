import React from "react";
import PropTypes, { string, bool, func, oneOfType, node } from 'prop-types';
import { Form } from 'react-bootstrap';
import { suppress } from '../../utils/events';

export default function Description( {
	id,
	children,
	description = children,
	text = description,
	editable,
	onChange,
} ) {

	if ( editable && onChange && 'string' === typeof text ) {
		text = <>
			<span
				suppressContentEditableWarning
				contentEditable
				onBlur={ ( e ) => { e.target.innerText !== text ? onChange( e.target.innerText ) : null } }
				onClick={ suppress }
				className="d-inline-block focus-ring"
				style={ { cursor: 'text', minWidth: '3em' } }
			>
				{ text }
			</span>
		</>
	}

	return (
		<Form.Text id={ id + '_desc' } muted>
			{ description }
		</Form.Text>
	)
}

Description.propTypes = {
	id: PropTypes.string,
	text: oneOfType( [ string, node ] ),
	description: oneOfType( [ string, node ] ),
	editable: bool,
	onChange: func,
};
