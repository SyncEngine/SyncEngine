import React, { createContext, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
	addEdge,
	applyEdgeChanges,
	applyNodeChanges,
	Background,
	Controls,
	getConnectedEdges,
	getIncomers,
	getOutgoers,
	MarkerType,
	MiniMap,
	ReactFlow,
	ReactFlowProvider,
	useEdgesState,
	useNodesState,
	useReactFlow,
} from '@xyflow/react';

import '@xyflow/react/dist/style.css';

import StepNode from './StepNode';
import StepEdge from './StepEdge';

import useGlobal from '../../../hooks/useGlobal';
import { createRefId, debug } from '../../../utils/globals';
import { debounce } from '../../../utils/events';
import { deepClone, objectMerge, objectToMappable } from '../../../utils/data';
import { isEmpty, isEqual } from '../../../utils/conditions';
import useEntities from '../../../hooks/useEntities';

const edgeIdSeparator = '->';
const edgeDefaults = {
	animated: true,
	type: 'step',
	style: {
		strokeWidth: 3,
	},
	markerEnd: {
		type: MarkerType.ArrowClosed,
	},
}
const nodeDefaults = {
	data: {},
	type: 'step',
	position: { x: 0, y: 0 },
};

export default ( props ) => (
	<ReactFlowProvider>
		<Flow { ...props } />
	</ReactFlowProvider>
);

export const FlowContext = createContext( {} );

function parseValue( value, defaults ) {
	const nodes = buildFlowChain( value.map( ( node, index ) => {
		node = parseNodeValue( node, defaults );

		// Auto target.
		const nextNode = value[ index + 1 ] ?? null;
		if ( ! node.target && nextNode ) {
			if ( ! nextNode.hasOwnProperty( '_ref' ) ) {
				nextNode._ref = createRefId();
			}
			node.target = nextNode._ref;
		}

		return node;
	} ) );

	if ( isEmpty( nodes ) ) {
		// Add empty node.
		nodes.push(
			parseNodeValue( {}, defaults )
		);
	}

	return [
		{
			id: 'input',
			type: 'input',
			target: nodes[0]?.id,
			position: { x: 0, y: 0 },
			data: { label: 'Input' },
		},
		...nodes,
		/*{
			id: 'output',
			type: 'output',
			target: null,
			position: { x: 0, y: 0 },
			data: { label: 'Output' },
		},*/
	];
}

function parseNodeValue( node, defaults ) {
	if ( ! node.hasOwnProperty( '_ref' ) ) {
		node._ref = node.id || createRefId();
	}

	node._meta ??= {};

	( node.origin ) ? node._meta.origin = node.origin : null;
	( node.position ) ? node._meta.position = node.position : null;

	node = objectMerge( deepClone( defaults ), node._meta, { data: node } );

	delete node.data._meta;
	delete node.data.origin;
	delete node.data.position;

	node.id = node.data._ref;

	return parseNode( node );
}

export function parseNode( node, defaults = {} ) {
	if ( ! node.id ) {
		node.id = node._ref ?? node.data?._ref ?? createRefId();
	}
	return { ...deepClone( nodeDefaults ), ...deepClone( defaults ), ...node };
}

export function parseEdge( edge = {}, defaults = {} ) {
	if ( ! edge.id ) {
		edge.id = edge.source + edgeIdSeparator + edge.target;
	}
	return { ...deepClone( edgeDefaults ), ...deepClone( defaults ), ...edge };
}

function parseEdgesFromNodes( nodes ) {
	return nodes
		.filter( node => node.target )
		.map( node => (
			parseEdge( {
				source: node.id,
				target: node.target,
			} )
		) );
}

