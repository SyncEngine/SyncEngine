import React from 'react';
import Conditions from "../components/fields/Conditions";

export default function ConditionsController( props ) {

	const {
		args = {},
		value,
		onChange,
	} = props;

	const {
		conditionTypes = {}
	} = args;

	return (
		<Conditions value={ value } conditionTypes={ conditionTypes } onChange={ onChange } />
	);
}
