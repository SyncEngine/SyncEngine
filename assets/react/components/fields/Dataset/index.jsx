import React, { useState } from 'react';
import Params from "../Params";
import Form from "react-bootstrap/Form";
import { Alert } from "react-bootstrap";

export default function Dataset( props ) {

	const {
		value = {},
		columns = [],
		onChange,
	} = props;

	const [ dataset, setDataset ] = useState( value );
	const [ error, setError ] = useState( '' );

	const code = ( ! columns || ! columns.length );

	const updateDataset = ( newDataset ) => {
		setDataset( newDataset );
		onChange( newDataset );
	}

	const updateInput = ( event ) => {
		try {
			const newDataset = JSON.parse( event.target.value );
			updateDataset( newDataset );
			setError( '' );
		} catch ( e ) {
			updateDataset( event.target.value  );
			setError( 'Cannot parse JSON' );
		}
	}

	return (
		<div className="p-1 border bg-body-tertiary">
			<div className="bg-body p-3">
				{ error &&
					<Alert variant="warning">{ error }</Alert>
				}
				{ ! code &&
				    <Params
					    value={ dataset }
					    onChange={ updateDataset }
					    columns={ columns }
				    />
				}
				{ code &&
					<Form.Control as="textarea" rows={ 3 } value={ ( 'object' === typeof dataset ) ? JSON.stringify( dataset, null, 4 ) : dataset } onChange={ updateInput } />
				}
			</div>
		</div>
	);
}
