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

	return (
		<Table>
			<ListHead columns={ columns } />
			<tbody>
			{
				objectToMappable( items, 'key' ).map( ( item, index ) => {
					return (
						<ListRow key={ item.key ?? index } columns={ columns } item={ item } />
					)
				} )
			}
			</tbody>
		</Table>
	);
}