function Flow( props ) {
	const app = useGlobal();
	const {
		onChange,
		spacing = 100,
		entity,
		preview = false,
	} = props;

	const [ entities, entityCallbacks, loading ] = useEntities( entity, {}, {} );

	const onNodeChange = useCallback( ( id, changes ) => {
		setNodes( ( nodes ) =>
			nodes.map( ( node ) => {
				if ( node.id === id ) {
					return { ...node, data: { ...node.data, ...changes } };
				}
				return node;
			} ),
		);
	}, [] );

	const nodeDefaults = useMemo(
		() => ({ data: { entity: entity, onChange: onNodeChange, preview: preview } }),
		[ entity, onNodeChange, preview ]
	);

	const value = useMemo(
		() => parseValue( objectToMappable( props.value || [] ), nodeDefaults ),
		[ props.value, nodeDefaults ]
	);

	// Validate flow structure
	const validateFlow = useCallback( ( nodes ) => {
		const inputNodes = nodes.filter( node => node.type === 'input' || node.id === 'input' );
		if ( inputNodes.length > 1 ) {
			console.error( 'Flow validation error: Multiple input nodes detected. There should only be one input node.' );
		}
	}, [] );

	// Resolve overlapping node positions without updating targets
	const resolveLayoutOverlaps = useCallback( ( nodes ) => {
		const result = resolveOverlaps( nodes, { spacing } );
		debug( 'Checked layout overlaps', { sameRef: result === nodes, sameData: isEqual( result, nodes ) } );
		return result;
	}, [ spacing ] );

	const [ theme ] = useState( app.theme.getTheme() );
	const [ edges, setEdges ] = useEdgesState( parseEdgesFromNodes( value ) );
	const [ nodes, setNodes ] = useNodesState( () => {
		const rebuilt = buildFlowChain( value, parseEdgesFromNodes( value ) );
		validateFlow( rebuilt );
		return resolveLayoutOverlaps( rebuilt );
	} );
	const { getNodes, getEdges, screenToFlowPosition } = useReactFlow();

	// Track drag/resize state to avoid parsing during user interactions
	const isInteractingRef = React.useRef( false );

	// Custom onNodesChange: only parse on structural changes (add/remove)
	const onNodesChange = useCallback( ( changes ) => {
		// Check if this is a structural change requiring reference updates
		const _hasStructuralChange = hasStructuralChange( changes );
		const _hasDimensionChange = hasDimensionChange( changes );

		setNodes( ( nds ) => {
			let updated = applyNodeChanges( changes, nds );

			// Only validate and update references on structural changes
			if ( _hasStructuralChange ) {
				validateFlow( updated );
				updated = buildFlowChain( updated, getEdges() );
			}
			if ( _hasDimensionChange ) {
				updated = resolveLayoutOverlaps( updated );
			}

			return updated;
		} );
	}, [ getEdges, validateFlow ] );

	// Custom onEdgesChange: only update node references on structural changes (add/remove)
	const onEdgesChange = useCallback( ( changes ) => {
		setEdges( ( eds ) => {
			const updated = applyEdgeChanges( changes, eds );

			// Only update node targets when edges structure changes
			if ( hasStructuralChange( changes ) ) {
				setNodes( ( nds ) => buildFlowChain( nds, updated ) );
			}

			return updated;
		} );
	}, [] );

	const handleUpdate = React.useRef(
		debounce( ( nodes, edges, onChange ) => {
			if ( isEmpty( nodes ) ) {
				return;
			}
			// Validate edges and update node targets to match current edges
			const validEdges = edges.filter( edge => edge.target && edge.source );
			//const validTargets = Object.fromEntries( validEdges.map( edge => [ edge.source, edge.target ] ) );

			onChange(
				buildFlowChain( nodes, validEdges )
					.filter( node => ! [ 'input', 'output' ].includes( node.type ) )
					.map( node => {
						const parsedNode = deepClone({
							...node.data,
							//target: validTargets[ node.id ] || null,
							_meta: { position: node.position }
						});
						if ( ! parsedNode._ref ) {
							parsedNode._ref = node.id;
						}
						delete parsedNode.entity;
						delete parsedNode.onChange;
						delete parsedNode.preview;
						return parsedNode;
					} )
			);
		}, 100 ),
	).current;

	const onConnect = useCallback( ( params ) => {
		setEdges( ( eds ) => addEdge( parseEdge( params ), eds ) );
	}, [] );

	// Track user interactions to defer layout operations
	const onNodeDragStart = useCallback( () => {
		isInteractingRef.current = true;
	}, [] );

	const onNodeDragStop = useCallback( () => {
		isInteractingRef.current = false;
		// Only resolve overlaps after drag completes (don't update targets)
		setNodes( ( nds ) => resolveLayoutOverlaps( nds ) );
	}, [ resolveLayoutOverlaps ] );

	const onNodesDelete = useCallback(
		( deleted ) => {
			setEdges(
				deleted.reduce( ( acc, node ) => {
					const incomers = getIncomers( node, nodes, acc );
					const outgoers = getOutgoers( node, nodes, acc );
					const connectedEdges = getConnectedEdges( [ node ], acc );

					const remainingEdges = acc.filter( edge => ! connectedEdges.includes( edge ) );

					const createdEdges = incomers.flatMap( ( { id: source } ) =>
						outgoers.map( ( { id: target } ) => parseEdge( { source, target } ) ),
					);

					return [ ...remainingEdges, ...createdEdges ];
				}, edges),
			);
		},
		[ nodes, edges ],
	);

	// Insert node between two connected nodes by splitting the edge and adding a new node at the specified position.
	const addNodeBetween = useCallback( ( edgeId, position ) => {
		setEdges( ( eds ) => {
			const current = eds.find( ( edge ) => edge.id === edgeId );
			if ( ! current ) {
				return eds;
			}

			const newNodeId = createRefId();
			const newNode = parseNode( {
				id: newNodeId,
				position,
				origin: [ 0.5, 0.0 ],
			}, nodeDefaults );

			const newEdges = [
				...eds.filter( ( edge ) => edge.id !== edgeId ),
				parseEdge( { source: current.source, target: newNodeId } ),
				parseEdge( { source: newNodeId, target: current.target } ),
			];

			// Only resolve overlaps after nodes are measured
			setNodes( ( nds ) => nds.concat( newNode ) );

			return newEdges;
		} );
	}, [ nodeDefaults ] );

	const removeEdge = useCallback( ( edgeId ) => {
		setEdges( ( eds ) => eds.filter( ( edge ) => edge.id !== edgeId ) );
	}, [] );

	const removeNode = useCallback( ( nodeId ) => {
		setEdges( ( eds ) => {
			const previous = eds.find( ( edge ) => edge.target === nodeId );
			const next = eds.find( ( edge ) => edge.source === nodeId );
			const connectedEdges = eds.filter( ( edge ) => edge.target === nodeId || edge.source === nodeId );

			let newEdges = eds.filter( ( edge ) => ! connectedEdges.includes( edge ) );
			if ( previous && next ) {
				newEdges = [ ...newEdges, parseEdge( { source: previous.source, target: next.target } ) ];
			}

			setNodes( ( nds ) => nds.filter( ( node ) => node.id !== nodeId ) );

			return newEdges;
		} );
	}, [] );

	const onConnectEnd = useCallback(
		( event, connectionState ) => {
			// when a connection is dropped on the pane it's not valid
			if ( ! connectionState.isValid ) {
				// we need to remove the wrapper bounds, in order to get the correct position
				const newNodeId = createRefId();
				const { clientX, clientY } = 'changedTouches' in event ? event.changedTouches[0] : event;
				const newNode = parseNode( {
					_ref: newNodeId,
					position: screenToFlowPosition( {
						x: clientX,
						y: clientY,
					} ),
					origin: [ 0.5, 0.0 ],
				}, { data: { entity: entity } } );

				const sourceNodeId = connectionState.fromNode.id;

				setEdges(
					( edges ) => {
						const newEdge = parseEdge( { source: sourceNodeId, target: newNodeId } );
						edges = edges.concat( newEdge );

						setNodes( ( nodes ) => nodes.concat( newNode ) );

						return edges;
					}
				);
			}
		},
		[ screenToFlowPosition, entity ],
	);

	const isValidConnection = useCallback(
		( connection ) => {
			// https://reactflow.dev/examples/interaction/prevent-cycles
			// we are using getNodes and getEdges helpers here
			// to make sure we create isValidConnection function only once
			const nodes = getNodes();
			const edges = getEdges();
			const target = nodes.find( node => node.id === connection.target );
			const hasCycle = ( node, visited = new Set() ) => {
				if ( visited.has( node.id ) ) {
					return false;
				}

				visited.add( node.id );

				for ( const outgoer of getOutgoers( node, nodes, edges ) ) {
					if ( outgoer.id === connection.source ) {
						return true;
					}
					if ( hasCycle( outgoer, visited ) ) {
						return true;
					}
				}
			};

			if ( target.id === connection.source ) {
				return false;
			}
			return ! hasCycle( target );
		},
		[ getNodes, getEdges ],
	);

	useEffect( () => {
		// Skip onChange updates during drag
		if ( isInteractingRef.current ) {
			return;
		}
		handleUpdate( nodes, edges, onChange );
	}, [ nodes, edges, onChange ] );

	const snapGrid = undefined;//[ 20, 20 ];
	const edgeTypes = { step: StepEdge };
	const nodeTypes = { step: StepNode };

	return (
		<div className="flow-container" style={ { width: '100%', height: 500 } }>
			<FlowContext.Provider value={
				{
					nodeDefaults,
					preview,
					loading,
					entity,
					entities,
					entityCallbacks,
					callbacks: {
						addNodeBetween,
						removeEdge,
						removeNode,
					},
				}
			}>
				<ReactFlow
					className="bg-transparent"
					colorMode={ theme === 'dark' ? 'dark' : 'light' }
					nodes={ nodes }
					edges={ edges }
					nodeTypes={ nodeTypes }
					edgeTypes={ edgeTypes }
					onNodesChange={ onNodesChange }
					onNodeDragStart={ onNodeDragStart }
					onNodeDragStop={ onNodeDragStop }
					onNodesDelete={ onNodesDelete }
					onEdgesChange={ onEdgesChange }
					onConnect={ onConnect }
					onConnectEnd={ onConnectEnd }
					isValidConnection={ isValidConnection }
					snapGrid={ snapGrid }
					snapToGrid
					fitView
					fitViewOptions={ { padding: 1.5 } }
				>
					<Background bgColor="transparent"/>
					<Controls/>
					<MiniMap/>
				</ReactFlow>
			</FlowContext.Provider>
		</div>
	);
}

