import React, { useCallback, useState } from 'react';
import { Table } from 'react-bootstrap';

import GridHead from './Head';
import GridRow from './Row';
import SortableTable from '../../services/Sortable/SortableTable';

import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';

export default function Grid( props ) {

	const {
		columns = {},
		nest = false,
		sortable = false,
		removable = true,
		taggable = false,
		onChange,
	} = props;

	const columnMap = objectToMappable( columns, 'name', 'label' );

	const parseValue = useCallback( ( value ) => {
		return objectToMappable( value ).map( ( row ) => {
			if ( ! row.hasOwnProperty( '_ref' ) ) {
				row._ref = createRefId();
			}
			return row;
		} )
	}, [] );

	const [ value, setValue ] = useState( ( Array.isArray( props.value ) && props.value.length ) ? parseValue( props.value ) : [] );

	const updateValue = ( newValue ) => {
		let stateValue = [];
		let upstreamValue = []; // Without refs.

		// Remove empty values.
		for ( let i = 0; i < newValue.length; i++ ) {
			const upstream = structuredClone( newValue[ i ] );
			delete upstream._ref;
			if ( ! Object.values( upstream ).every( x => isEmpty( x ) ) ) {
				stateValue.push( newValue[ i ] );
				upstreamValue.push( upstream );
			}
		}

		setValue( stateValue );
		onChange( upstreamValue );
	}

	const updateIndex = ( index, newValue ) => {

		// Set new value.
		value[ index ] = { ...newValue };

		updateValue( value );
	}

	let appendEmptyRow = ! value.length;
	if ( ! appendEmptyRow ) {
		// Check if last item is empty, if not, add new row.
		const last = structuredClone( value[ value.length -1 ] ); last && delete last._ref;
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
