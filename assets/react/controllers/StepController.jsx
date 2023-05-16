import React from 'react';
import  { Tabs, Tab, TabContent } from 'react-bootstrap';
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
			className="mt-2"
		>
			<Tab eventKey="tasks" title="Tasks">
				<TabContent className="p-2 border bg-light">
					<TasksController
						{...args}
						value={ value.tasks ?? [] }
						onChange={ updateTasks }
					/>
				</TabContent>
			</Tab>
		</Tabs>
	);
}
