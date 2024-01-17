import React from 'react';
import { ListGroupItem, Stack } from 'react-bootstrap';
import ListCol from "./Col";
import { objectToMappable } from '../../../utils/data';

export default function ListRow( {
	item,
	type,
	columns,
	callbacks,
	wrap = true,
} ) {

	let row = columns &&
		objectToMappable( columns, 'key', 'label' ).map( ( column, index ) => {
			const columnName = column.key ?? column.name ?? '';
			const content = ( item.hasOwnProperty( columnName ) ) ? item[ columnName ] : '';

			return (
				<ListCol
					key={ item.key ?? index }
					column={ column }
					type={ type }
					item={ item.entity ?? item }
					content={ content }
					callbacks={ callbacks }
				/>
			)
		} );

	row = <Stack direction="horizontal" className="justify-content-between w-100" gap={2}>{ row }</Stack>

	if ( wrap ) {
		row = <ListGroupItem>{ row }</ListGroupItem>
	}

	return row;
}
