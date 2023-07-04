import React, { useState } from 'react';
import { Stack, InputGroup, Button } from 'react-bootstrap';

import Body from "./Body";
import Header from "./Header";
import RepeatableAccordion from "./Accordion";
import Actions from "./Actions";
import { createRefId } from "../../../utils/globals";
import RepeatableList from "./List";

export default function Repeatable( props ) {

	const {
		items,
		inline = false,
		sortable = false,
		addCallback,
		reorderCallback,
	} = props;

	const parseItems = ( items ) => {
		return items.map( ( item ) => {
			if ( ! item.hasOwnProperty( '_ref' ) ) {
				item._ref = createRefId();
			}
			return item;
		} )
	}

	const toolbar = props.toolbar || (
		<Button variant="outline-secondary" onClick={ addCallback }>Add</Button>
	);

	if ( ! items || ! items.length ) {
		return toolbar;
	}

	return (
		<Stack gap={0}>
			{ inline &&
				<RepeatableList items={ parseItems( items ) } sortable={ sortable } reorderCallback={ reorderCallback } />
			}
			{ ! inline &&
				<RepeatableAccordion items={ parseItems( items ) } sortable={ sortable } reorderCallback={ reorderCallback } />
			}
			<InputGroup className="p-2 border border-top-0">
				{ toolbar }
			</InputGroup>
		</Stack>
	);
}
