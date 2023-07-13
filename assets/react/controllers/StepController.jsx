import React from 'react';
import { Tabs, Tab, TabContent, Card } from 'react-bootstrap';
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
				<TabContent style={ { marginTop: '-1px' } }>
					<Tasks
						{...args}
						value={ value.tasks ?? [] }
						onChange={ updateTasks }
					/>
				</TabContent>
			</Tab>
			<Tab eventKey="conditionals" title="Conditionals">
				<TabContent className="bg-body p-3 border border-top-0">
					<Conditionals
						{...args}
						value={ value.conditionals ?? [] }
						onChange={ updateConditionals }
					/>
				</TabContent>
			</Tab>
		</Tabs>
	);
}

