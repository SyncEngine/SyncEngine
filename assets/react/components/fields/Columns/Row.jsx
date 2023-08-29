import React from 'react';
import { Row } from 'react-bootstrap';
import ColumnsCol from "./Col";

export default function ColumnsRow( props ) {
	const {
		data,
		columnMap,
		nest = false,
		onChange,
	} = props;

	const update = ( column, value ) => {
		data[ column ] = value;
		onChange( data );
	}

	return (
		<Row className="g-1">
			{
				columnMap.map( ( column, index ) => {
					const columnName = column.key ?? column.name ?? '';
					const choices = ( column.hasOwnProperty( 'choices' ) && Object.keys( column.choices ).length ) ? column.choices : null;
					const value = ( data.hasOwnProperty( columnName ) ) ? data[ columnName ] : '';

					return (
						<ColumnsCol
							key={ index }
							value={ value }
							choices={ choices }
							nest={ nest }
							onChange={ ( value ) => { update( columnName, value ) } }
						/>
					)
				} )
			}
		</Row>
	);
}
