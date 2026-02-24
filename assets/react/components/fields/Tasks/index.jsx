import React, { useCallback, useState } from 'react';
import { any, array, bool, func, number, object, oneOfType } from 'prop-types';
import { useTranslation } from 'react-i18next';
import { OverlayTrigger, Tooltip } from 'react-bootstrap';

import useTasks from '../../../hooks/useTasks';

import SelectTask from '../../form/SelectTask';
import Repeatable from '../../services/Repeatable';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import PreviewModal from '../../modals/PreviewModal';
import Badge from '../../partials/Badge';

import { createRefId } from '../../../utils/globals';
import { mapGetIndex, objectToMappable } from '../../../utils/data';
import useClipboard from '../../../hooks/useClipboard';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import useFieldValue from '../../../hooks/useFieldValue';
import Icon from '../../partials/Icon';
import Button from '../../partials/Button';

function parseValue( value ) {
	return objectToMappable( value ).map( ( row ) => {
		if ( ! row.hasOwnProperty( '_ref' ) ) {
			row._ref = createRefId();
		}
		return row;
	} )
}

function replaceRefIds( obj ) {
	if ( Array.isArray( obj ) ) {
		obj.forEach( item => replaceRefIds( item ) );
	} else if ( obj && typeof obj === 'object' ) {
		for ( const [ key, value ] of Object.entries( obj ) ) {
			// Only do this for `_ref` props. `ref` might actually be an entity.
			if ( '_ref' === key ) {
				obj[ key ] = createRefId();
			} else {
				replaceRefIds( value );
			}
		}
	}
	return obj;
}

function getTaskLabel( task, taskTypes = {} ) {
	let label = '';
	if ( task.hasOwnProperty( '_label' ) && task._label ) {
		label = task._label;
	}

	const taskType = taskTypes.hasOwnProperty( task._class ) ? taskTypes[ task._class ] : null;
	let taskLabel = ( taskType ) ? taskType.label || taskType.name || '' : task._class;

	return label ? label + ' (' + taskLabel + ')' : taskLabel;
}

const TaskLabel = ( props ) => {
	const {
		type = {},
	} = props;

	const [ label ] = useFieldValue( '_label' ) ?? '';
	const taskInfo = type.label || type.name || useFieldValue( '_class' );

	return <>
		{ label &&
		  <span className="me-2">{ label }</span>
		}
		{ taskInfo &&
		  <Badge pill subtle bg="task" className="text-bg-task">{ taskInfo }</Badge>
		}
		{ ! type &&
		  <Badge bg="danger">Task not found</Badge>
		}
	</>
}

const TaskDescription = ( props ) => {
	const {
		type = {},
	} = props;

	const [ description ] = useFieldValue( '_description' );

	return <>{ description || '' }</>;
}

