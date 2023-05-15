import * as React from 'react';
import { getTaskTypes } from '../utils/globals';
import { TaskController } from "./TaskController";
import { TaskSelector } from "../components/TaskSelector";

export function StepController( props ) {

	const {
		config,
	} = props;

	const {
		tasks,
	} = config;

	const addTask = ( type ) => {
		let allTasks = getTaskTypes();
		tasks.push( allTasks( type ) );
	}

	const AddTask = (
		<TaskSelector options={ getTaskTypes } onChange={ addTask }></TaskSelector>
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
			<hr>
			<h2>Add Task</h2>
			{ AddTask }
		</>
	);
}
