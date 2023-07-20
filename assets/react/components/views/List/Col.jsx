import React from 'react';
import Col from 'react-bootstrap/Col';
import Actions from './Cols/Actions';
import Info from './Cols/Info';

export default function ColumnsCol( props ) {
	const {
		column,
		content,
		onChange,
	} = props;

	let colContent = '';

	switch ( column ) {
		case 'info':
			colContent = <Info { ...content } />
			break;
		case 'actions':
			colContent = <Actions { ...content } />
			break;
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
