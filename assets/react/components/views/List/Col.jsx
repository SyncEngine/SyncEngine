import React from 'react';
import Col from 'react-bootstrap/Col';

export default function ColumnsCol( props ) {
	const {
		column,
		content,
		onChange,
	} = props;

	let colContent = '';

	switch ( column ) {
		default:
			// Maybe parse object as JSON?
			colContent = content;
			break;
	}

	return (
		<Col>
			{ colContent }
		</Col>
	);
}
