import React, { useState } from 'react';
import { Stack, Accordion, Badge } from 'react-bootstrap';

import Task from "./Task";
import SelectTask from "../../form/SelectTask";
import ConfirmDelete from "../../form/ConfirmDelete";
import Sortable from "../../services/Sortable";

import { isSet } from "../../../utils/conditionals";
import { createRefId } from "../../../utils/globals";

export default function Tasks( props ) {

	const {
		taskTypes = {},
		value = [],
		onChange,
	} = props;

	const parseValue = ( value ) => {
		return value.map( ( row ) => {
			if ( ! row.hasOwnProperty( '_ref' ) ) {
				row._ref = createRefId();
			}
			return row;
		} )
	}

	const [ tasks, setTasks ] = useState( parseValue( value ) );

	const getTaskRefs = () => tasks.map( item => item._ref );
	const getTaskIndex = ( ref ) => getTaskRefs().indexOf( ref );

	const addTask = ( type ) => {
		let newTasks = [ ...tasks ];
		newTasks.push( { _class: type, _ref: createRefId() } );
		updateTasks( newTasks );
	}

	const removeTask = ( ref ) => {
		let newTasks = [ ...tasks ];
		newTasks.splice( getTaskIndex( ref ), 1 );
		updateTasks( newTasks );
	}

	const updateTask = ( input, ref ) => {
		let newTasks = [ ...tasks ];
		newTasks[ getTaskIndex( ref ) ] = input;
		updateTasks( newTasks );
	}

	const updateTasks = ( newTasks ) => {
		setTasks( newTasks );
		onChange( newTasks );
	}

	const reorderTasks = ( newTasks ) => {
		updateTasks( newTasks );
	}

	const AddTask = (
		<SelectTask options={ taskTypes } onChange={ addTask } label="Add Task"></SelectTask>
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
							const taskType = taskTypes.hasOwnProperty( task._class ) ? taskTypes[ task._class ] : null;
							const taskInfo = ( taskType ) ? isSet( taskType.label ) ? taskType.label : taskType.name ?? '' : task._class;
							const label = ( isSet( task.label ) ) ? task.label + ' (' + taskInfo + ')' : taskInfo;
							const description = ( isSet( task.description ) ) ? task.description : ( taskType ) ? taskType.description : '';

							return {
								id: task._ref,
								value: task,
								component: Accordion.Item,
								attributes: {
									eventKey: task._ref,
								},
								header: {
									component: Accordion.Header,
									children: (
										<>
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
											<ConfirmDelete callback={ () => removeTask( task._ref ) } />
										</>
									)
								},
								body: (
									<Accordion.Body>
										{ taskType &&
											<Task {...taskType} value={ task } onChange={ ( input ) => updateTask( input, task._ref ) } />
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
