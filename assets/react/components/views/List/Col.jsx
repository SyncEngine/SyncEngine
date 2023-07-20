import React from 'react';
import Actions from './Cols/Actions';
import Info from './Cols/Info';
import Ref from './Cols/Ref';

export default function ListCol( props ) {
	const {
		column,
		content,
	} = props;

	let colContent;
	let classes = '';

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
