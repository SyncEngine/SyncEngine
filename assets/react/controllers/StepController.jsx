import React, { useState } from 'react';
import Stack from 'react-bootstrap/Stack';
import Accordion from 'react-bootstrap/Accordion';
import TaskController from "./TaskController";
import TaskSelector from "../components/TaskSelector";
import Sortable from "../components/Sortable";

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
		newTasks.push( { type: type } );
		setTasks( newTasks );

		value.tasks = tasks;
		onChange( value );
	}

	const updateTask = ( input, index ) => {
		let newTasks = [...tasks];
		newTasks[ index ] = input;
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
			{ 'Tasks' }
			<Accordion>
				<Sortable
					setItems={ setTasks }
					items={
						tasks.map( ( task, index ) => {
							if ( ! taskTypes.hasOwnProperty( task.type ) ) {
								return ( 'Not found.' );
							}
							const taskType = taskTypes[ task.type ];

							return {
								component: Accordion.Item,
								attributes: {
									eventKey: index,
								},
								header: {
									component: Accordion.Header,
									children: (
										<>{ '#' + ( index + 1 ) + ': ' + ( taskType.label ?? taskType.name ?? '' ) }</>
									)
								},
								body: (
									<Accordion.Body>
										<TaskController {...taskType} value={ task } onChange={ ( input ) => { updateTask( input, index ) } } />
									</Accordion.Body>
								),
							}
						} )
					}
				/>
			</Accordion>
			{ AddTask }
		</Stack>
	);
}