function hasStructuralChange( changes ) {
	return changes.some( c => c.type === 'add' || c.type === 'remove' );
}

function hasDimensionChange( changes ) {
	return changes.some( c => c.type === 'dimensions' && c.dimensions );
}

/**
 * Updates node target/source properties based on current edges.
 * Clears old references and sets new ones from the edge list.
 * Returns original nodes if nothing changed.
 * @param {Array} nodes - Array of nodes.
 * @param {Array} edges - Array of edges with `source` and `target` properties.
 * @returns {Array} Nodes with updated target/source properties.
 */
function syncTargetsFromEdges( nodes, edges = [] ) {
	if ( ! edges || ! edges.length ) {
		return nodes;
	}

	nodes.forEach( ( node, i ) => {
		node.target = edges.find( edge => edge.source === node.id )?.target ?? null;
		node.source = edges.find( edge => edge.target === node.id )?.source ?? null;
	} );

	return nodes;
}

/**
 * Sorts nodes by following the target chain from the Input node.
 * Input node is always first, then nodes are ordered by target relationships.
 * Any additional root nodes or disconnected nodes are appended at the end.
 * Returns original nodes if order hasn't changed.
 * @param {Array} nodes - Array of nodes with `id` and `target` properties.
 * @returns {Array} Sorted array of nodes.
 */
