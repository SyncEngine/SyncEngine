import React from 'react';
import  { Stack, Tab, Tabs } from 'react-bootstrap';
import TasksController from "./TasksController";

export default function StepController( props ) {
	const {
		args: args = {},
		value: value = {},
		onChange,
	} = props;

	const updateTasks = ( tasks ) => {
		value.tasks = tasks;
		onChange( value );
	}

	return (
		<Tabs
			className="my-2"
		>
			<Tab eventKey="tasks" title="Tasks">
				<TasksController
					{...args}
					value={ value.tasks ?? [] }
					onChange={ updateTasks }
				/>
			</Tab>
		</Tabs>
	);
}
