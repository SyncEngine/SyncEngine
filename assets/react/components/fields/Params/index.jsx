import React, { useState } from 'react';
import { Alert, Button, ButtonGroup, ButtonToolbar, Form, Stack } from 'react-bootstrap';

import ParamsColumns from './Columns';
import { BiCode, BiColumns } from 'react-icons/bi';
import { toFormat, fromFormat, getFormats } from '../../../utils/format';
import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';

export default function Params( props ) {
	const {
		manual,
		columns = {
			key: 'Key',
			value: 'Value',
		},
		onChange,
	} = props;

	const formats = ( props.formats ) ? getFormats( props.formats ) : [];

	const [ params, setParams ] = useState( props.value ?? {} );
	const [ view, setView ] = useState( ( ! isEmpty( columns ) ) ? 'columns' : 'code' );
	const [ error, setError ] = useState( '' );
	const [ format, setFormat ] = useState( props.format ?? 'json' );

	const updateParams = ( newParams ) => {
		setParams( newParams );
		onChange( newParams );
	}

	const updateInput = ( event ) => {
		let newParams = event.target.value;
		if ( format ) {
			try {
				newParams = fromFormat( event.target.value, format );
			} catch ( e ) {
				setError( 'Cannot parse value' );
			}
		}
		updateParams( newParams );
	}

	const updateFormat = ( newFormat ) => {
		setFormat( newFormat );
	}

	console.log( params );

	let control = [];
	switch ( view ) {
		case 'columns':
			control = <ParamsColumns { ...props } value={ objectToMappable( params, 'value', 'key' ) } onChange={ updateParams } />;
			break;
		case 'code':
			let formatted = '';
			if ( params && ! error ) {
				try {
					formatted = toFormat( params, format );
				} catch ( e ) {
					setError( e.message )
				}
			}
			control = <Form.Control as="textarea" rows={ 5 } value={ formatted } onChange={ updateInput } />;
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
							<Button className="icon-link" variant={ ( 'columns' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'columns' ) } }><BiColumns /></Button>
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
		</Stack>
	);
}
