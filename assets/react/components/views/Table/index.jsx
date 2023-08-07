import React from 'react';
import { Table } from 'react-bootstrap';

import TableRow from './Row';
import TableHead from './Head';

import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';

export default function TableView( props ) {

	const {
		callbacks = {},
		columns = {},
		items = {},
		type,
	} = props;

	const columnMap = objectToMappable( columns, 'key', 'label' );

	return (
		<Table responsive hover className="align-middle table-list">
			<TableHead columns={ columnMap } />
			<tbody>
			{ ! isEmpty( items ) &&
				objectToMappable( items, 'key' ).map( ( item, index ) => {
					return (
						<TableRow key={ item.key ?? index } type={ type } columns={ columnMap } item={ item } callbacks={ callbacks } />
					)
				} )
			}
			{ isEmpty( items ) &&
				<tr>
					<td colSpan={ columnMap.length } className="text-center">
						No items found.
					</td>
				</tr>
			}
			</tbody>
		</Table>
	);
}
