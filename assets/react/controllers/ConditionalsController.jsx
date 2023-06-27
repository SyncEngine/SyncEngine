import React from 'react';
import Conditionals from "../components/fields/Conditionals";

export default function ConditionalsController( props ) {

	const {
		args = {},
		value = [],
		onChange,
	} = props;

	const {
		conditionTypes = {}
	} = args;

	return (
		<Conditionals value={ value } conditionTypes={ conditionTypes } onChange={ onChange } />
	);
}
