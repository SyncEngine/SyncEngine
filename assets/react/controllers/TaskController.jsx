import React from 'react';
import Task from "../components/fields/Tasks/Task";

export default function TaskController( props ) {

	const {
		args = {},
		value = {},
		onChange,
	} = props;

	return (
		<Task {...args} value={ value } onChange={ onChange } />
	);
}
