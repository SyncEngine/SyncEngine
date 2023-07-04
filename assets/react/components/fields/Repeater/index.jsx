import React, { useState } from 'react';

import Repeatable from "../../services/Repeatable";
import { createRefId } from "../../../utils/globals";

export default function Repeater( props ) {

	const {
		fields = props.fieldset ?? {},
		value = props.default ?? [],
		inline = false,
		sortable = true,
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

	const items = props.items || rows.map( ( row, index ) => {
		const label = ( index + 1 ) + ( row._label ? ': ' + row._label : '' );
		const description = row._description ?? '';

		return {
			_ref: row._ref,
			value: row,
			label: label,
			description: description,
			fields: fields,
			actions: { 'disable': toggleRow, 'delete': removeRow, },
			onChange: ( input ) => updateRow( input, row._ref ),
		}
	} );

	return (
		<Repeatable items={ items } inline={ inline } sortable={ sortable } addCallback={ addRow } reorderCallback={ reorderRows }></Repeatable>
	);
}
