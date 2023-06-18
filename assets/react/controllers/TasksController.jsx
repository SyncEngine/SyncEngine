import React, { useState } from 'react';
import Tasks from "../components/fields/Tasks";

export default function TasksController( props ) {

	const {
		args = {},
		value = [],
		onChange,
	} = props;

	const {
		taskTypes,
	} = args;

	return (
		<Tasks taskTypes={ taskTypes } value={ value } onChange={ onChange } />
	);
}
