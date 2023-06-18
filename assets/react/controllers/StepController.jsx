import React from 'react';
import  { Tabs, Tab, TabContent } from 'react-bootstrap';
import ConditionsController from "./ConditionsController";
import Tasks from "../components/fields/Tasks";
import Conditions from "../components/fields/Conditions";

export default function StepController( props ) {

	const {
		args = {},
		value = {},
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
					<Tasks
						{...args}
						value={ value.tasks ?? [] }
						onChange={ updateTasks }
					/>
				</TabContent>
			</Tab>
			<Tab eventKey="conditions" title="Conditions">
				<TabContent className="p-2 border bg-body-tertiary">
					<Conditions
						{...args}
						value={ value.conditions ?? [] }
						onChange={ updateConditions }
					/>
				</TabContent>
			</Tab>
		</Tabs>
	);
}
