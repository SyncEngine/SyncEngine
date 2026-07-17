import React, { useCallback, useContext, useEffect, useState } from 'react';
import { any, array, bool, func, object, oneOfType, string } from 'prop-types';
import { useTranslation } from 'react-i18next';
import { Alert, ButtonGroup, ButtonToolbar, Dropdown, DropdownButton, InputGroup, Stack } from 'react-bootstrap';

import Grid from '../Grid';
import Code from '../Code';
import Group from '../../form/Fields/Group';
import Button from '../../partials/Button';

import { fromFormat, getFormats, toFormat } from '../../../utils/format';
import { isArray, isEmpty, isEqual, isFieldEditable, isObject } from '../../../utils/conditions';
import Icon from '../../partials/Icon';
import useCodecs from '../../../hooks/useCodecs';
import Codec from '../Codec';
import useFieldValue from '../../../hooks/useFieldValue';
import { FieldsContext } from '../../../context/FieldsContext';

function getFormatTypeFromConfig( formatConfig ) {
	if ( formatConfig ) {
		if ( 'object' === typeof formatConfig ) {
			return formatConfig._class?.toLowerCase();
		}
		return formatConfig;
	}
	return '';
}

function parseCodecQuery( query, choices ) {
	if ( isEmpty( choices ) || 'object' !== typeof choices ) {
		return query;
	}

	if ( ! isObject( query ) ) {
		query = {};
	}

	query = {
		filter: {
			_class: Array.isArray( choices ) ? choices : Object.keys( choices ),
		}
	}

	return query;
}

export default function Params( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );
	const fieldsContext = useContext( FieldsContext );

	const {
		customizable,
		columns = {
			key: t('Key'),
			value: t('Value'),
		},
		onChange,
		query = {}
	} = props;

	const isList = 1 === Object.values( columns ).length;

	const [ error, setError ] = useState( '' );
	const [ params, setParams ] = useState( props.value ?? [] );

	const supportedFormats = getFormats();
	const formats = ( props.formats ) ? props.formats.choices ?? props.formats : [];
	const [ formatFieldValue ] = useFieldValue( props.formats?.name ?? null, fieldsContext );

	// Only fetch codec models if not static props.format key is set.
	const [ codecTypes, codecCallbacks, loading ] = useCodecs( props.codecTypes ?? {}, ( ! props.format && props.formats ) ? parseCodecQuery( query, formats ) : false );

	useEffect( () => {
		if ( ! isEqual( props.value, params ) ) {
			setParams( props.value );
		}
	}, [ props.value ] );

	const getView = useCallback( ( format ) => {
		if ( view && ! isEmpty( params ) ) {
			return view;
		}
		if ( customizable ) {
			if ( 'object' === typeof params ) {
				return 'grid';
			}
			if ( ! isEmpty( formats ) && ! format ) {
				return 'code';
			}
			if ( format && ! supportedFormats.hasOwnProperty( format ) ) {
				return 'code';
			}
		}
		return ( ! customizable || ! isEmpty( columns ) ) ? 'grid' : 'code';
	}, [ columns, supportedFormats, view, params ] );

	const format = getFormatTypeFromConfig( props.format ?? formatFieldValue );
	const [ view, setView ] = useState( getView( format ) );

	const updateParams = ( newParams ) => {

		setError( '' );
		if ( 'string' === typeof newParams && format && supportedFormats.hasOwnProperty( format ) ) {
			try {
				newParams = fromFormat( newParams, format );
			} catch ( e ) {
				setParams( newParams );
				setError( t('Cannot parse value' ) );
				return;
			}
		}

		if ( isList ) {
			newParams = Object.values( newParams );
		}

		setParams( newParams );
		onChange( newParams );
	}

	const updateColumns = ( newParams ) => {
		let paramsObject = {};
		for ( const index in newParams ) {
			paramsObject[ ( isList ) ? index : newParams[ index ].key ] = newParams[ index ].value ?? '';
		}

		updateParams( paramsObject );
	}

	const updateInput = ( value ) => {
		updateParams( value );
	}

	const updateFormat = ( newFormat ) => {
		setError( '' );
		if ( ! props.format && props.formats && props.formats.hasOwnProperty( 'name' ) ) {
			onChange( newFormat, props.formats.name );
		}
	}

	const updateView = ( newView ) => {
		setView( newView );
	}

	let control = [];
	switch ( view ) {
		case 'grid':
			let value = params;
			let columnFormatted = [];
			if ( 'string' === typeof value ) {
				try {
					value = fromFormat( params, format );
				} catch ( e ) {
					setError( e.message );
				}
			}
			for ( const key in value ) {
				if ( isArray( params ) || value.hasOwnProperty( key ) ) {
					columnFormatted.push( { key: key, value: value[ key ] } );
				}
			}
			control = (
				<Grid
					{ ...props }
					columns={ columns }
					value={ columnFormatted }
					onChange={ updateColumns }
					editable={ editable }
					taggable={ props.taggable }
					sortable={ props.sortable ?? editable }
					disabled={ props.disabled }
				/>
			);
			break;
		case 'code':
			let text = params;
			if ( 'string' !== typeof text ) {
				// @todo Use Codec models.
				if ( ! error && text && format && supportedFormats.hasOwnProperty( format.toLowerCase() ) ) {
					try {
						text = toFormat( params, format.toLowerCase(), ( 'json' === format.toLowerCase() ? [ null, 2 ] : undefined ) );
					} catch ( e ) {
						setError( e.message );
					}
				}
			}
			control = (
				<Code
					height="200px"
					value={ String( text ) }
					onChange={ updateInput }
					editable={ editable }
					taggable={ props.taggable }
					disabled={ props.disabled }
				/>
			);
			break;
	}

	const toolbarLeft = ( customizable && columns ) && (
		<ButtonGroup key={ 'view' }>
			<Button variant="secondary" outline={ 'code' !== view } onClick={ () => { setView( 'code' ) } }><Icon icon="code" /></Button>
			{ ( ! format || supportedFormats.hasOwnProperty( format.toLowerCase() ) ) && // @todo Use Codec models.
				<Button variant="secondary" outline={ 'grid' !== view } onClick={ () => { setView( 'grid' ) } }><Icon icon="grid" /></Button>
			}
		</ButtonGroup>
	)

	const toolbarRight = ( ! props.format && ! loading && editable && ! isEmpty( formats ) ) && (
		<InputGroup>
			<Codec
				compact
				prefix={ t( 'Format' ) }
				label={ t( 'Format' ) }
				codecTypes={ codecTypes }
				direction={ props.formats.direction }
				value={ formatFieldValue }
				onChange={ updateFormat }
			/>
		</InputGroup>
	)

	const toolbar = ( toolbarLeft || toolbarRight ) && (
		<ButtonToolbar className="justify-content-between">
			{ toolbarLeft }
			{ toolbarRight }
		</ButtonToolbar>
	);

	return (
		<Stack gap={ 2 }>
			{ error && <Alert variant="warning">{ error }</Alert> }
			{ toolbar }
			{ control }
			{ ( editable && props.formats && props.formats.fields && supportedFormats.hasOwnProperty( format ) ) &&
				<Group fields={ props.formats.fields } updateField={ onChange } />
			}
		</Stack>
	);
}

Params.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	taggable: bool,
	sortable: bool,
	customizable: bool,
	columns: oneOfType( [ object, array ] ),
	formats: oneOfType( [ object, array ] ),
	format: oneOfType( [ string, object ] ), // Sets a static format type for this field.
}
