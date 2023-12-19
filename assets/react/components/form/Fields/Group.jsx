import React from 'react';
import { Alert, Col, Stack } from 'react-bootstrap';

import FieldsItem from './Item';
import { objectToMappable } from "../../../utils/data";
import { createRefId } from '../../../utils/globals';
import useConditions from '../../../hooks/useConditions';

export default function Group( props ) {
	const validate = useConditions();

	const {
		fields,
		values,
		updateField,
		inline = false,
	} = props;

	if ( ! fields ) {
		return <Alert variant="warning">No fields found.</Alert>
	}

	let elements = objectToMappable( fields, 'name' ).filter( ( field => {
		return ! (
			field.hasOwnProperty( 'conditions' ) && ! validate( field.conditions, values )
		);
	} ) ).map( ( field, index ) => {

		field.id = field.id ?? createRefId();

		const item = <FieldsItem key={ field.id } updateField={ updateField } field={ field } values={ values } />;

		if ( 'fixed' === inline ) {
			return <Col key={ field.id + '_col' } className={ field.col }>{ item }</Col>
		}
		return item;

	} ).filter( ( elem ) => React.isValidElement( elem ) );

	if ( ! elements.length ) {
		return;
	}

	return (
		<Stack gap={ 2 } direction={ inline ? 'horizontal' : 'vertical' }>
			{ elements }
		</Stack>
	);
}
