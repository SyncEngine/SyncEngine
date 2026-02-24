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

import { parseId } from '../../../utils/globals';
import { deepClone, mapGetIndex } from '../../../utils/data';
import { suppress } from '../../../utils/events';


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
		preview = _FlowContext?.preview ?? false,
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

	const nodes = getNodes();
	const nodeIndex = mapGetIndex( nodes, props.id, 'id' );

	const actions = [ 'edit' ];
	if ( ! selectedEntity ) {
		actions.push( 'create' );
	} else {
		const configAction = { action: 'config' };
		if ( preview ) {
			configAction.preview = {
				type: 'step',
				title: t( 'Step #{number}', { number: nodeIndex + 1 } ),
				item: () => data,
				scope: scope,
				onParse: ( params ) => {
					return {
						...params,
						// Full step config required.
						config: {
							config: params.config,
							// After config changes the data below is omitted, this fixes this.
							[entity]: data[ entity ],
							_ref: data._ref,
							_meta: {
								entity: entity,
								index: nodeIndex,
								number: nodeIndex + 1,
							},
						}
					}
				}
			};
		}
		actions.unshift( configAction );
	}

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
			<LimitedHandle limit={ 1 } type="target" position={ Position.Top } />
			<InputGroup className={ "p-2 gap-2 bg-body border border-1 border-" + ( selected ? entity : 'input' ) }>
				<InputGroup.Text>{ nodeIndex + 1 }</InputGroup.Text>
				<div onClick={ suppress }>
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
								preview={ preview }
							/>
						</TagsContext.Provider>
				}
				</div>
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
