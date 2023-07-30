import React from 'react';
import { Alert, Stack } from 'react-bootstrap';
import Fields from "../../form/Fields";
import useWebservices from '../../../hooks/useWebservices';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

export default function Webservice( props ) {

	const [ webservices ] = useWebservices( null, {} );
	const webservice = ( webservices && props.webservice ) ? ( webservices[ props.webservice._class ?? props.webservice ] ?? null ) : null;

	if ( ! webservices || ! props.webservice ) {
		return <LoadingPlaceholder/>
	}

	if ( ! webservice ) {
		return <Alert variant="danger">Webservice not found</Alert>
	}

	if ( ! webservice.hasOwnProperty( 'fields' ) ) {
		return <Alert variant="warning">Webservice has no fields</Alert>
	}

	return (
		<Stack gap={ 2 }>
			<Fields {...props} fields={ webservice.fields } />
		</Stack>
	);
}
