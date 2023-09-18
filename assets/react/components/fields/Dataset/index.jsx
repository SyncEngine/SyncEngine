import React, { useState } from 'react';
import { Alert, ButtonGroup, Button, Form } from "react-bootstrap";
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
					taggable={ props.taggable }
				/>
			);
			break;
		case 'columns':
			control = (
				<Columns
					taggable={ props.taggable }
					value={ dataset }
					onChange={ updateDataset }
					columns={ columns }
				/>
			);
			break;
	}

	return (
		<div className="p-3 border bg-body">
			{ error &&
				<Alert variant="warning">{ error }</Alert>
			}

			{ columns &&
			    <ButtonGroup>
					<Button variant={ ( 'code' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'code' ) } }><span className="bi bi-code" /></Button>
					<Button variant={ ( 'columns' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'columns' ) } }><span className="bi bi-input-cursor" /></Button>
			    </ButtonGroup>
			}

			{ control }
		</div>
	);
}
