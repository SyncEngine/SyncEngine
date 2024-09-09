import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Alert, Button, ButtonGroup, ButtonToolbar, Dropdown, DropdownButton, Stack } from 'react-bootstrap';

import Grid from '../Grid';
import Code from '../Code';
import Group from '../../form/Fields/Group';

import { fromFormat, getFormats, toFormat } from '../../../utils/format';
import { objectToMappable } from '../../../utils/data';
import { isEmpty, isFieldEditable, isObject } from '../../../utils/conditions';
import useFieldValues from '../../../hooks/useFieldValues';
import Icon from '../../partials/Icon';

function getFormat( props, values ) {
	if ( props.format ) {
		return props.format;
	}
	if ( ! isEmpty( values ) && props.formats && props.formats.hasOwnProperty( 'name' ) ) {
		return values[ props.formats.name ] ?? '';
	}
	return '';
}

function getFormatLabel( formats, value ) {
	if ( ! isObject( formats ) || ! formats.hasOwnProperty( value ) ) {
		return null;
	}

	const format = formats[ value ];

	return isObject( format ) ? format.label || format.name || format.value : format;
}

export default function Params( props ) {
	const { t } = useTranslation();
	const [ values ] = useFieldValues( props.values );
	const editable = isFieldEditable( props );

	const {
		manual,
		columns = {
			key: t('Key'),
			value: t('Value'),
		},
		onChange,
	} = props;

	const [ error, setError ] = useState( '' );
	const [ params, setParams ] = useState( props.value ?? [] );

	const supportedFormats = getFormats();
	const formats = ( props.formats ) ? props.formats.choices ?? props.formats : [];

	const getView = useCallback( ( format ) => {
		if ( view && ! isEmpty( params ) ) {
			return view;
		}
		if ( manual ) {
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
		return ( ! manual || ! isEmpty( columns ) ) ? 'grid' : 'code';
	}, [ columns, supportedFormats, view, params ] );

	const [ format, setFormat ] = useState( getFormat( props ) );
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

		setParams( newParams );
		onChange( newParams );
	}

	const updateColumns = ( newParams ) => {
		let paramsObject = {};
		for ( const index in newParams ) {
			paramsObject[ newParams[ index ].key ] = newParams[ index ].value;
		}

		updateParams( paramsObject );
	}

	const updateInput = ( value ) => {
		updateParams( value );
	}

	const updateFormat = ( newFormat ) => {
		setError( '' );
		setFormat( newFormat );
		setView( getView( newFormat ) );

		if ( props.formats && props.formats.name ) {
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
				if ( value.hasOwnProperty( key ) ) {
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
				if ( ! error && text && format && supportedFormats.hasOwnProperty( format ) ) {
					try {
						text = toFormat( params, format );
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

	const toolbarLeft = ( manual && columns ) && (
		<ButtonGroup key={ 'view' }>
			<Button variant={ ( 'code' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'code' ) } }><Icon icon="code" /></Button>
			{ ( ! format || supportedFormats.hasOwnProperty( format ) ) &&
				<Button variant={ ( 'grid' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'grid' ) } }><Icon icon="grid" /></Button>
			}
		</ButtonGroup>
	)

	const toolbarRight = ( editable && ! isEmpty( formats ) ) && (
		<ButtonGroup key={ 'format' }>
			<DropdownButton
				title={ t( 'Format' ) + ( format ? ': ' + getFormatLabel( formats, format ) : '' ) }
				variant={ format ? 'secondary' : 'outline-secondary' }
			>
				{
					objectToMappable( formats, 'value', 'label' ).map( ( formatOption ) => {
						return (
							<Dropdown.Item
								key={ formatOption.value }
								active={ formatOption.value === format }
								onClick={ () => updateFormat( formatOption.value ) }
							>
								{ formatOption.label }
							</Dropdown.Item>
						)
					} )
				}
			</DropdownButton>
		</ButtonGroup>
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
