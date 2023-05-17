import React, { useEffect, useState } from 'react';
import { Stack, Accordion } from 'react-bootstrap';
import TaskSelector from "../components/TaskSelector";
import TaskController from "./TaskController";
import Sortable from "../components/Sortable";

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
							if ( ! taskTypes.hasOwnProperty( task.type ) ) {
								return ( 'Not found.' );
							}
							const taskType = taskTypes[ task.type ];
							const label = ( task.label ) ? task.label + ' (' + ( taskType.label ?? taskType.name ) + ')' : taskType.label ?? taskType.name ?? '';
							const description = ( task.description ) ? task.description : taskType.description ?? '';

							return {
								component: Accordion.Item,
								attributes: {
									eventKey: index,
								},
								header: {
									component: Accordion.Header,
									children: (
										<Stack>
											<span>{ label }</span>
											{ description &&
											  <small className="text-secondary">{ description }</small>
											}
										</Stack>
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
