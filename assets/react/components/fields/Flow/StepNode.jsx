import React, { useCallback, useContext } from 'react';
import { Position, useReactFlow } from '@xyflow/react';
import { InputGroup } from 'react-bootstrap';

import { TagsContext } from '../../../context/TagsContext';
import { FlowContext, parseEdge } from './index';
import LimitedHandle from './LimitedHandle';
import Entity from '../Entity';

import useEntity from '../../../hooks/useEntity';

import DeleteModal from '../../modals/DeleteModal';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import Button from '../../partials/Button';
import Icon from '../../partials/Icon';

import { isEmpty } from '../../../utils/conditions';
import { parseId } from '../../../utils/globals';
import { deepClone, mapGetIndex } from '../../../utils/data';


export default function StepNode( props ) {
	const _FlowContext = useContext( FlowContext );
	const {
		id,
		data = {},
		selected,
	} = props;

	const {
		entity = _FlowContext?.entity ?? '',
		onChange,
	} = data;

	const { getNodes, getEdges, setNodes, setEdges } = useReactFlow();
	// @todo find a way to fetch entity info more cleanly. Maybe centralize this somewhere? Or get callbacks from Entity field?
	const [ selectedEntity, entityCallbacks, loading ] = useEntity( entity, data[ entity ], [], {} );
	const tagsContext = useContext( TagsContext );

	const onNodeRemove = useCallback( () => {
		const edges = getEdges();
		const previous = edges.find( ( edge ) => edge.target === id );
		const next = edges.find( ( edge ) => edge.source === id );

		setNodes( ( nodes ) => nodes.filter( ( node ) => node.id !== id ) );

		if ( previous && next ) {
			setEdges(( edges ) => {
				return [
					...edges,
					parseEdge( { source: previous.source, target: next.target } ),
				];
			});
		}
	})

	const handleChange = useCallback( newValue => {
		const entityId = newValue.id;
		delete newValue.id;
		// @todo only set if actually changed?
		entityCallbacks.set( entityId );
		onChange( props.id, { ...data, [ entity ]: entityId, config: newValue } );
	}, [ onChange, data, entity ] );

	const actions = [ 'edit' ];
	if ( ! selectedEntity ) {
		actions.push( 'create' );
	} else {
		actions.unshift( 'config' );
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
			<InputGroup className={ "p-2 gap-2 bg-body border border-1 border-" + ( selected ? entity : 'input' ) }>
				<InputGroup.Text>{ nodeIndex + 1 }</InputGroup.Text>
				{
					loading ? <LoadingPlaceholder /> :
						<TagsContext.Provider value={ tags }>
							<Entity
								className="nodrag"
								entity={ entity }
								value={ { id: data[ entity ], ...( data.config ?? {} ), } }
								//selectable={ isEmpty( data[ entity ] ) } @todo, prevent re-selection if config is made?
								onChange={ handleChange }
								actions={ actions }
								config={ 'entity:_step.fields' }
							/>
						</TagsContext.Provider>
				}
				{ selected &&
					<DeleteModal callback={ onNodeRemove }>
						<Button variant="secondary" outline subtle><Icon icon="trash" /></Button>
					</DeleteModal>
				}
			</InputGroup>
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
