import React, { useContext, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { any, array, bool, func, object, oneOfType } from 'prop-types';
import { Table } from 'react-bootstrap';

import GridHead from './Head';
import GridRow from './Row';
import SortableTable from '../../services/Sortable/SortableTable';
import ChooseModal from '../../modals/ChooseModal';
import useClipboard from '../../../hooks/useClipboard';
import CopyToClipboard from '../../services/CopyToClipboard';
import { FieldContainerContext } from '../../form/Field/Container';

import { deepClone, objectToMappable } from '../../../utils/data';
import { isArray, isEmpty, isFieldEditable, isKey, isMultiline, isObject, isScalar } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';
import { suppress } from '../../../utils/events';

function parseValue( value, indexColumn, valueColumn, force ) {
	return objectToMappable( value, indexColumn, valueColumn, force ).map( ( row ) => {
		if ( ! row.hasOwnProperty( '_ref' ) ) {
			row._ref = createRefId();
		}
		/*if ( force && isObject( row[ valueColumn ] ) ) {
			row = { ...row, ...row[ valueColumn ] };
		}*/
		return row;
	} )
}

function parseColumns( columns, values ) {
	if ( ! isEmpty( columns ) ) {
		return objectToMappable( columns, 'name', 'header' );
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

	return objectToMappable( columns, 'name', 'header' );
}

export default function Grid( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );
	const [ clipboard, updateClipboard, clipboardAccessible ] = useClipboard( '', '', false );
	const containerContext = useContext( FieldContainerContext );

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

	const indexColumn = isObject( indexed ) ? indexed : isKey( indexed ) ? { name: indexed, header: indexed } : { name: '_index', header: '_index' };
	const valueColumn = { name: '_value', header: '_value' };

	if ( params ) {
		columnMap.push( valueColumn );
	}
	if ( indexed ) {
		columnMap.unshift( indexColumn );
	}

	const [ value, setValue ] = useState( ! isEmpty( props.value ) ? parseValue( props.value, indexColumn.name, valueColumn.name, params ) : [] );

	// Create a static, always up to date, value used for copy mechanism.
	// This makes sure copying will work even when the component is not re-rendered.
	const valueRef = useRef( [] );
	valueRef.current = isArray( value ) ? value : [];

	const [ pasteModal, setPasteModal ] = useState( null );

	const onPaste = props.onPaste ?? function( e ) {
		const paste = e.clipboardData.getData('Text');
		const value = valueRef.current;
		if ( isMultiline( paste ) ) {
			let pasteData = paste.replaceAll( "\r", '' ).split( "\n" ).filter( String );
			if ( pasteData.length > 1 ) {
				// @todo Opt-in through modal? And append instead of override?
				//e.preventDefault();

				const parsed = parseValue( pasteData.map( item => {
					const columns = item.split( "\t" );
					const val = {};
					for ( const index in columns ) {
						val[ columnMap[ index ].name ] = columns[ index ];
					}
					return val;
				} ) );

				setPasteModal(
					<ChooseModal
						text={ t( 'Multiline content detected, please select paste method.' )}
						choices={ [
							{
								label: t( 'Override' ),
								callback: () => {
									updateValue( parsed );
								}
							},
							{
								label: t( 'Merge' ),
								callback: () => {
									const merged = [ ...value ];
									parsed.map( ( item, index ) => {
										merged[ index ] = { ...( merged[ index ] ?? {} ), ...item };
									} );
									updateValue( merged );
								}
							},
							{
								label: t( 'Append' ),
								callback: () => {
									updateValue( [ ...value, ...parsed ] );
								}
							},
							{
								label: t( 'Default' ),
								callback: () => {
									e.target.value = paste;
									e.target.dispatchEvent( new Event( 'input', { bubbles: true } ) );
								}
							}
						] }
						onClose={ () => { setPasteModal( null ) } }
					/>
				);

			}
		}
	}

	const onCopy = props.onCopy ?? function( e ) {
		if ( false === clipboardAccessible ) {
			return;
		}

		suppress( e );

		const rows = valueRef.current?.map( item => {
			const row = { ...item };

			for ( const key in row ) {
				if ( row.hasOwnProperty( key ) && ! isScalar( row[ key ] ) ) {
					row[ key ] = JSON.stringify( row[ key ] );
				}
			}

			delete row._ref;

			return Object.values( row ).join( "\t" );
		} );

		updateClipboard( rows.join( "\n" ) );
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

		valueRef.current = stateValue;
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
		className: "align-middle table-transparent",
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

	let toolbar = <>{ onCopy && <CopyToClipboard onClick={ onCopy } /> }</>
	if ( containerContext.hasOwnProperty( 'setToolbar' ) ) {
		if ( containerContext.setToolbar( toolbar, props.id ) ) {
			toolbar = null;
		}
	}

	if ( sortable ) {
		return (
			<>
				{ pasteModal }
				{ toolbar }
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
			</>
		);
	}

	return (
		<>
			{ pasteModal }
			{ toolbar }
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
		</>
	);
}

Grid.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	taggable: bool,
	removable: bool,
	indexed: bool,
	columns: oneOfType( [ object, array ] ),
	nest: bool,
	//onPaste: func,
}
