import React, { useState } from 'react';
import { Alert, ButtonGroup, Button } from "react-bootstrap";
import Columns from '../Columns';
import Code from '../Code';
import Mapper from '../Mapper';
import Repeater from '../Repeater';
import { objectToMappable } from '../../../utils/data';

export default function Dataset( props ) {

	const {
		value = [],
		type,
		onChange,
		datasetConfig = {},
		columns = datasetConfig.columns ?? [],
	} = props;

	const [ dataset, setDataset ] = useState( value );
	const [ error, setError ] = useState( '' );
	const [ view, setView ] = useState( ( ( Array.isArray( columns ) && columns.length ) || 'mapper' === type ) ? 'columns' : 'code' );

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
		case 'columns':
			if ( 'mapper' === type ) {
				control = (
					<Mapper
						values={ datasetConfig.mapper ?? {} }
						taggable={ props.taggable }
						value={ structuredClone( dataset ) }
						onChange={ updateDataset }
					/>
				);
			} else if ( 'fields' === type ) {
				if ( datasetConfig.fields && 'advanced' === datasetConfig.fields.configuration ) {
					control = (
						<Repeater
							fields={ datasetConfig.fields.fieldset ?? {} }
							value={ structuredClone( dataset ) }
							onChange={ updateDataset }
						/>
					);
				} else {
					control = (
						<Columns
							taggable={ props.taggable }
							value={ objectToMappable( structuredClone( dataset ), 'key', 'label' ) }
							onChange={ updateDataset }
							columns={ {
								key: 'Field Key',
								label: 'Field Label',
							} }
						/>
					);
				}
			} else {
				control = (
					<Columns
						taggable={ props.taggable }
						value={ structuredClone( dataset ) }
						onChange={ updateDataset }
						columns={ columns }
					/>
				);
			}
			break;
		default:
			control = (
				<Code
					height="60vh"
					value={ ( 'object' === typeof dataset ) ? JSON.stringify( dataset, null, 4 ) : dataset }
					onChange={ updateInput }
					taggable={ props.taggable }
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
