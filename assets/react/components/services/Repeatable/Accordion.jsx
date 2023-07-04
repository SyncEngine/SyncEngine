import React from 'react';
import { Accordion } from 'react-bootstrap';

import Sortable from "../../services/Sortable";

export default function RepeatableAccordion( props ) {

	const {
		sortable = true,
		reorderCallback,
	} = props;

	const items = props.items.map( ( row, index ) => {
		const {
			header,
			body,
			actions,
			value,
			ref = index,
		} = row;

		if ( ! sortable ) {
			return (
				<Accordion.Item eventKey={ ref } key={ ref }>
					<Accordion.Header>
						{ header }
						{ actions }
					</Accordion.Header>
					<Accordion.Body className="bg-primary-subtle p-1">
						<div className="bg-body p-3">
							{ body }
						</div>
					</Accordion.Body>
				</Accordion.Item>
			)
		}

		return {
			id: ref,
			value: value,
			component: Accordion.Item,
			attributes: {
				eventKey: ref,
			},
			header: {
				component: Accordion.Header,
				children: (
					<>
						{ header }
						{ actions }
					</>
				)
			},
			body: (
				<Accordion.Body className="bg-primary-subtle p-1">
					<div className="bg-body p-3">
						{ body }
					</div>
				</Accordion.Body>
			),
		};
	} );

	if ( ! sortable ) {
		return (
			<Accordion>
				{ items }
			</Accordion>
		)
	}

	return (
		<Accordion>
			<Sortable
				setItems={ reorderCallback }
				items={ items }
			/>
		</Accordion>
	);
}
