import React, { useState } from 'react';
import { Alert, ButtonGroup, Button, Form } from "react-bootstrap";
import { BiCode, BiColumns } from "react-icons/bi";
import Columns from '../Columns';
import Code from '../Code';

export default function Dataset( props ) {

	const {
		value = [],
		columns = [],
		onChange,
	} = props;

	const [ dataset, setDataset ] = useState( value );
	const [ error, setError ] = useState( '' );
	const [ view, setView ] = useState( ( columns && columns.length ) ? 'columns' : 'code' );

	const updateDataset = ( newDataset ) => {
		setDataset( newDataset );
		onChange( newDataset );
	}

	const updateInput = ( value ) => {
		try {
			const newDataset = JSON.parse( value );
			updateDataset( newDataset );
			setError( '' );
		} catch ( e ) {
			updateDataset( value );
			setError( 'Cannot parse JSON' );
		}
	}

	let control = [];

	switch ( view ) {
		case 'code':
			control = (
				<Code
					height="60vh"
					value={ ( 'object' === typeof dataset ) ? JSON.stringify( dataset, null, 4 ) : dataset }
					onChange={ updateInput }
				/>
			);
			break;
		case 'columns':
			control = (
				<Columns
					value={ dataset }
					onChange={ updateDataset }
					columns={ columns }
				/>
			);
			break;
	}

	return (
		<div className="p-1 border bg-body-tertiary">
			<div className="bg-body p-3">
				{ error &&
					<Alert variant="warning">{ error }</Alert>
				}

				{ columns &&
				    <ButtonGroup>
						<Button className="icon-link" variant={ ( 'code' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'code' ) } }><BiCode /></Button>
						<Button className="icon-link" variant={ ( 'columns' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'columns' ) } }><BiColumns /></Button>
				    </ButtonGroup>
				}

				{ control }
			</div>
		</div>
	);
}
