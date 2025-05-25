import React from 'react';
import AccordionSticky from '../../partials/AccordionSticky';

import Sortable from '../../services/Sortable';

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
				<AccordionSticky.Item eventKey={ _ref } key={ _ref }>
					<AccordionSticky.Header>
						{ header }
						{ actions }
					</AccordionSticky.Header>
					<AccordionSticky.Body className={ item.fields && 'fields-container' }>
						{ body }
					</AccordionSticky.Body>
				</AccordionSticky.Item>
			)
		}

		return {
			_ref: _ref,
			value: value,
			component: AccordionSticky.Item,
			attributes: {
				eventKey: _ref,
			},
			header: {
				component: AccordionSticky.Header,
				children: (
					<>
						{ header }
						{ actions }
					</>
				)
			},
			body: (
				<AccordionSticky.Collapse eventKey={ _ref } unmountOnExit>
					<AccordionSticky.Body className={ item.fields && 'fields-container' }>
						{ body }
					</AccordionSticky.Body>
				</AccordionSticky.Collapse>
			),
		};
	} );

	if ( ! sortable ) {
		return (
			<AccordionSticky activeKey={ props.activeKey } alwaysOpen>
				{ items }
			</AccordionSticky>
		)
	}

	return (
		<AccordionSticky activeKey={ props.activeKey } alwaysOpen>
			<Sortable
				setItems={ reorderCallback }
				items={ items }
			/>
		</AccordionSticky>
	);
}
