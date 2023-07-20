import React from 'react';
import Row from 'react-bootstrap/Row';
import Col from "./Col";

export default function ListRow( props ) {
	const {
		item,
		columns,
		onChange,
	} = props;

	return (
		<Row className="g-1">
			{
				columns.map( ( column, index ) => {
					const columnName = column.key ?? column.name ?? '';
					const content = ( item.hasOwnProperty( columnName ) ) ? item[ columnName ] : '';

					return (
						<Col key={ item.key ?? index } column={ columnName } content={ content } onChange={ onChange } />
					)
				} )
			}
		</Row>
	);
}
