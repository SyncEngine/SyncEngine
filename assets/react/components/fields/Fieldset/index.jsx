import React, { useState } from 'react';
import { Stack, Accordion, Badge, InputGroup, FormCheck, Button } from 'react-bootstrap';

import Row from "./Row";

import ConfirmDelete from "../../form/ConfirmDelete";
import Sortable from "../../services/Sortable";

import { isSet } from "../../../utils/conditionals";
import { createRefId } from "../../../utils/globals";

export default function Fieldset( props ) {

	const {
		fieldset = {},
		value = props.default ?? [],
		rowLabel,
		rowDescription,
		onChange,
	} = props;

	const parseValue = ( value ) => {
		return value.map( ( row ) => {
			if ( ! row.hasOwnProperty( '_ref' ) ) {
				row._ref = createRefId();
			}
			return row;
		} )
	}

	const [ rows, setRows ] = useState( parseValue( value ) );

	const getRowRefs = () => rows.map( item => item._ref );
	const getRowIndex = ( ref ) => getRowRefs().indexOf( ref );

	const addRow = () => {
		let newRows = [ ...rows ];
		newRows.push( { _ref: createRefId() } );
		updateRows( newRows );
	}

	const removeRow = ( ref ) => {
		let newRows = [ ...rows ];
		newRows.splice( getRowIndex( ref ), 1 );
		updateRows( newRows );
	}

	const updateRow = ( input, ref ) => {
		const index = getRowIndex( ref );
		let newRows = [ ...rows ];
		if ( newRows[ index ]._disabled ) {
			input._disabled = true;
		}
		newRows[ index ] = input;
		updateRows( newRows );
	}

	const updateRows = ( newRows ) => {
		setRows( newRows );
		onChange( newRows );
	}

	const toggleRow = ( ref ) => {
		const index = getRowIndex( ref );
		let newRows = [ ...rows ];
		if ( newRows[ index ]._disabled ) {
			delete newRows[ index ]._disabled;
		} else {
			newRows[ index ]._disabled = true;
		}
		updateRows( newRows );
	}

	const reorderRows = ( newRows ) => {
		updateRows( newRows );
	}

	const Toolbar = (
		<Button variant="outline-secondary" onClick={ addRow }>Add</Button>
	);

	if ( ! rows || ! rows.length ) {
		return Toolbar;
	}

	return (
		<Stack gap={0}>
			<Accordion>
				<Sortable
					setItems={ reorderRows }
					items={
						rows.map( ( row, index ) => {
							const label = ( index + 1 ) + ( row._label ? ': ' + row._label : '' );
							const description = row._description ?? '';

							return {
								id: row._ref,
								value: row,
								component: Accordion.Item,
								attributes: {
									eventKey: row._ref,
								},
								header: {
									component: Accordion.Header,
									children: (
										<>
											<Header label={ label } description={ description } row={ row } />
											<FormCheck type="switch" defaultChecked={ ! ( row._disabled ?? false ) } onClick={ ( e ) => {
												e.stopPropagation();
												toggleRow( row._ref );
											} } />
											<ConfirmDelete callback={ () => removeRow( row._ref ) } />
										</>
									)
								},
								body: (
									<Accordion.Body className="bg-body-tertiary p-1">
										<div className="bg-body p-3">
											<Row fields={ fieldset } value={ row } onChange={ ( input ) => updateRow( input, row._ref ) } />
										</div>
									</Accordion.Body>
								),
							}
						} )
					}
				/>
			</Accordion>
			<InputGroup className="p-2 border border-top-0">
				{ Toolbar }
			</InputGroup>
		</Stack>
	);
}
