import React, { useEffect, useState } from 'react';
import { Stack, Accordion, Badge } from 'react-bootstrap';
import TaskSelector from "../components/TaskSelector";
import TaskController from "./TaskController";
import Sortable from "../components/Sortable";
import { isSet } from "../utils/conditionals";

export default function TasksController( props ) {

	const {
		taskTypes: taskTypes = {},
		value: value = [],
		onChange,
	} = props;

	const [ tasks, setTasks ] = useState( value );

	/**
	 * Update parent value.
	 * This needs to be an effect since the state update is async.
	 */
	useEffect( () => {
		onChange( tasks );
	}, [ tasks ] );

	const addTask = ( type ) => {
		let newTasks = [...tasks];
		newTasks.push( { type: type } );
		updateTasks( newTasks );
	}

	const updateTask = ( input, index ) => {
		let newTasks = [...tasks];
		newTasks[ index ] = input;
		updateTasks( newTasks );
	}

	const updateTasks = ( newTasks ) => {
		setTasks( newTasks );
	}

	const AddTask = (
		<TaskSelector options={ taskTypes } onChange={ addTask }></TaskSelector>
	);

	if ( ! tasks || ! tasks.length ) {
		return AddTask;
	}

	return (
		<Stack gap={2}>
			<Accordion>
				<Sortable
					setItems={ updateTasks }
					items={
						tasks.map( ( task, index ) => {
							const taskType = taskTypes.hasOwnProperty( task.type ) ? taskTypes[ task.type ] : null;
							const taskInfo = ( taskType ) ? isSet( taskType.label ) ? taskType.label : taskType.name ?? '' : task.type;
							const label = ( isSet( task.label ) ) ? task.label + ' (' + taskInfo + ')' : taskInfo;
							const description = ( isSet( task.description ) ) ? task.description : ( taskType ) ? taskType.description : '';

							return {
								component: Accordion.Item,
								attributes: {
									eventKey: index,
								},
								header: {
									component: Accordion.Header,
									children: (
										<Stack>
											<span>
												{ label }
												{ ! taskType &&
												  <Badge bg="danger" className="ms-2">Task not found</Badge>
												}
											</span>
											{ description &&
											  <small className="text-secondary">{ description }</small>
											}
										</Stack>
									)
								},
								body: (
									<Accordion.Body>
										{ taskType &&
										  <TaskController {...taskType} value={ task } onChange={ ( input ) => { updateTask( input, index ) } } />
										}
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
