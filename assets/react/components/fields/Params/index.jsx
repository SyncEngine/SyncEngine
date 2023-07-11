import React, { useCallback, useState } from 'react';
import { Alert, Button, ButtonGroup, ButtonToolbar, Form, Stack } from 'react-bootstrap';

import Columns from '../Columns';
import { BiCode, BiColumns } from 'react-icons/bi';
import { toFormat, fromFormat, getFormats } from '../../../utils/format';
import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';

import Group from '../../form/Fields/Group';
import Code from '../Code';

export default function Params( props ) {
	const {
		manual,
		columns = {
			key: 'Key',
			value: 'Value',
		},
		onChange,
	} = props;

	const [ error, setError ] = useState( '' );
	const [ params, setParams ] = useState( props.value ?? [] );

	const supportedFormats = getFormats();
	const formats = ( props.formats ) ? props.formats.choices ?? props.formats : [];

	const getFormat = useCallback( ( props ) => {
		if ( props.format ) {
			return props.format;
		}
		if ( props.values && props.fields && props.fields.hasOwnProperty( 'name' ) ) {
			return props.values[ props.fields.name ] ?? '';
		}
		return '';
	}, [] );

	const getView = useCallback( ( format ) => {
		if ( view && ! isEmpty( params ) ) {
			return view;
		}
		if ( manual ) {
			if ( formats && ! format ) {
				return 'code';
			}
			if ( format && ! supportedFormats.hasOwnProperty( format ) ) {
				return 'code';
			}
		}
		return ( ! manual || ! isEmpty( columns ) ) ? 'columns' : 'code';
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
				setError( 'Cannot parse value' );
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
		case 'columns':
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
			control = <Columns { ...props } columns={ columns } value={ columnFormatted } onChange={ updateColumns } />;
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
			control = <Code height="200px" value={ String( text ) } onChange={ updateInput } />;
			break;
	}

	return (
		<Stack gap={ 2 }>

			{ error &&
				<Alert variant="warning">{ error }</Alert>
			}

			{ ( manual || formats ) &&
				<ButtonToolbar className="justify-content-between">
					{ ( manual && columns ) &&
						<ButtonGroup key={ 'view' }>
							<Button className="icon-link" variant={ ( 'code' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'code' ) } }><BiCode /></Button>
							{ ( ! format || supportedFormats.hasOwnProperty( format ) ) &&
								<Button className="icon-link" variant={ ( 'columns' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'columns' ) } }><BiColumns /></Button>
							}
						</ButtonGroup>
					}
					{ formats &&
						<ButtonGroup key={ 'format' }>
							{
								objectToMappable( formats, 'value', 'label' ).map( ( formatOption ) => {
									return (
										<Button
											key={ formatOption.value }
											variant={ ( formatOption.value === format ) ? 'secondary' : 'outline-secondary' }
											onClick={ () => updateFormat( formatOption.value ) }
										>
											{ formatOption.label }
										</Button>
									)
								} )
							}
						</ButtonGroup>
					}
				</ButtonToolbar>
			}

			{ control }

			{ ( props.formats && props.formats.fields && supportedFormats.hasOwnProperty( format ) ) &&
				<Group fields={ props.formats.fields } values={ props.values ?? {} } updateField={ onChange } />
			}
		</Stack>
	);
}
