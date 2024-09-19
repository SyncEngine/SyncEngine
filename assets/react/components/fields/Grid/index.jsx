import React, { useState } from 'react';
import { Table } from 'react-bootstrap';

import GridHead from './Head';
import GridRow from './Row';
import SortableTable from '../../services/Sortable/SortableTable';

import { deepClone, objectToMappable } from '../../../utils/data';
import { isEmpty, isFieldEditable, isKey, isMultiline, isObject } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';

function parseValue( value, indexColumn, valueColumn, force ) {
	return objectToMappable( value, indexColumn, valueColumn, force ).map( ( row ) => {
		if ( ! row.hasOwnProperty( '_ref' ) ) {
			row._ref = createRefId();
		}
		return row;
	} )
}

function parseColumns( columns, values ) {
	if ( ! isEmpty( columns ) ) {
		return objectToMappable( columns, 'name', 'label' );
	}

	columns = {};
	for ( const i in values ) {
		if ( ! values.hasOwnProperty( i ) ) {
			continue;
		}
		for ( const column in values[i] ) {
			columns[ column ] = column;
		}
	}

	return objectToMappable( columns, 'name', 'label' );
}

export default function Grid( props ) {
	const editable = isFieldEditable( props );

	const {
		columns = {},
		nest = false,
		sortable = false,
		removable = editable,
		taggable = false,
		disabled = false,
		indexed = isObject( props.value ),
		onChange,
	} = props;

	const columnMap = parseColumns( columns, props.value );
	const params = indexed && isEmpty( columns );

	const indexColumn = isObject( indexed ) ? indexed : isKey( indexed ) ? { name: indexed, label: indexed } : { name: '_index', label: '_index' };
	const valueColumn = { name: '_value', label: '_value' };

	if ( params ) {
		columnMap.push( valueColumn );
	}
	if ( indexed ) {
		columnMap.unshift( indexColumn );
	}

	const [ value, setValue ] = useState( ! isEmpty( props.value ) ? parseValue( props.value, indexColumn.name, valueColumn.name, params ) : [] );

	const onPaste = props.onPaste ?? function( e ) {
		let paste = e.clipboardData.getData('Text');
		if ( isMultiline( paste ) ) {
			// @todo Opt-in through modal? And append instead of override?
			e.preventDefault();
			paste = paste.split( "\n" ).filter( String );

			updateValue( paste.map( item => {
				const columns = item.split( "\t" );
				const val = {};
				for ( const index in columns ) {
					val[ columnMap[ index ].name ] = columns[ index ];
				}
				return val;
			} ) );
		}
	}

	const updateValue = ( newValue ) => {
		let stateValue = [];
		let upstreamValue = []; // Without refs.

		// Remove empty values.
		for ( let i = 0; i < newValue.length; i++ ) {
			const upstream = deepClone( newValue[ i ] );
			delete upstream._ref;
			if ( ! Object.values( upstream ).every( x => isEmpty( x ) ) ) {
				stateValue.push( newValue[ i ] );
				upstreamValue.push( upstream );
			} else if ( i === ( newValue.length -1 ) ) {
				// Last row is empty, add to state to keep reference.
				stateValue.push( newValue[ i ] );
			}
		}

		if ( indexed ) {
			let upstreamObject = {};

			for ( let i = 0; i < upstreamValue.length; i++ ) {
				const _index = upstreamValue[ i ][ indexColumn.name ];
				delete upstreamValue[ i ][ indexColumn.name ];
				if ( params ) {
					upstreamObject[ _index ] = upstreamValue[ i ][ valueColumn.name ];
				} else {
					upstreamObject[ _index ] = upstreamValue[ i ];
				}
			}

			onChange( upstreamObject );
		} else {
			onChange( upstreamValue );
		}

		setValue( stateValue );
	}

	const updateIndex = ( index, newValue ) => {

		// Set new value.
		value[ index ] = { ...newValue };

		updateValue( value );
	}

	if ( editable ) {
		let appendEmptyRow = ! value.length;
		if ( ! appendEmptyRow ) {
			// Check if last item is empty, if not, add new row.
			const last = deepClone( value[ value.length -1 ] ); last && delete last._ref;
			appendEmptyRow = ! isEmpty( last );
		}

		if ( appendEmptyRow ) {
			value.push( { _ref: createRefId() } );
		}
	}

	const thead = columnMap && <GridHead columnMap={ columnMap } sortable={ sortable } removable={ removable } />;
	const tableProps = {
		responsive: true,
		borderless: true,
		className: "align-middle",
		size: "sm",
	}

	const rowProps = {
		columnMap: columnMap,
		nest: nest,
		editable: editable,
		taggable: taggable,
		removable: removable,
		disabled: disabled,
	}

	if ( sortable ) {
		return (
			<SortableTable
				table={ tableProps }
				thead={ thead }
				setItems={ updateValue }
				items={ value.map( ( row, index ) => {
					return {
						_ref: row._ref,
						_key: row._ref,
						value: row,
						component: GridRow,
						attributes: {
							...rowProps,
							data: row,
							onChange: ( value ) => { updateIndex( index, value ) },
							onPaste: onPaste,
						},
						handle: 'custom',
					}
				} ) }
			/>
		);
	}

	return (
		<Table { ...tableProps }>
			{ thead }
			<tbody>
				{
					value.map( ( row, index ) => {
						return (
							<GridRow
								{ ...rowProps }
								key={ row._ref }
								data={ row }
								onChange={ ( value ) => { updateIndex( index, value ) } }
								onPaste={ onPaste }
							/>
						)
					} )
				}
			</tbody>
		</Table>
	);
}
