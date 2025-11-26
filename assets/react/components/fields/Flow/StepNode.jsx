import React, { useCallback, useContext } from 'react';
import { Position, useReactFlow } from '@xyflow/react';
import LimitedHandle from './LimitedHandle';
import Entity from '../Entity';
import { deepClone, mapGetIndex } from '../../../utils/data';
import { TagsContext } from '../../../context/TagsContext';
import { FlowContext } from './index';
import { parseId } from '../../../utils/globals';
import useEntity from '../../../hooks/useEntity';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import { isEmpty } from '../../../utils/conditions';
import { suppress } from '../../../utils/events';


export default function StepNode( props ) {
	const _FlowContext = useContext( FlowContext );
	const {
		data = {},
		selected,
	} = props;

	const {
		entity = _FlowContext?.entity ?? '',
		onChange,
	} = data;

	const { getNodes, getEdges } = useReactFlow();
	// @todo find a way to fetch entity info more cleanly. Maybe centralize this somewhere? Or get callbacks from Entity field?
	const [ selectedEntity, entityCallbacks, loading ] = useEntity( entity, data[ entity ], [], {} );
	const tagsContext = useContext( TagsContext );

	const handleChange = useCallback( newValue => {
		const entityId = newValue.id;
		delete newValue.id;
		if ( selectedEntity !== entityId ) {
			entityCallbacks.set( entityId );
		}
		onChange( props.id, { ...data, [ entity ]: entityId, config: newValue } );
	}, [ onChange, data, entity ] );

	const actions = [ 'config', 'edit' ];
	if ( ! data[ entity ] ) {
		actions.push( 'create' );
	}

	const nodes = getNodes();
	const nodeIndex = mapGetIndex( nodes, props.id, 'id' );

	const stepsContext = {};
	if ( ! loading ) {
		const previousNodes = collectPreviousNodes(
			data._ref,
			Object.fromEntries( nodes.map( (n, i) => [ n.id, { ...n, _index: i } ] ) ),
			getEdges()
		);

		for ( let i = 0; i < previousNodes.length; i ++ ) {
			let row = previousNodes[ i ];
			stepsContext[ row.data._ref ] = {
				label: String( row._index + 1 ),
				_tag: row.data._ref,
				_children: entityCallbacks.get( row.data[ entity ] )?._step?.tags || {}
			};
		}
	}

	const tags = {
		step: deepClone( stepsContext ),
		...deepClone( tagsContext ),
	};

	return (
		<>
			<LimitedHandle limit={ 1 } type="target" position={ Position.Top }/>
			<div className={ "p-2 bg-body border border-1 border-" + ( selected ? entity : 'input' ) } onClick={ suppress }>
			{
				loading ? <LoadingPlaceholder /> :
					<TagsContext.Provider value={ tags }>
						<Entity
							className="nodrag"
							prefix={ nodeIndex + 1 }
							entity={ entity }
							value={ { id: data[ entity ], ...( data.config ?? {} ), } }
							//selectable={ isEmpty( data[ entity ] ) } @todo, prevent re-selection if config is made?
							onChange={ handleChange }
							actions={ actions }
							config={ 'entity:_step.fields' }
						/>
					</TagsContext.Provider>
			}
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
