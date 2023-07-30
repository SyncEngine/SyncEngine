import React from 'react';
import { Alert, Stack } from "react-bootstrap";
import Fields from "../../form/Fields";
import useWebservices from '../../../hooks/useWebservices';

export default function Webservice( props ) {

	const [ webservices, callbacks ] = useWebservices();
	const webservice = callbacks.get( props.webservice ? ( props.webservice._class ?? props.webservice ) : '' );

	if ( ! webservice ) {
		return (
			<Alert variant="danger">Webservice not found</Alert>
		)
	}

	if ( ! webservice.hasOwnProperty( 'fields' ) ) {
		return (
			<Alert variant="warning">Task has no fields</Alert>
		)
	}

	return (
		<Stack gap={ 2 }>
			<Fields {...props} fields={ webservice.fields } />
		</Stack>
	);
}
