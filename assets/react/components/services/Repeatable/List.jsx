import React from 'react';
import { ListGroup, Stack } from 'react-bootstrap';

import Sortable from "../../services/Sortable";
import SortableIcon from "../../services/Sortable/SortableIcon";

export default function RepeatableList( props ) {

	const {
		sortable = true,
		reorderCallback,
	} = props;

	const items = props.items.map( ( row, index ) => {
		const {
			header,
			body,
			actions,
			onClick,
			value,
			ref = index,
		} = row;

		const attributes = onClick && {
			action: true,
			onClick: onClick,
		};

		if ( ! sortable ) {
			return (
				<ListGroup.Item key={ ref } action={ 'function' === typeof onClick } onClick={ onClick }>
					{ header }
					{ body }
					{ actions }
				</ListGroup.Item>
			)
		}

		return {
			id: ref,
			value: value,
			component: ListGroup.Item,
			attributes: attributes ?? {},
			header: (
				<Stack direction="horizontal" gap={3}>
					<SortableIcon></SortableIcon>
					{ header }
					{ body }
					{ actions }
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
