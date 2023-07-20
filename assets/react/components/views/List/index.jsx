import React from 'react';
import { Table, Row } from 'react-bootstrap';

import { objectToMappable } from '../../../utils/data';
import ListRow from './Row';
import ListHead from './Head';
import { isEmpty } from '../../../utils/conditionals';

export default function List( props ) {

	const {
		callbacks = {},
		columns = {},
		items = {},
		type,
	} = props;

	const columnMap = objectToMappable( columns, 'key', 'label' );

	return (
		<Table hover className="align-middle">
			<ListHead columns={ columnMap } />
			<tbody>
			{ ! isEmpty( items ) &&
				objectToMappable( items, 'key' ).map( ( item, index ) => {
					return (
						<ListRow key={ item.key ?? index } type={ type } columns={ columnMap } item={ item } callbacks={ callbacks } />
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
