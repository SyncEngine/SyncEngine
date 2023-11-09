import React, { forwardRef } from 'react';
import { useTranslation } from 'react-i18next';
import { Row } from 'react-bootstrap';
import ColumnsCol from "./Col";

export default forwardRef( function ColumnsRow( props, ref ) {
	const { t } = useTranslation();

	const {
		data,
		columnMap,
		nest = false,
		sortable = false,
		removable = true,
		onChange,
	} = props;

	const update = ( column, value ) => {
		data[ column ] = value;
		onChange( data );
	}

	const remove = () => {
		onChange( null );
	}

	return (
		<Row className="g-1" ref={ ref } style={ props.style }>
			{ props.sortableHandle &&
				<span className="icon-link lh-1 col col-auto">{props.sortableHandle}</span>
			}
			{
				columnMap.map( ( column, index ) => {
					const columnName = column.key ?? column.name ?? '';
					const choices = ( column.hasOwnProperty( 'choices' ) && Object.keys( column.choices ).length ) ? column.choices : null;
					const value = ( data.hasOwnProperty( columnName ) ) ? data[ columnName ] : '';

					return (
						<ColumnsCol
							{ ...column }
							key={ index }
							value={ value }
							choices={ choices }
							nest={ nest }
							onChange={ ( value ) => { update( columnName, value ) } }
							taggable={ column.taggable ?? props.taggable }
						/>
					)
				} )
			}
			{ removable &&
				<span
					title={ t('Delete') }
					role="button"
					className="bi bi-dash-circle icon-link text-danger-hover col col-auto"
					onClick={ remove }
				/>
			}
		</Row>
	);
} )
