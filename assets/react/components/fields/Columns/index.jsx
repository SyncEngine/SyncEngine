import React, { useCallback, useState } from 'react';
import { Stack } from 'react-bootstrap';

import ColumnsHead from './Head';
import ColumnsRow from './Row';
import Sortable from '../../services/Sortable';

import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';
import { createRefId } from '../../../utils/globals';

export default function Columns( props ) {

	const {
		columns = {},
		nest = false,
		sortable = false,
		removable = true,
		taggable = true,
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
		setValue( newValue );

		// Remove refs.
		onChange( newValue.map( item => { item = { ...item }; delete item._ref; return item } ) );
	}

	const updateIndex = ( index, newValue ) => {
		let filteredValue = [];

		// Set new value.
		value[ index ] = { ...newValue };

		// Remove empty values.
		for ( let i = 0; i < value.length; i++ ) {
			if ( ! Object.values( value[ i ] ).every( x => isEmpty( x ) ) ) {
				filteredValue.push( value[ i ] );
			}
		}

		updateValue( filteredValue );
	}

	if ( ! value.length || ! isEmpty( value[ value.length - 1 ] ) ) {
		value.push( {} );
	}

	let items = [];
	if ( sortable ) {
		items = (
			<Sortable
				setItems={ updateValue }
				items={ value.map( ( row, index ) => {
					return {
						_ref: row._ref,
						value: row,
						component: ColumnsRow,
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
	} else {
		items = (
			<>
				{
					value.map( ( row, index ) => {
						return (
							<ColumnsRow
								key={ index }
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
			</>
		);
	}

	return (
		<Stack gap="1">
			{ columnMap &&
				<ColumnsHead columnMap={ columnMap } sortable={ sortable } removable={ removable } />
			}
			{ items }
		</Stack>
	);
}
