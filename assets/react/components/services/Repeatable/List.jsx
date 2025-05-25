import React from 'react';
import { ListGroup } from 'react-bootstrap';

import Sortable from '../../services/Sortable';
import HStack from '../../partials/Stack/HStack';

export default function RepeatableList( props ) {

	const {
		sortable = true,
		reorderCallback,
	} = props;

	const items = props.items.map( ( item, index ) => {
		const {
			header,
			body,
			actions,
			onClick,
			value,
			_ref = index,
		} = item;

		const attributes = {
			key: _ref,
			eventKey: _ref,
			className: item.className,
			active: _ref === props.activeKey,
		}

		if ( 'function' === typeof onClick ) {
			attributes.action = true;
			attributes.onClick = onClick;
		}

		if ( ! sortable ) {
			// If columns are defined we only should support columns.
			// @todo Move actions and body to columns completely.
			return (
				<ListGroup.Item { ...attributes }>
					{ header }
					{ ! item.columns && body }
					{ ! item.columns && actions }
				</ListGroup.Item>
			)
		}

		return {
			_ref: _ref,
			value: value,
			component: ListGroup.Item,
			attributes: attributes,
			header: {
				// If columns are defined we only should support columns.
				// @todo Move actions and body to columns completely.
				component: HStack,
				children: (
					<>
						{ header }
						{ ! item.columns && body }
						{ ! item.columns && actions }
					</>
				)
			},
		};
	} );

	if ( ! sortable ) {
		return (
			<ListGroup activeKey={ props.activeKey }>
				{ items }
			</ListGroup>
		)
	}

	return (
		<ListGroup activeKey={ props.activeKey }>
			<Sortable
				setItems={ reorderCallback }
				items={ items }
			/>
		</ListGroup>
	);
}
