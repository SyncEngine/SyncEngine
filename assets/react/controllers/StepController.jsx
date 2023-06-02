import React from 'react';
import  { Tabs, Tab, TabContent } from 'react-bootstrap';
import TasksController from "./TasksController";
import ConditionsController from "./ConditionsController";

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

	const updateConditions = ( conditions ) => {
		value.conditions = conditions;
		onChange( value );
	}

	return (
		<Tabs className="mt-2">
			<Tab eventKey="tasks" title="Tasks">
				<TabContent className="p-2 border bg-body-tertiary">
					<TasksController
						{...args}
						value={ value.tasks ?? [] }
						onChange={ updateTasks }
					/>
				</TabContent>
			</Tab>
			<Tab eventKey="conditions" title="Conditions">
				<TabContent className="p-2 border bg-body-tertiary">
					<ConditionsController
						{...args}
						value={ value.conditions ?? [] }
						onChange={ updateConditions }
					/>
				</TabContent>
			</Tab>
		</Tabs>
	);
}
