import React, { forwardRef } from 'react';
import { useTranslation } from 'react-i18next';
import { Col, Row } from 'react-bootstrap';
import GridInput from './Input';
import Field from '../../form/Field';
import useConditions from '../../../hooks/useConditions';
import Icon from '../../partials/Icon';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';

export default forwardRef( function GridRow( props, ref ) {
	const { t } = useTranslation();
	const [ valid, validate ] = useConditions();

	const {
		data,
		columnMap,
		nest = false,
		sortable = false,
		editable = true,
		removable = true,
		onChange,
		onPaste,
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
			    <td className="table-cell-shrink position-sticky start-0 z-2"><span className="icon-link lh-1 d-flex">{ props.sortableHandle }</span></td>
			}
			<td>
			<Row className="g-1 flex-nowrap z-1">
			{
				columnMap.map( ( column, index ) => {
					if ( column.conditions && ! validate( column.conditions, data ) ) {
						return;
					}
					const columnLabel = column.label ?? '';
					const columnName = column.key ?? column.name ?? '';
					const choices = ( ! isEmpty( column.choices ) ) ? column.choices : null;
					const value = ( data.hasOwnProperty( columnName ) ) ? data[ columnName ] : null;

					const onChange = ( value ) => { isFieldEditable( props ) && update( columnName, value ) };

					const style = column.style ?? { minWidth: 200 };

					if ( props.hasOwnProperty( 'editable' ) ) {
						column.editable = props.editable;
					}
					if ( props.hasOwnProperty( 'disabled' ) ) {
						column.disabled = props.disabled;
					}

					let field;

					if ( column.type ) {
						if ( React.isValidElement( column.type ) ) {
							field = React.cloneElement( column.type, {
								compact: true,
								readonly: column.readonly,
								readOnly: column.readOnly,
								editable: column.editable,
								disabled: column.disabled,
								help: null,
								value: value,
								onChange: onChange,
								onPaste: onPaste,
							} );
						} else {
							field = (
								<Field
									{ ...column }
									compact={ true }
									help={ null }
									value={ value }
									onChange={ onChange }
									onPaste={ onPaste }
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
								onPaste={ onPaste }
								taggable={ column.taggable ?? props.taggable }
							/>
						)
					}

					return (
						<Col
							key={ index }
							className="d-flex align-items-center"
							style={ style }
							aria-label={ column["aria-label"] ?? columnLabel }
						>
							{ field }
						</Col>
					)
				} )
			}
			</Row>
			</td>
			{ removable &&
			    <td className="table-cell-shrink position-sticky end-0 z-2">
					<Icon
						title={ t('Remove') }
						role="button"
						icon="remove"
						className="icon-link text-danger-hover d-flex"
						onClick={ remove }
					/>
			    </td>
			}
		</tr>
	);
} )
