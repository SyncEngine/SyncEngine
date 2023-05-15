import React, { useState } from 'react';
import Stack from 'react-bootstrap/Stack';
import Accordion from 'react-bootstrap/Accordion';
import TaskController from "./TaskController";
import TaskSelector from "../components/TaskSelector";

export default function StepController( props ) {
	const {
		args: args = {},
		value: value = {},
		onChange,
	} = props;

	const {
		taskTypes: taskTypes = {},
	} = args;

	const [ tasks, setTasks ] = useState( value.tasks ?? [] );

	const addTask = ( type ) => {
		let newTasks = [...tasks];
		newTasks.push( taskTypes[ type ] );
		setTasks( newTasks );

		value.tasks = tasks;
		onChange( value );
	}

	const AddTask = (
		<TaskSelector options={ taskTypes } onChange={ addTask }></TaskSelector>
	);

	if ( ! tasks ) {
		return AddTask;
	}

	return (
		<Stack gap={2} className="my-2">
			<Accordion>
			{
				tasks.map( ( task, index ) => {
					return (
						<Accordion.Item key={ index } eventKey={ index }>
							<Accordion.Header>{ task.label ?? task.name ?? 'Task: ' + index }</Accordion.Header>
							<Accordion.Body>
								<TaskController {...task}/>
							</Accordion.Body>
						</Accordion.Item>
					)
				} )
			}
			</Accordion>
			{ AddTask }
		</Stack>
	);
}
