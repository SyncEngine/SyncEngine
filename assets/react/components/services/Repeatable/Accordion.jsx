import React from 'react';
import { Accordion } from 'react-bootstrap';

import Sortable from "../../services/Sortable";

export default function RepeatableAccordion( props ) {

	const {
		sortable = true,
		reorderCallback,
	} = props;

	const items = props.items.map( ( item, index ) => {
		const {
			header,
			body,
			actions,
			value,
			_ref = index,
		} = item;

		if ( ! sortable ) {
			return (
				<Accordion.Item eventKey={ _ref } key={ _ref }>
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
			_ref: _ref,
			value: value,
			component: Accordion.Item,
			attributes: {
				eventKey: _ref,
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
