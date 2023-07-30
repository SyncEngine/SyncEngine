import React, { useState } from 'react';
import { Badge } from 'react-bootstrap';

import useTasks from '../../../hooks/useTasks';

import SelectTask from "../../form/SelectTask";
import Repeatable from "../../services/Repeatable";
import LoadingPlaceholder from '../../partials/Loading/Placeholder';

import { isSet } from '../../../utils/conditionals';
import { createRefId } from "../../../utils/globals";

export default function Tasks( props ) {

	const {
		value = props.default ?? [],
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
	const [ taskTypes ] = useTasks( props.taskTypes, props.query ?? {} );

	if ( null === taskTypes ) {
		return <LoadingPlaceholder/>
	}

	const getTaskRefs = () => tasks.map( item => item._ref );
	const getTaskIndex = ( ref ) => getTaskRefs().indexOf( ref );

	const addTask = ( type ) => {
		if ( ! type ) {
			return;
		}
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
		const index = getTaskIndex( ref );
		let newTasks = [ ...tasks ];
		if ( newTasks[ index ]._disabled ) {
			input._disabled = true;
		}
		newTasks[ index ] = input;
		updateTasks( newTasks );
	}

	const updateTasks = ( newTasks ) => {
		setTasks( newTasks );
		onChange( newTasks );
	}

	const toggleTask = ( ref ) => {
		const index = getTaskIndex( ref );
		let newTasks = [ ...tasks ];
		if ( newTasks[ index ]._disabled ) {
			delete newTasks[ index ]._disabled;
		} else {
			newTasks[ index ]._disabled = true;
		}
		updateTasks( newTasks );
	}

	const reorderTasks = ( newTasks ) => {
		updateTasks( newTasks );
	}

	const toolbar = (
		<SelectTask options={ taskTypes } onChange={ addTask } label="Add Task" variant="task"></SelectTask>
	);

	if ( ! tasks || ! tasks.length ) {
		return toolbar;
	}

	const items = tasks.map( ( task, index ) => {
		const taskType = taskTypes.hasOwnProperty( task._class ) ? taskTypes[ task._class ] : null;
		const taskInfo = ( taskType ) ? isSet( taskType.label ) ? taskType.label : taskType.name ?? '' : task._class;
		const label = task._label ?? '';
		const description = ( isSet( task._description ) ) ? task._description : ( taskType ) ? taskType.description : '';

		return {
			_ref: task._ref,
			value: task,
			label: (
				<>
					{ label &&
						<span className="me-2">{ label }</span>
					}
					{ taskInfo &&
						<Badge pill bg="task" className="text-bg-task">{ taskInfo }</Badge>
					}
					{ ! taskType &&
						<Badge bg="danger">Task not found</Badge>
					}
				</>
			),
			description: description,
			fields: taskType && taskType.fields,
			actions: { 'disable': toggleTask, 'delete': removeTask, },
			onChange: ( input ) => updateTask( input, task._ref ),
		}
	} );

	return (
		<Repeatable items={ items } inline={ false } sortable={ true } toolbar={ toolbar } addCallback={ addTask } reorderCallback={ reorderTasks }></Repeatable>
	);
}
