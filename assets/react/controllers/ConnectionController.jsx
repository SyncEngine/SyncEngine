import React, { useEffect, useState } from 'react';
import Stack from 'react-bootstrap/Stack';
import FieldsController from "./FieldsController";
import WebserviceSelector from "../components/form/WebserviceSelector";
import { Tab, TabContent, Tabs } from "react-bootstrap";

export default function ConnectionController( props ) {

	const {
		args = {},
		value = [],
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
				/*...webserviceTypes[ config.webservice ].fields ?? {},*/
			}
		}
		return null;
	}

	const fields = getWebserviceFields();

	return (
		<Stack gap={2} className="mt-2">
			<WebserviceSelector options={ webserviceTypes } onChange={ selectWebservice } value={ config.webservice ?? '' }></WebserviceSelector>
			{ fields &&
			  <Stack gap={0}>
				  <Tabs>
					  <Tab eventKey="auth" title="Authorization">
						  <TabContent className="p-2 border bg-body-tertiary">
							  <FieldsController fields={ fields } value={ value } onChange={ updateConfig } />
						  </TabContent>
					  </Tab>
				  </Tabs>
			  </Stack>
			}
		</Stack>
	);
}
