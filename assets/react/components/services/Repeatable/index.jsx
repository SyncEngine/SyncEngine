import React, { useCallback, useContext } from 'react';
import { Button, InputGroup, Stack } from 'react-bootstrap';

import Body from './Body';
import Header from './Header';
import RepeatableAccordion from './Accordion';
import Actions from './Actions';
import { createRefId } from '../../../utils/globals';
import RepeatableList from './List';
import { FieldContext, FieldsContext } from '../../../context/FieldsContext';
import { mergeClassNames } from '../../../utils/props';

export default function Repeatable( props ) {

	const {
		activeKey,
		items,
		inline = false,
		sortable = false,
		editable = true,
		max,
		addCallback,
		reorderCallback,
	} = props;

	const fieldsContext = useContext( FieldsContext );
	const field = useContext( FieldContext );

	const parseItems = useCallback( ( items ) => {
		return items.map( ( item, index ) => {
			const value = item.value ?? {};

			if ( ! item.hasOwnProperty( '_ref' ) ) {
				item._ref = createRefId();
			}
			if ( ! item.hasOwnProperty( '_disabled' ) ) {
				item._disabled = value ? value._disabled : false;
			}

			const context = FieldsContext.create( ( field.name && field.name + '.' ) + item._ref, value, item._ref, fieldsContext, field );
			context._index = context; // Store index context.

			if ( ! item.header ) {
				item.header = (
					// @Todo cache unchanged keys.
					<FieldsContext.Provider value={ context }>
						<Header key={ item._key ? item._key + '_header' : undefined } { ...item } />
					</FieldsContext.Provider>
				)
			}

			if ( item.actions && ! React.isValidElement( item.actions ) ) {
				// If columns are defined we only should support columns.
				// @todo Move this to columns completely.
				item.actions = ! item.columns && (
					<FieldsContext.Provider value={ context }>
						<Actions { ...item } className={ inline ? 'mx-2' : 'mx-3' } />
					</FieldsContext.Provider>
				)
			}

			if ( ( item.body || item.fields ) && ! React.isValidElement( item.body ) ) {
				// @Todo cache unchanged keys.
				item.body = (
					<FieldsContext.Provider value={ context }>
						<Body key={ item._key ? item._key + '_body' : undefined } { ...item } { ...item.body ?? {} } />
					</FieldsContext.Provider>
				)
			}

			return item;
		} );
	}, [ inline, editable ] );

	const toolbar = props.toolbar || false !== props.toolbar && (
		<Button variant="outline-secondary" onClick={ addCallback }>Add</Button>
	);

	if ( ! items || ! items.length ) {
		return <Toolbar>{ toolbar }</Toolbar>;
	}

	return (
		<Stack gap={0} className="bg-body">
			{ inline ?
				<RepeatableList items={ parseItems( items ) } sortable={ sortable } reorderCallback={ reorderCallback } activeKey={ activeKey } />
				:
				<RepeatableAccordion items={ parseItems( items ) } sortable={ sortable } reorderCallback={ reorderCallback } activeKey={ activeKey } />
			}
			{ ( ! max || max > items.length ) &&
				<Toolbar className="p-2 border">
					{ toolbar }
				</Toolbar>
			}
		</Stack>
	);
}

function Toolbar( props ) {
	return <InputGroup { ...props } className={ mergeClassNames( "fields-container position-sticky sticky-bottom", props.className ) } />
}
