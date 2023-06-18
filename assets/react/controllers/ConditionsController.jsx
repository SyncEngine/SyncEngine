import React from 'react';
import Conditions from "../components/fields/Conditions";
import Stack from "react-bootstrap/Stack";

export default function ConditionsController( props ) {

	const {
		args,
		value = [],
		onChange,
	} = props;

	const {
		conditionTypes = {}
	} = args;

	return (
		<Stack gap={2}>
			<Conditions value={ value } conditionTypes={ conditionTypes } onChange={ onChange } />
		</Stack>
	);
}
