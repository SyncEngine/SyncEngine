import React from 'react';
import { ListGroup, Stack } from 'react-bootstrap';

import Sortable from "../../services/Sortable";
import SortableIcon from "../../services/Sortable/SortableIcon";

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

		const attributes = onClick && {
			action: true,
			onClick: onClick,
		};

		if ( ! sortable ) {
			// If columns are defined we only should support columns.
			// @todo Move actions and body to columns completely.
			return (
				<ListGroup.Item key={ _ref } action={ 'function' === typeof onClick } onClick={ onClick }>
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
			attributes: attributes ?? {},
			header: (
				// If columns are defined we only should support columns.
				// @todo Move actions and body to columns completely.
				<Stack direction="horizontal" gap={3}>
					<SortableIcon></SortableIcon>
					{ header }
					{ ! item.columns && body }
					{ ! item.columns && actions }
				</Stack>
			),
		};
	} );

	if ( ! sortable ) {
		return (
			<ListGroup>
				{ items }
			</ListGroup>
		)
	}

	return (
		<ListGroup>
			<Sortable
				setItems={ reorderCallback }
				items={ items }
			/>
		</ListGroup>
	);
}
