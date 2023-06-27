import React from 'react';
import  { Tabs, Tab, TabContent } from 'react-bootstrap';
import Tasks from "../components/fields/Tasks";
import Conditionals from "../components/fields/Conditionals";

export default function StepController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const value = { ...props.value };

	const updateTasks = ( tasks ) => {
		value.tasks = tasks;
		onChange( value );
	}

	const updateConditionals = ( conditionals ) => {
		value.conditionals = conditionals;
		onChange( value );
	}

	return (
		<Tabs className="mt-2">
			<Tab eventKey="tasks" title="Tasks">
				<TabContent className="p-1 border bg-body-tertiary">
					<div className="bg-body p-3">
						<Tasks
							{...args}
							value={ value.tasks ?? [] }
							onChange={ updateTasks }
						/>
					</div>
				</TabContent>
			</Tab>
			<Tab eventKey="conditionals" title="Conditionals">
				<TabContent className="p-1 border bg-body-tertiary">
					<div className="bg-body p-3">
						<Conditionals
							{...args}
							value={ value.conditionals ?? [] }
							onChange={ updateConditionals }
						/>
					</div>
				</TabContent>
			</Tab>
		</Tabs>
	);
}

