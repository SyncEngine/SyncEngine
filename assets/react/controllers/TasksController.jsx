import React, { useState } from 'react';
import { Stack, Accordion, Badge } from 'react-bootstrap';
import TaskSelector from "../components/TaskSelector";
import TaskController from "./TaskController";
import Sortable from "../components/Sortable";
import { isSet } from "../utils/conditionals";
import { createRefId } from "../utils/globals";

export default function TasksController( props ) {

	const {
		taskTypes: taskTypes = {},
		value: value = [],
		onChange,
	} = props;

	const parseValue = ( value ) => {
		return value.map( ( row ) => {
			if ( ! row.hasOwnProperty( 'id' ) ) {
				row.id = createRefId();
			}
			return row;
		} )
	}

	const [ tasks, setTasks ] = useState( parseValue( value ) );

	const addTask = ( type ) => {
		let newTasks = [ ...tasks ];
		newTasks.push( { type: type, id: createRefId() } );
		updateTasks( newTasks );
	}

	const updateTask = ( input, ref ) => {
		let newTasks = [ ...tasks ];
		for ( const index in tasks ) {
			if ( tasks[ index ].id === ref ) {
				newTasks[ index ] = { ...input };
				updateTasks( newTasks );
			}
		}
	}

	const updateTasks = ( newTasks ) => {
		setTasks( newTasks );
		onChange( newTasks );
	}

	const reorderTasks = ( newTasks ) => {
		updateTasks( newTasks );
	}

	const AddTask = (
		<TaskSelector options={ taskTypes } onChange={ addTask } label="Add asdTask"></TaskSelector>
	);

	if ( ! tasks || ! tasks.length ) {
		return AddTask;
	}

	return (
		<Stack gap={2}>
			<Accordion>
				<Sortable
					setItems={ reorderTasks }
					items={
						tasks.map( ( task, index ) => {
							const taskType = taskTypes.hasOwnProperty( task.type ) ? taskTypes[ task.type ] : null;
							const taskInfo = ( taskType ) ? isSet( taskType.label ) ? taskType.label : taskType.name ?? '' : task.type;
							const label = ( isSet( task.label ) ) ? task.label + ' (' + taskInfo + ')' : taskInfo;
							const description = ( isSet( task.description ) ) ? task.description : ( taskType ) ? taskType.description : '';

							return {
								id: task.id,
								value: task,
								component: Accordion.Item,
								attributes: {
									eventKey: task.id,
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
											<TaskController {...taskType} value={ task } onChange={ ( input ) => { updateTask( input, task.id ) } } />
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
