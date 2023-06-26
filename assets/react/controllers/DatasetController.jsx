import React from 'react';
import Dataset from "../components/fields/Dataset";
import FieldGroup from "../components/form/FieldGroup";

export default function DatasetController( props ) {

	const {
		args = {},
		value = {},
		onChange,
	} = props;

	const {
		prop,
		fields,
	} = args;

	switch ( prop ) {
		case 'data':
			return ( <Dataset value={ value } onChange={ onChange } columns={ value.columns ?? [] }></Dataset> );
		case 'config':
			// Todo
			return ( <FieldGroup fields={ fields } value={ value } onChange={ onChange } /> )
	}
}
