import React from 'react';
import { useTranslation } from 'react-i18next';
import { ListGroup } from 'react-bootstrap';

import ListRow from './Row';
import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';

export default function ListView( props ) {
	const { t } = useTranslation();

	const {
		callbacks = {},
		columns = {},
		items = {},
		type,
	} = props;

	return (
		<ListGroup>
		{ ! isEmpty( items ) &&
			objectToMappable( items, 'key' ).map( ( item, index ) => {
				return (
					<ListRow key={ item.key ?? index } type={ type } columns={ columns } item={ item } callbacks={ callbacks } />
				)
			} )
		}
		{ isEmpty( items ) &&
			<ListGroup.Item className="text-center">
				{ t('No items found') }
			</ListGroup.Item>
		}
		</ListGroup>
	);
}