function sortByTargetChain( nodes ) {
	const nodeMap = Object.fromEntries( nodes.map( node => [ node.id, node ] ) );

	const visited = new Set();
	const result = [];

	// Always start with the Input node
	const inputNode = nodes.find( node => node.type === 'input' || node.id === 'input' );

	const visitChain = node => {
		while ( node && ! visited.has( node.id ) ) {
			result.push( node );
			visited.add( node.id );
			node = node.target ? nodeMap[ node.target ] : null;
		}
	};

	// Start with the input node if it exists
	if ( inputNode ) {
		visitChain( inputNode );
	}

	// Find any other root nodes (no incoming connections) and visit their chains
	const incoming = {};
	nodes.forEach( node => {
		if ( node.target ) {
			incoming[ node.target ] = true;
		}
	} );
	const otherRoots = nodes.filter( node =>
		! incoming[ node.id ] && node.id !== inputNode?.id
	);
	otherRoots.forEach( root => visitChain( root ) );

	// Append any unvisited nodes (disconnected or cycles)
	nodes.forEach(node => {
		if ( ! visited.has( node.id ) ) {
			result.push( node );
			visited.add( node.id );
		}
	});

	// Order hasn't changed, return original nodes to prevent object reference changes.
	if ( result.length === nodes.length && result.every( ( node, i ) => node.id === nodes[ i ].id ) ) {
		return nodes;
	}

	return result;
}

