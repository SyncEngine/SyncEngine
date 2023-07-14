import React from 'react';
import { Tabs, Tab } from 'react-bootstrap';
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
			<Tab eventKey="tasks" title="Tasks" style={ { marginTop: '-1px' } }>
				<Tasks
					{...args}
					value={ value.tasks ?? [] }
					onChange={ updateTasks }
				/>
			</Tab>
			<Tab eventKey="conditionals" title="Conditionals" className="bg-body p-3 border border-top-0">
				<Conditionals
					{...args}
					value={ value.conditionals ?? [] }
					onChange={ updateConditionals }
				/>
			</Tab>
		</Tabs>
	);
}

