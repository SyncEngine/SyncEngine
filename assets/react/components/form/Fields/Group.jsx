import React from 'react';
import { Alert, Col, Stack } from 'react-bootstrap';

import FieldsItem from './Item';
import { objectToMappable } from '../../../utils/data';
import { createRefId } from '../../../utils/globals';
import useConditions from '../../../hooks/useConditions';

export default function Group( props ) {
	//const values = useContext( FieldsContext ).values ?? {}
	const validate = useConditions();

	const {
		fields,
		updateField,
		inline = false,
		editable = true,
	} = props;

	if ( ! fields ) {
		return <Alert variant="warning">No fields found.</Alert>
	}

	let elements = objectToMappable( fields, 'name' ).map( ( field, index ) => {

		field.id = field.id ?? createRefId();

		let item = <FieldsItem key={ field.id } updateField={ updateField } field={ field } editable={ editable } />;

		if ( 'fixed' === inline ) {
			item = <Col className={ field.col ?? props.col }>{ item }</Col>
		}
		return <ConditionalField key={ field.id + index } field={ field }>{ item }</ConditionalField>;

	} );

	if ( ! elements.length ) {
		return;
	}

	return (
		<Stack gap={ props.gap || 2 } direction={ inline ? 'horizontal' : 'vertical' }>
			{ elements }
		</Stack>
	);
}

const ConditionalField = ( props ) => {
	const {
		field
	} = props;

	const [ valid ] = useConditions( field.conditions );

	if ( ! valid ) {
		return;
	}

	return props.children;
}
