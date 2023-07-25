import React from 'react';
import { ListGroup } from 'react-bootstrap';

import { objectToMappable } from '../../../utils/data';
import ListRow from './Row';
import { isEmpty } from '../../../utils/conditionals';

export default function ListView( props ) {

	const {
		callbacks = {},
		columns = {},
		items = {},
		type,
	} = props;

	return (
		<ListGroup>
			<tbody>
			{ ! isEmpty( items ) &&
				objectToMappable( items, 'key' ).map( ( item, index ) => {
					return (
						<ListRow key={ item.key ?? index } type={ type } columns={ columns } item={ item } callbacks={ callbacks } />
					)
				} )
			}
			{ isEmpty( items ) &&
				<ListGroup.Item className="text-center">
					No items found.
				</ListGroup.Item>
			}
			</tbody>
		</ListGroup>
	);
}
