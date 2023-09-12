import React, { useState } from 'react';
import { Stack, Tab, Tabs } from 'react-bootstrap';

import useWebservices from '../../../hooks/useWebservices';

import Fields from '../../form/Fields';
import SelectWebservice from '../../form/SelectWebservice';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

export default function Authentication( props ) {

	const {
		onChange,
	} = props;

	const config = { ...props.value };
	const [ selectedWebservice, setSelectedWebservice ] = useState( ( config._class ?? '' ) );
	const [ webserviceTypes ] = useWebservices( props.webserviceTypes, props.query ?? {} );

	if ( null === webserviceTypes ) {
		return <LoadingPlaceholder/>
	}

	const selectWebservice = ( type ) => {
		setSelectedWebservice( type );

		config._class = type;
		onChange( config );
	}

	const updateWebservice = ( webservice ) => {
		if ( selectedWebservice ) {
			webservice._class = selectedWebservice;
		}
		onChange( webservice );
	}

	const getWebserviceFields = () => {
		if ( webserviceTypes[ selectedWebservice ] ) {
			return {
				...webserviceTypes[ selectedWebservice ].auth ?? {},
				/*...webserviceTypes[ selectedWebservice ].fields ?? {},*/
			}
		}
		return null;
	}

	const fields = getWebserviceFields();

	return (
		<Stack gap={2} className="mt-2">
			<SelectWebservice options={ webserviceTypes } onChange={ selectWebservice } value={ selectedWebservice }></SelectWebservice>
			{ fields &&
			  <Stack gap={0}>
				  <Tabs>
					  <Tab eventKey="auth" title="Authorization" className="p-3 border bg-body">
						  <Fields fields={ fields } value={ config } onChange={ updateWebservice } />
					  </Tab>
				  </Tabs>
			  </Stack>
			}
		</Stack>
	);
}
