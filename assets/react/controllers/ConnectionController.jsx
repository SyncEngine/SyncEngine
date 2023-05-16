import React from 'react';
import FieldsController from "./FieldsController";

export default function ConnectionController( props ) {
	const {
		args,
		onChange,
	} = props;

	return (
		<FieldsController {...args} onChange={ onChange } />
	);
}
