import React from 'react';
import Row from 'react-bootstrap/Row';
import ListCol from "./Col";
import { objectToMappable } from '../../../utils/data';

export default function ListRow( props ) {
	const {
		item,
		type,
		columns,
		callbacks,
	} = props;

	return (
		<tr>
			{
				objectToMappable( columns, 'key' ).map( ( column, index ) => {
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
				} )
			}
		</tr>
	);
}