export default function Tasks( props ) {
	const { t } = useTranslation();
	const [ clipboard, updateClipboard ] = useClipboard( 'task' );
	const editable = isFieldEditable( props );

	const {
		value = props.default ?? [],
		onChange,
	} = props;

	// Render keys is a unique list of index keys for React re-render triggers.
	const [ renderKeys, setRenderKeys ] = useState( {} );

	const [ tasks, setTasks ] = useState( parseValue( value ) );
	const [ taskTypes ] = useTasks( props.taskTypes, props.query ?? {} );

	const updateTasks = useCallback( ( newTasks ) => {
		setTasks( newTasks );
		onChange( newTasks );
	}, [ setTasks, onChange ] );

	const addTask = useCallback( ( type, config = {} ) => {
		if ( ! type ) {
			return;
		}
		let newTasks = [ ...tasks ];
		newTasks.push( { ...config, _class: type, _ref: createRefId() } );
		updateTasks( newTasks );
	}, [ updateTasks, tasks ] );

	const removeTask = useCallback( ( ref ) => {
		let newTasks = [ ...tasks ];
		newTasks.splice( mapGetIndex( tasks, ref, '_ref' ), 1 );
		updateTasks( newTasks );
	}, [ updateTasks, tasks ] );

	const updateTask = useCallback( ( input, ref ) => {
		const index = mapGetIndex( tasks, ref, '_ref' );
		// Do not remove reference to prevent new memo.
		let newTasks = tasks; //[ ...tasks ];
		if ( newTasks[ index ]._disabled ) {
			input._disabled = true;
		}
		newTasks[ index ] = input;
		updateTasks( newTasks );
	}, [ updateTasks, tasks ] );

	const getTask = useCallback( ( ref ) => {
		return tasks[ mapGetIndex( tasks, ref, '_ref' ) ];
	}, [ tasks ] );

	const toggleTask = useCallback( ( ref ) => {
		const index = mapGetIndex( tasks, ref , '_ref' );
		let newTasks = [ ...tasks ];
		if ( newTasks[ index ]._disabled ) {
			delete newTasks[ index ]._disabled;
		} else {
			newTasks[ index ]._disabled = true;
		}
		updateTasks( newTasks );
	}, [ updateTasks, tasks ] );

	const reorderTasks = useCallback( ( newTasks ) => {
		updateTasks( newTasks );
	}, [ updateTasks ] );

	const Paste = ( { callback, children, tooltip } ) => (
		<OverlayTrigger overlay={ <Tooltip>{ tooltip }</Tooltip> }>
			<Button variant="task" onClick={ callback }>{ children }</Button>
		</OverlayTrigger>
	);

	const onPaste = () => {
		addTask( clipboard._class, replaceRefIds( clipboard ) );
	}

	if ( isEmpty( taskTypes ) ) {
		return <LoadingPlaceholder/>
	}

	const toolbar = editable && (
		<>
			<SelectTask options={ taskTypes } onChange={ addTask } label="Add Task" variant="task"></SelectTask>
			{ ( clipboard && clipboard.hasOwnProperty( '_class' ) ) &&
				<Paste
					callback={ onPaste }
					tooltip={ "Task Clipboard: " + getTaskLabel( clipboard, taskTypes ) }
				>
					Paste
				</Paste>
			}
		</>
	);

	const items = ( tasks && tasks.length ) && tasks.map( ( task, index ) => {
		const taskType = taskTypes[ task._class ];

		const onConfigChange = ( input ) => editable && updateTask( input, task._ref );

		return {
			_ref: task._ref,
			_key: renderKeys[ task._ref ],
			value: task,
			label: <TaskLabel type={ taskType } />,
			description: <TaskDescription type={ taskType } />,
			icon: taskType && taskType.icon,
			fields: taskType && taskType.fields,
			body: {
				label: ( taskType ) ? taskType.label : '',
				description: ( taskType ) ? taskType.description : '',
			},
			actions: editable && {
				'preview': (
					<PreviewModal
						title={ () => t('Preview') + ': ' + getTaskLabel( task, taskTypes ) }
						item={ () => getTask( task._ref ) } // Function to always return latest data.
						fields={ taskType && taskType.fields }
						onSave={ ( input ) => { onConfigChange( input ); setRenderKeys( { ...renderKeys, [ task._ref ]: createRefId() } ) } }
						type="task"
					>
						<Icon icon="preview" className="icon-link scale-110-hover transition-all transition-fast" />
					</PreviewModal>
				),
				'copy': updateClipboard,
				'disable': toggleTask,
				'delete': removeTask,
			},
			onChange: onConfigChange,
		}
	} );

	return (
		<Repeatable
			items={ items }
			inline={ false }
			toolbar={ toolbar }
			max={ props.max }
			editable={ editable }
			sortable={ props.sortable ?? editable }
			disabled={ props.disabled }
			addCallback={ addTask }
			reorderCallback={ reorderTasks }
			className="sequence"
		/>
	);
}

Tasks.propTypes = {
	onChange: func,
	value: any,
	default: any,
	editable: bool,
	disabled: bool,
	readonly: bool,
	sortable: bool,
	taskTypes: oneOfType( [ object, array ] ),
	query: object,
	max: number
}
