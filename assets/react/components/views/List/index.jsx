import React from 'react';
import { Table } from 'react-bootstrap';
import Row from 'react-bootstrap/Row';
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
			<Row>
				<ListHead columns={ columns } />
			</Row>
			{
				objectToMappable( items, 'key' ).map( ( item, index ) => {
					return (
						<ListRow key={ item.key ?? index } columns={ columns } item={ item } />
					)
				} )
			}
		</Table>
	);
}
