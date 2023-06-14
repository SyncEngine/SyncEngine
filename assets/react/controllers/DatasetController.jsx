import React, { useEffect, useState } from 'react';
import Stack from 'react-bootstrap/Stack';

export default function DatasetController( props ) {

	const {
		value = [],
		args,
		onChange,
	} = props;

	const {
	} = args;

	const [ config, setConfig ] = useState( value );

	/**
	 * Update parent value.
	 * This needs to be an effect since the state update is async.
	 */
	useEffect( () => {
		onChange( config );
	}, [ config ] );

	return (
		<Stack gap={2} className="mt-2">

		</Stack>
	);
}
