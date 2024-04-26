import React, { useState } from 'react';
import { Table } from 'react-bootstrap';

import GridHead from './Head';
import GridRow from './Row';
import SortableTable from '../../services/Sortable/SortableTable';

import { deepClone, objectToMappable } from '../../../utils/data';
import { isEmpty, isKey, isObject } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';

function parseValue( value, indexColumn, valueColumn, force ) {
	return objectToMappable( value, indexColumn, valueColumn, force ).map( ( row ) => {
		if ( ! row.hasOwnProperty( '_ref' ) ) {
			row._ref = createRefId();
		}
		return row;
	} )
}

export default function Grid( props ) {

	const {
		columns = {},
		nest = false,
		sortable = false,
		removable = true,
		taggable = false,
		indexed = isObject( props.value ),
		onChange,
	} = props;

	const columnMap = objectToMappable( columns, 'name', 'label' );
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

	let appendEmptyRow = ! value.length;
	if ( ! appendEmptyRow ) {
		// Check if last item is empty, if not, add new row.
		const last = deepClone( value[ value.length -1 ] ); last && delete last._ref;
		appendEmptyRow = ! isEmpty( last );
	}

	if ( appendEmptyRow ) {
		value.push( { _ref: createRefId() } );
	}

	const thead = columnMap && <GridHead columnMap={ columnMap } sortable={ sortable } removable={ removable } />;
	const tableProps = {
		responsive: true,
		borderless: true,
		className: "align-middle",
		size: "sm",
	}

	let items;
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
							data: row,
							columnMap: columnMap,
							nest: nest,
							onChange: ( value ) => { updateIndex( index, value ) },
							taggable: taggable,
							removable: removable,
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
								key={ row._ref }
								data={ row }
								columnMap={ columnMap }
								nest={ nest }
								onChange={ ( value ) => { updateIndex( index, value ) } }
								taggable={ taggable }
								removable={ removable }
							/>
						)
					} )
				}
			</tbody>
		</Table>
	);
}
