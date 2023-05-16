import React from 'react';
import Stack from 'react-bootstrap/Stack';
import FieldsController from "./FieldsController";

export default function ConnectionController( props ) {
	const {
		args,
		onChange,
	} = props;

	return (
		<Stack gap={ 2 } className="mt-2">
			<FieldsController {...args} onChange={ onChange } />
		</Stack>
	);
}
