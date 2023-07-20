import React from 'react';
import Col from 'react-bootstrap/Col';
import Actions from './Cols/Actions';
import Info from './Cols/Info';
import Ref from './Cols/Ref';

export default function ListCol( props ) {
	const {
		column,
		content,
	} = props;

	let colContent;

	switch ( column ) {
		case 'ref':
		case 'id':
			colContent = <Ref { ...props } />
			break;
		case 'info':
			colContent = <Info { ...props } />
			break;
		case 'actions':
			colContent = <Actions { ...props } actions={ content } />
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
