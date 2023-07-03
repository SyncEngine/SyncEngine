import React, { useState } from 'react';
import { Stack, Tab, TabContent, Tabs } from "react-bootstrap";

import FieldGroup from "../components/form/FieldGroup";
import SelectWebservice from "../components/form/SelectWebservice";


export default function ConnectionController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const value = { ...props.value };

	const {
		webserviceTypes = window.app.types.webservices ?? {},
	} = args;

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

	const selectWebservice = ( type ) => {
		setSelectedWebservice( type );

		config.webservice._class = type;
		onChange( config );
	}

	const updateWebservice = ( webservice ) => {
		config.webservice = webservice;
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
						<Tab eventKey="auth" title="Authorization">
							<TabContent className="p-1 border bg-body-tertiary">
								<div className="bg-body p-3">
									<FieldGroup fields={ fields } value={ config.webservice } onChange={ updateWebservice } />
								</div>
							</TabContent>
						</Tab>
					</Tabs>
				</Stack>
			}
		</Stack>
	);
}
