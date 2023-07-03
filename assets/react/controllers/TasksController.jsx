import React, { useState } from 'react';
import Tasks from "../components/fields/Tasks";

export default function TasksController( props ) {

	const {
		args = {},
		value = [],
		onChange,
	} = props;

	const {
		taskTypes = window.app.types.tasks ?? {},
	} = args;

	return (
		<Tasks taskTypes={ taskTypes } value={ value } onChange={ onChange } />
	);
}
