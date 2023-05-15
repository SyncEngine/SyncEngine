import React, { useState } from 'react';
import Accordion from 'react-bootstrap/Accordion';
import { TaskController } from "./TaskController";
import { TaskSelector } from "../components/TaskSelector";

export function StepController( props ) {
	const {
		config: config = {},
		value: value = {},
		onChange,
	} = props;

	const {
		taskTypes: taskTypes = {},
	} = config;

	const [ tasks, setTasks ] = useState( value.tasks ?? [] );

	const addTask = ( type ) => {
		tasks.push( taskTypes[ type ] );
	}

	const AddTask = (
		<TaskSelector options={ taskTypes } onChange={ addTask }></TaskSelector>
	);

	if ( ! tasks ) {
		return AddTask;
	}

	return (
		<>
			<Accordion>
			{
				tasks.map( ( task, index ) => {
					return (
						<Accordion.Item key={ index }>
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
		</>
	);
}