/**
 * Builds the flow chain by syncing node targets from edges and sorting by target chain.
 * Combines syncTargetsFromEdges and sortByTargetChain operations.
 * Returns original nodes if nothing changed.
 * @param {Array} nodes - Array of nodes.
 * @param {Array} edges - Array of edges.
 * @returns {Array} Nodes with synced targets, sorted by flow chain.
 */
export function buildFlowChain( nodes, edges = [] ) {
	const result = sortByTargetChain( syncTargetsFromEdges( nodes, edges ) );
	debug( 'buildFlowChain', { sameRef: result === nodes, sameData: isEqual( result, nodes ), result, nodes, edges } );
	return result;
}

/**
 * @param {{measured: {width,height}, position: {x,y}}} node
 * @return {{x1, y1, x2: *, y2: *}}
 */
const getBounds = ( node ) => {
	const w = node?.measured?.width || 180;
	const h = node?.measured?.height || 60;
	return {
		x1: ( node?.position?.x ?? 0 ),
		y1: ( node?.position?.y ?? 0 ),
		x2: ( node?.position?.x ?? 0 ) + w,
		y2: ( node?.position?.y ?? 0 ) + h,
	};
};

/**
 *
 * @param {{measured: {width,height}, position: {x,y}}} a
 * @param {{measured: {width,height}, position: {x,y}}} b
 * @param {Object} [options]
 * @param {number} [options.spacing=0] - Minimum spacing between nodes (in px).
 * @return {{x: (number|number), y: (number|number)}|null}
 */
const getOverlapAmount = ( a, b, { spacing = 100 } = {} ) => {
	const A = getBounds( a );
	const B = getBounds( b );

	const distanceX = Math.max( 0, Math.max(A.x1, B.x1) - Math.min(A.x2, B.x2) );
	const distanceY = Math.max( 0, Math.max(A.y1, B.y1) - Math.min(A.y2, B.y2) );

	if ( distanceX < spacing || distanceY < spacing ) {
		// If both axes overlap, return the minimum overlap amount
		return {
			x: distanceX < spacing ? spacing - distanceX : 0,
			y: distanceY < spacing ? spacing - distanceY : 0,
		};
	}
	return null;
};

