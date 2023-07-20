import React from 'react';
import Actions from './Cols/Actions';
import Info from './Cols/Info';
import Ref from './Cols/Ref';
import { Placeholder } from 'react-bootstrap';

export default function ListCol( props ) {
	const {
		column,
		item,
		content,
	} = props;

	let colContent;
	let classes = '';

	if ( 'placeholder' === item ) {
		return (
			<td>
				<Placeholder animation="glow">
					<Placeholder xs={12} size="lg" />
				</Placeholder>
			</td>
		)
	}

	switch ( column.key ?? column.name ?? column ) {
		case 'ref':
		case 'id':
			colContent = <Ref { ...props } />
			classes += 'text-start table-cell-shrink';
			break;
		case 'info':
			colContent = <Info { ...props } />
			break;
		case 'actions':
			colContent = <Actions { ...props } actions={ column.actions } />
			classes += 'text-end table-cell-shrink';
			break;
		default:
			// Maybe parse object as JSON?
			colContent = content;
			break;
	}

	return (
		<td className={ classes }>
			{ colContent }
		</td>
	);
}
