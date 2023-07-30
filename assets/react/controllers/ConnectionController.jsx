import React, { useState } from 'react';
import { Stack, Tabs, Tab } from 'react-bootstrap';

import useWebservices from '../hooks/useWebservices';

import Fields from "../components/form/Fields";
import SelectWebservice from "../components/form/SelectWebservice";
import LoadingPlaceholder from '../components/partials/Loading/Placeholder';


export default function ConnectionController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const value = { ...props.value };

	// @todo Remove default parsing, this was for testing only.
	const parseValue = ( value ) => {
		if ( ! value.webservice ) {
			value.webservice = {};
		} else {
			if ( 'string' === typeof value.webservice ) {
				let webservice = {...value};
				webservice._class = webservice.webservice;
				delete webservice.webservice;

				value = {
					webservice: webservice,
				}
			}
		}
		return value;
	}

	const config = parseValue( value ?? {} );
	const [ selectedWebservice, setSelectedWebservice ] = useState( ( config.webservice._class ?? '' ) );
	const [ webserviceTypes ] = useWebservices( args.webserviceTypes, args.query ?? {} );

	if ( null === webserviceTypes ) {
		return <LoadingPlaceholder/>
	}

	const selectWebservice = ( type ) => {
		setSelectedWebservice( type );

		config.webservice._class = type;
		onChange( config );
	}

	const updateWebservice = ( webservice ) => {
		config.webservice = webservice;
		if ( selectedWebservice ) {
			config.webservice._class = selectedWebservice;
		}
		onChange( config );
	}

	const getWebserviceFields = () => {
		if ( webserviceTypes[ selectedWebservice ] ) {
			return {
				...webserviceTypes[ selectedWebservice ].auth ?? {},
				/*...webserviceTypes[ config.webservice ].fields ?? {},*/
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
							<Fields fields={ fields } value={ config.webservice } onChange={ updateWebservice } />
						</Tab>
					</Tabs>
				</Stack>
			}
		</Stack>
	);
}