/**
 * Checks if any nodes in the list overlap with each other.
 * @param {[{measured: {width,height}, position: {x,y}}]} nodes
 * @param {Object} [options]
 * @param {number} [options.spacing=0] - Minimum spacing between nodes (in px).
 * @returns {boolean} True if any overlap is found, false otherwise.
 */
function hasOverlaps( nodes, { spacing = 100 } = {} ) {
	for ( let i = 0; i < nodes.length; i++ ) {
		for ( let j = i + 1; j < nodes.length; j++ ) {
			if ( getOverlapAmount( nodes[i], nodes[j], { spacing: spacing } ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Adjusts node positions to prevent overlap, preserving data order.
 * Sorts by position for algorithm correctness, but returns in original order.
 * Early-exits with unchanged nodes if no shifts occurred.
 *
 * @param {Array} nodes - The array of nodes with `position`, `width`, and `height`.
 * @param {Object} options
 * @param {'vertical'|'horizontal'} [options.direction='vertical'] - Layout direction: top-bottom or left-right.
 * @param {number} [options.spacing=100] - Minimum spacing between nodes (in px).
 * @returns {Array} New array of nodes with adjusted positions in original order.
 */
function resolveOverlaps( nodes, { direction = 'vertical', spacing = 100 } = {} ) {
	// Early exit — nodes not yet measured by React Flow
	if ( nodes.some( ( node ) => ! node.measured ) ) {
		return nodes;
	}

	// @todo Remove as redundant performance-wise?
	if ( ! hasOverlaps( nodes, { spacing } ) ) {
		return nodes;
	}

	const isVertical = direction === 'vertical';
	const axis = isVertical ? 'y' : 'x';

	// Create index map to restore original order later
	const indexMap = nodes.map( (node, idx) => [idx, { ...node }] );

	// Sort by position for algorithm to work correctly (top-to-bottom)
	const sorted = indexMap.sort( ( [, a], [, b] ) => {
		const primaryA = a?.position?.[axis] ?? 0;
		const primaryB = b?.position?.[axis] ?? 0;
		const secondaryAxis = isVertical ? 'x' : 'y';
		const secondaryA = a?.position?.[secondaryAxis] ?? 0;
		const secondaryB = b?.position?.[secondaryAxis] ?? 0;

		if ( primaryA !== primaryB ) {
			return primaryA - primaryB;
		}
		return secondaryA - secondaryB;
	} );

	const shiftNode = ( node, amount ) => ({
			...node,
			position: {
				...node?.position ?? {},
				x: ( node?.position?.x ?? 0 ) + ( amount.x ?? 0 ),
				y: ( node?.position?.y ?? 0 ) + ( amount.y ?? 0 ),
			},
	});

	// Resolve overlaps on sorted nodes
	let hasChanges = false;
	let changed = true;
	while ( changed ) {
		changed = false;
		for ( let i = 0; i < sorted.length; i ++ ) {
			// Check for overlaps with all previous nodes (now that they're sorted)
			for ( let j = 0; j < i; j++ ) {
				const a = sorted[ j ][1];
				const b = sorted[ i ][1];

				const overlapAmount = getOverlapAmount( a, b, { spacing, direction } );
				if ( overlapAmount?.x && overlapAmount?.y ) {
					sorted[ i ][1] = shiftNode( b, { [axis]: overlapAmount[axis] } );
					hasChanges = true;
					changed = true;
				}
			}
		}
	}

	// Early exit — nothing moved
	if ( ! hasChanges ) {
		return nodes;
	}

	// Restore original order but with updated positions.
	return nodes.map( ( node, originalIdx ) => {
		const updatedNode = sorted.find( ( [ idx ] ) => idx === originalIdx );
		return updatedNode ? updatedNode[ 1 ] : node;
	} );
}
