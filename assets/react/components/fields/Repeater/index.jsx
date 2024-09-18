import React, { useState } from 'react';

import Repeatable from '../../services/Repeatable';
import RequestModal from '../../modals/RequestModal';
import { createRefId } from '../../../utils/globals';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import { deepClone, mapGetIndex } from '../../../utils/data';
import Icon from '../../partials/Icon';

export default function Repeater( props ) {
	const editable = isFieldEditable( props );

	const {
		fields = props.fieldset ?? {},
		value = props.default ?? [],
		inline = false,
		sortable = editable,
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

	const addRow = () => {
		let newRows = [ ...rows ];
		newRows.push( { _ref: createRefId() } );
		updateRows( newRows );
	}

	const removeRow = ( ref ) => {
		let newRows = [ ...rows ];
		newRows.splice( mapGetIndex( rows, ref, '_ref' ), 1 );
		updateRows( newRows );
	}

	const updateRow = ( input, ref ) => {
		const index = mapGetIndex( rows, ref, '_ref' );
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
		const index = mapGetIndex( rows, ref, '_ref' );
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

		const actions = {};

		if ( editable ) {
			for ( const key in props.actions ) {
				if ( props.actions.hasOwnProperty( key ) ) {
					let action = key;
					if ( isEmpty( props.actions[ key ] ) ) {
						continue;
					}
					let actionConfig = props.actions[ key ];
					if ( 'object' !== typeof props.actions[ key ] ) {
						action = props.actions[ key ];
						actionConfig = {
							type: props.actions[ key ]
						}
					}
					let actionProps = deepClone( actionConfig.props );
					// @todo Error if not provided?
					switch ( actionConfig.type ?? '' ) {
						case 'delete':
							actions[ action ] = removeRow;
							break;
						case 'disable':
							actions[ action ] = toggleRow;
							break;
						case 'request':
							// @todo Custom icons.
							actions[ action ] = <RequestModal { ...actionProps } item={ row }><Icon icon="request" className="icon-link" /></RequestModal>
							break;
						case 'link':
							actions[ action ] = <Icon icon="link" className="icon-link" { ...actionProps } />
							break;
					}
				}
			}
		}

		return {
			_ref: row._ref,
			value: row,
			label: label,
			description: description,
			fields: fields,
			actions: actions,
			onChange: ( input ) => editable && updateRow( input, row._ref ),
		}
	} );

	return (
		<Repeatable items={ items } inline={ inline } editable={ editable } sortable={ sortable } max={ props.max } addCallback={ addRow } reorderCallback={ reorderRows }></Repeatable>
	);
}
