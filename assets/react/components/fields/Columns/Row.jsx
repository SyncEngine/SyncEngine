import React from 'react';
import Row from 'react-bootstrap/Row';
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
					const predefined = ( column.hasOwnProperty( 'predefined' ) && Object.keys( column.predefined ).length ) ? column.predefined : null;
					const value = ( data.hasOwnProperty( columnName ) ) ? data[ columnName ] : '';

					return (
						<ColumnsCol
							key={ index }
							value={ value }
							predefined={ predefined }
							nest={ nest }
							onChange={ ( value ) => { update( columnName, value ) } }
						/>
					)
				} )
			}
		</Row>
	);
}
