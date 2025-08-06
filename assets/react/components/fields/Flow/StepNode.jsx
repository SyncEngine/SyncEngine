import React, { useCallback, useContext } from 'react';
import { Position, useReactFlow } from '@xyflow/react';
import LimitedHandle from './LimitedHandle';
import Entity from '../Entity';
import useEntities from '../../../hooks/useEntities';
import { deepClone } from '../../../utils/data';
import { TagsContext } from '../../../context/TagsContext';

export default function StepNode( props ) {
	const {
		data = {},
	} = props;

	const {
		entity = '',
		onChange,
	} = data;

	const { getNodes, getEdges } = useReactFlow();
	// @todo find a way to fetch entity info more cleanly. Maybe centralize this somewhere? Or get callbacks from Entity field?
	const [ entities, entityCallbacks, loading ] = useEntities( entity, [], {} );
	const tagsContext = useContext( TagsContext );

	const handleChange = useCallback( newValue => {
		const entityId = newValue.id;
		delete newValue.id;
		onChange( props.id, { ...data, [ entity ]: entityId, config: newValue } );
	}, [ onChange, data, entity ] );

	const actions = [ 'config', 'edit' ];
	if ( ! data[ entity ] ) {
		actions.push( 'create' );
	}

	const stepsContext = {};
	if ( ! loading ) {

		const nodes = getNodes();
		const edges = getEdges();
		const nodeMap = Object.fromEntries( nodes.map( n => [ n.id, n ] ) );

		const previousNodes = collectPreviousNodes( data._ref, nodeMap, edges );

		for ( let i = 0; i < previousNodes.length; i ++ ) {
			let row = previousNodes[ i ];
			stepsContext[ row.data._ref ] = entityCallbacks.get( row.data[ entity ] )?._step?.tags || {};
		}
	}

	const tags = {
		step: deepClone( stepsContext ),
		...deepClone( tagsContext ),
	};

	return (
		<>
			<LimitedHandle limit={ 1 } type="target" position={ Position.Top }/>
			<div className="p-2 bg-body border border-1 border-input" onClick={ e => e.stopPropagation() }>
				<h5 className="mb-0">Step: { data._ref }</h5>
				<TagsContext.Provider value={ tags }>
					<Entity
						className="nodrag"
						entity={ entity }
						value={ { id: data[ entity ], ...( data.config ?? {} ), } }
						onChange={ handleChange }
						config={ 'entity:_step.fields' }
						actions={ actions }
					/>
				</TagsContext.Provider>
			</div>
			<LimitedHandle limit={ 1 } type="source" position={ Position.Bottom }/>
		</>
	)
}


// Recursively collect previous nodes in order
function collectPreviousNodes( nodeId, nodeMap, edges, visited = new Set() ) {
	if ( visited.has( nodeId ) ) {
		return [];
	}
	visited.add( nodeId );

	const prevEdges = edges.filter( e => e.target === nodeId );
	let result = [];
	for ( const edge of prevEdges ) {
		const prevNode = nodeMap[ edge.source ];
		if ( prevNode ) {
			result = [
				...collectPreviousNodes( prevNode.id, nodeMap, edges, visited ),
				prevNode,
				...result,
			];
		}
	}
	return result;
}
