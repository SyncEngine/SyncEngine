import React, { useEffect, useState } from 'react';
import Stack from 'react-bootstrap/Stack';
import FieldsController from "./FieldsController";
import WebserviceSelector from "../components/WebserviceSelector";

export default function ConnectionController( props ) {

	const {
		value: value = [],
		args,
		onChange,
	} = props;

	const {
		webserviceTypes: webserviceTypes = {},
	} = args;

	const [ config, setConfig ] = useState( value );

	/**
	 * Update parent value.
	 * This needs to be an effect since the state update is async.
	 */
	useEffect( () => {
		onChange( config );
	}, [ config ] );

	const selectWebservice = ( value ) => {
		let newConfig = { ...config };
		newConfig.webservice = value;
		setConfig( newConfig );
	}

	const updateConfig = ( value ) => {
		setConfig( { ...config, ...value } );
	}

	const getWebserviceFields = () => {
		if ( config.webservice && webserviceTypes[ config.webservice ] ) {
			return {
				...webserviceTypes[ config.webservice ].auth ?? {},
				...webserviceTypes[ config.webservice ].fields ?? {},
			}
		}
		return null;
	}

	const fields = getWebserviceFields();

	return (
		<Stack gap={2}>
			<WebserviceSelector options={ webserviceTypes } onChange={ selectWebservice } value={ config.webservice ?? '' }></WebserviceSelector>
			{ fields &&
			  <FieldsController fields={ fields } value={ value } onChange={ updateConfig } />
			}
		</Stack>
	);
}
