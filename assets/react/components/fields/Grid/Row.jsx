import React, { forwardRef } from 'react';
import { useTranslation } from 'react-i18next';
import { Col, Row } from 'react-bootstrap';
import GridInput from './Input';
import Field from '../../form/Field';
import useConditions from '../../../hooks/useConditions';

export default forwardRef( function GridRow( props, ref ) {
	const { t } = useTranslation();
	const [ valid, validate ] = useConditions();

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
		<tr ref={ ref } style={ props.style }>
			{ props.sortableHandle &&
			    <td className="table-cell-shrink position-sticky start-0 bg-body z-1"><span className="icon-link lh-1 d-flex">{ props.sortableHandle }</span></td>
			}
			<td>
			<Row className="g-1 flex-nowrap">
			{
				columnMap.map( ( column, index ) => {
					const columnLabel = column.label ?? '';
					const columnName = column.key ?? column.name ?? '';
					const choices = ( column.hasOwnProperty( 'choices' ) && Object.keys( column.choices ).length ) ? column.choices : null;
					const value = ( data.hasOwnProperty( columnName ) ) ? data[ columnName ] : '';

					if ( column.conditions && ! validate( column.conditions, data ) ) {
						return;
					}

					const onChange = ( value ) => { update( columnName, value ) };

					const style = column.style ?? { minWidth: 200 };

					let field;

					if ( column.type ) {
						if ( React.isValidElement( column.type ) ) {
							field = React.cloneElement( column.type, {
								"aria-label": column["aria-label"] ?? column.label,
								compact: true,
								help: null,
								value: value,
								onChange: onChange,
							} );
						} else {
							field = (
								<Field
									{ ...column }
									aria-label={ column["aria-label"] ?? column.label }
									compact={ true }
									help={ null }
									value={ value }
									onChange={ onChange }
								/>
							)
						}
					} else {
						field = (
							<GridInput
								{ ...column }
								value={ value }
								choices={ choices }
								nest={ nest }
								onChange={ onChange }
								taggable={ column.taggable ?? props.taggable }
							/>
						)
					}

					return (
						<Col key={ index } className="d-flex align-items-center" style={ style } aria-label={ column["aria-label"] ?? columnLabel }>
							{ field }
						</Col>
					)
				} )
			}
			</Row>
			</td>
			{ removable &&
			    <td className="table-cell-shrink position-sticky end-0 bg-body z-1">
					<span
						title={ t('Delete') }
						role="button"
						className="bi bi-dash-circle icon-link text-danger-hover d-flex"
						onClick={ remove }
					/>
			    </td>
			}
		</tr>
	);
} )
