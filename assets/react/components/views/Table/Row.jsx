import React from 'react';
import PropTypes from 'prop-types';
import TableCol from "./Col";
import { objectToMappable } from '../../../utils/data';

export default function TableRow( {
	item,
	type,
	columns,
	callbacks,
	wrap = true,
} ) {

	let row = objectToMappable( columns, 'key' ).map( ( column, index ) => {
		const columnName = column.key ?? column.name ?? '';
		const content = ( item.hasOwnProperty( columnName ) ) ? item[ columnName ] : '';

		return (
			<TableCol
				key={ item.key ?? index }
				column={ column }
				type={ type }
				item={ item.entity ?? item }
				content={ content }
				callbacks={ callbacks }
			/>
		)
	} )

	if ( wrap ) {
		row = <tr>{ row }</tr>;
	}

	return row;
}

TableRow.propTypes = {
	item: PropTypes.object,
	type: PropTypes.string,
	columns: PropTypes.object,
	callbacks: PropTypes.object,
	wrap: PropTypes.bool,
};
