import React from 'react';
import { Table, Row } from 'react-bootstrap';

import { objectToMappable } from '../../../utils/data';
import ListRow from './Row';
import ListHead from './Head';

export default function List( props ) {

	const {
		callbacks = {},
		columns = {},
		items = {},
		type,
	} = props;

	const columnMap = objectToMappable( columns, 'key', 'label' );

	return (
		<Table>
			<ListHead columns={ columnMap } />
			<tbody>
			{
				objectToMappable( items, 'key' ).map( ( item, index ) => {
					return (
						<ListRow key={ item.key ?? index } type={ type } columns={ columnMap } item={ item } callbacks={ callbacks } />
					)
				} )
			}
			</tbody>
		</Table>
	);
}
