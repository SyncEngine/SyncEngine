import React from "react";
import PropTypes from 'prop-types';
import { Form } from 'react-bootstrap';

export default function Description( {
	id,
	text,
	description = text,
} ) {
	return (
		<Form.Text id={ id + '_desc' } muted>
			{ description }
		</Form.Text>
	)
}

Description.propTypes = {
	id: PropTypes.string,
	text: PropTypes.string,
	description: PropTypes.string,
};
