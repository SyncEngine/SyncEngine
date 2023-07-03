import React from 'react';
import { Accordion } from 'react-bootstrap';

import Sortable from "../../services/Sortable";

export default function RepeatableAccordion( props ) {

	const {
		items,
		sortable = true,
		reorderCallback,
	} = props;

	if ( ! sortable ) {
		return (
			<Accordion>
				{ items.map( ( row ) => {
					const {
						header,
						actions,
						body,
						ref
					} = row;

					return (
						<Accordion.Item eventKey={ ref } key={ ref }>
							<Accordion.Header>
								{ header }
								{ actions }
							</Accordion.Header>
							<Accordion.Body className="bg-body-tertiary p-1">
								<div className="bg-body p-3">
									{ body }
								</div>
							</Accordion.Body>
						</Accordion.Item>
					)
				} ) }
			</Accordion>
		)
	}

	return (
		<Accordion>
			<Sortable
				setItems={ reorderCallback }
				items={ items.map( ( row ) => {
					const {
						header,
						actions,
						body,
						ref,
						value,
					} = row;

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
							<Accordion.Body className="bg-body-tertiary p-1">
								<div className="bg-body p-3">
									{ body }
								</div>
							</Accordion.Body>
						),
					}
				} ) }
			/>
		</Accordion>
	);
}
