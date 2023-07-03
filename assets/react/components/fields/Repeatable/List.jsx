import React from 'react';
import { ListGroup, Stack } from 'react-bootstrap';

import Sortable from "../../services/Sortable";
import SortableIcon from "../../services/Sortable/SortableIcon";

export default function RepeatableList( props ) {

	const {
		items,
		sortable = true,
		reorderCallback,
	} = props;

	if ( ! sortable ) {
		return (
			<ListGroup>
				{ items.map( ( row, index ) => {
					const {
						header,
						body,
						actions,
						onClick,
						ref = index,
					} = row;

					const attributes = onClick && {
						action: true,
						onClick: onClick,
					};

					return (
						<ListGroup.Item key={ ref } action={ 'function' === typeof onClick } onClick={ onClick }>
							{ header }
							{ body }
							{ actions }
						</ListGroup.Item>
					)
				} ) }
			</ListGroup>
		)
	}

	return (
		<ListGroup>
			<Sortable
				setItems={ reorderCallback }
				items={ items.map( ( row ) => {
					const {
						header,
						body,
						actions,
						onClick,
						ref,
						value,
					} = row;

					const attributes = onClick && {
						action: true,
						onClick: onClick,
					};

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
				} ) }
			/>
		</ListGroup>
	);
}
