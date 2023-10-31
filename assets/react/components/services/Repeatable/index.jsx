import React, { useCallback } from 'react';
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
		max,
		addCallback,
		reorderCallback,
	} = props;

	const parseItems = useCallback( ( items ) => {
		return items.map( ( item ) => {
			if ( ! item.hasOwnProperty( '_ref' ) ) {
				item._ref = createRefId();
			}
			if ( ! item.hasOwnProperty( '_disabled' ) ) {
				item._disabled = item.value._disabled ?? false;
			}

			if ( ! item.header ) {
				item.header = (
					// @Todo cache unchanged keys.
					<Header key={ item._key ? item._key + '_header' : undefined } { ...item } />
				)
			}

			if ( item.actions && ! React.isValidElement( item.actions ) ) {
				// If columns are defined we only should support columns.
				// @todo Move this to columns completely.
				item.actions = ! item.columns && (
					<Actions { ...item } className={ inline ? 'mx-2' : 'mx-3' } />
				)
			}

			if ( ( item.body || item.fields ) && ! React.isValidElement( item.body ) ) {
				item.body = (
					// @Todo cache unchanged keys.
					<Body key={ item._key ? item._key + '_body' : undefined } { ...item } { ...item.body ?? {} } />
				)
			}

			return item;
		} );
	}, [ inline ] );

	const toolbar = props.toolbar || false !== props.toolbar && (
		<Button variant="outline-secondary" onClick={ addCallback }>Add</Button>
	);

	if ( ! items || ! items.length ) {
		return (
			<InputGroup className="p-2 border bg-body">
				{ toolbar }
			</InputGroup>
		);
	}

	return (
		<Stack gap={0} className="bg-body">
			{ inline &&
				<RepeatableList items={ parseItems( items ) } sortable={ sortable } reorderCallback={ reorderCallback } />
			}
			{ ! inline &&
				<RepeatableAccordion items={ parseItems( items ) } sortable={ sortable } reorderCallback={ reorderCallback } />
			}
			{ ( ! max || max > items.length ) &&
			  <InputGroup className="p-2 border border-top-0 bg-body">
				  { toolbar }
			  </InputGroup>
			}
		</Stack>
	);
}
