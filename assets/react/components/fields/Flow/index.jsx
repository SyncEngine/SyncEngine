import React, { createContext, useCallback, useEffect, useState } from 'react';
import {
	addEdge,
	Background,
	BaseEdge,
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
import { createRefId } from '../../../utils/globals';
import { debounce } from '../../../utils/events';
import { deepClone, objectMerge, objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditions';

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
	const nodes = sortNodesByTarget( value.map( ( node, index ) => {
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

function parseEdges( nodes ) {
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

	const onNodeChange = ( id, changes ) => {
		setNodes( ( nodes ) =>
			nodes.map( ( node ) => {
				if ( node.id === id ) {
					return { ...node, data: { ...node.data, ...changes } };
				}
				return node;
			} ),
		);
	};

	const nodeDefaults = { data: { entity: entity, onChange: onNodeChange, preview: preview } };

	const value = parseValue( objectToMappable( props.value || [] ), nodeDefaults );

	const handleOverlaps = ( nodes ) => {
		return sortNodesByTarget( hasOverlaps( nodes, { spacing: spacing } ) ? resolveOverlaps( nodes, { spacing: spacing } ) : nodes )
	}

	const [ theme ] = useState( app.theme.getTheme() );
	const [ nodes, setNodes, onNodesChange ] = useNodesState( handleOverlaps( value ) );
	const [ edges, setEdges, onEdgesChange ] = useEdgesState( parseEdges( value ) );
	const { getNodes, getEdges, screenToFlowPosition } = useReactFlow();

	const handleUpdate = React.useRef(
		debounce( ( nodes, edges, onChange ) => {
			if ( isEmpty( nodes ) ) {
				return;
			}
			// Validate edges and update node targets to match current edges
			const validEdges = edges.filter( edge => edge.target && edge.source );
			//const validTargets = Object.fromEntries( validEdges.map( edge => [ edge.source, edge.target ] ) );

			onChange(
				sortNodesByTarget( nodes, validEdges )
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
		setEdges( edgesSnapshot => addEdge( parseEdge( params ), edgesSnapshot ) );
		setNodes( nodes => sortNodesByTarget( nodes ) )
	}, [ setEdges ] );

	const onNodeDragStop = useCallback( () => {
		setNodes( nodes => handleOverlaps( nodes ) );
	}, [ setNodes, handleOverlaps ] );

	const onNodesDelete = useCallback(
		( deleted ) => {
			let remainingNodes = [ ...nodes ];
			setEdges(
				deleted.reduce( ( acc, node ) => {
					const incomers = getIncomers( node, remainingNodes, acc );
					const outgoers = getOutgoers( node, remainingNodes, acc );
					const connectedEdges = getConnectedEdges( [ node ], acc );

					const remainingEdges = acc.filter( edge => ! connectedEdges.includes( edge ) );

					const createdEdges = incomers.flatMap( ( { id: source } ) =>
						outgoers.map( ( { id: target } ) => parseEdge( { source, target } ) ),
					);

					remainingNodes = remainingNodes.filter( rn => rn.id !== node.id );

					return [ ...remainingEdges, ...createdEdges ];
				}, edges),
			);
		},
		[ nodes, edges ],
	);

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

				setNodes(( nds ) => nds.concat( newNode ) );
				setEdges(( eds ) =>
					eds.concat( parseEdge( { source: connectionState.fromNode.id, target: newNode.id } ) ),
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
					entity,
					preview,
					callbacks: { handleOverlaps }
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
					onNodeDragStop={ onNodeDragStop }
					onNodesDelete={ onNodesDelete }
					onEdgesChange={ onEdgesChange }
					onConnect={ onConnect }
					onConnectEnd={ onConnectEnd }
					isValidConnection={ isValidConnection }
					snapGrid={ snapGrid }
					snapToGrid
					fitView
				>
					<Background bgColor="transparent"/>
					<Controls/>
					<MiniMap/>
				</ReactFlow>
			</FlowContext.Provider>
		</div>
	);
}

/**
 * Sorts nodes so that the first node (with no incoming target) is first,
 * and each subsequent node is the target of the previous node.
 * Any additional root nodes (with no incoming target) are appended at the end in order.
 * @param {Array} nodes - Array of nodes with `id` and `target` properties.
 * @param {Array} edges - Array of edges with `source` and `target` properties.
 * @returns {Array} Sorted array of nodes.
 */
function sortNodesByTarget( nodes, edges = [] ) {
	const nodeMap = Object.fromEntries( nodes.map( node => {
		if ( edges && edges.length ) {
			node.target = edges.find( edge => edge.source === node.id )?.target ?? null;
			node.source = edges.find( edge => edge.target === node.id )?.source ?? null;
		}
		return [ node.id, node ]
	} ) );
	const incoming = {};
	nodes.forEach( node => {
		if ( node.target ) {
			incoming[ node.target ] = true;
		}
	} );
	// Find root nodes (no other node points to them)
	const roots = nodes.filter( node => ! incoming[ node.id ] );
	const visited = new Set();
	const result = [];
	const visitChain = node => {
		while ( node && ! visited.has( node.id ) ) {
			result.push( node );
			visited.add( node.id );
			node = node.target ? nodeMap[ node.target] : null;
		}
	};
	roots.forEach( root => visitChain( root ) );
	// Append any unvisited nodes (disconnected or cycles)
	nodes.forEach(node => {
		if ( ! visited.has( node.id ) ) {
			result.push( node );
			visited.add( node.id );
		}
	});
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
 * Adjusts node positions to prevent overlap, using a greedy recursive strategy.
 *
 * @param {Array} nodes - The array of nodes with `position`, `width`, and `height`.
 * @param {Object} options
 * @param {'vertical'|'horizontal'} [options.direction='vertical'] - Layout direction: top-bottom or left-right.
 * @param {number} [options.spacing=100] - Minimum spacing between nodes (in px).
 * @returns {Array} New array of nodes with adjusted positions.
 */
function resolveOverlaps( nodes, { direction = 'vertical', spacing = 100 } = {} ) {
	const isVertical = direction === 'vertical';
	const axis = isVertical ? 'y' : 'x';

	// Sort nodes by their leading position
	const resolved = [ ...nodes ].sort(
		( a, b ) => {
			const primaryA = a?.position?.[axis] ?? 0;
			const primaryB = b?.position?.[axis] ?? 0;
			const secondaryAxis = isVertical ? 'x' : 'y';
			const secondaryA = a?.position?.[secondaryAxis] ?? 0;
			const secondaryB = b?.position?.[secondaryAxis] ?? 0;

			if ( primaryA !== primaryB ) {
				return primaryA - primaryB;
			}
			return secondaryA - secondaryB;
		}
	);

	const shiftNode = ( node, amount ) => ({
			...node,
			position: {
				...node?.position ?? {},
				x: ( node?.position?.x ?? 0 ) + ( amount.x ?? 0 ),
				y: ( node?.position?.y ?? 0 ) + ( amount.y ?? 0 ),
			},
	});

	let changed = true;
	while ( changed ) {
		changed = false;
		for ( let i = 0; i < resolved.length; i ++ ) {
			// Check for overlaps with all previous nodes
			for ( let j = 0; j < i; j++ ) {
				const a = resolved[ j ];
				const b = resolved[ i ];

				const overlapAmount = getOverlapAmount( a, b, { spacing, direction } );
				if ( overlapAmount?.x && overlapAmount?.y ) {
					resolved[ i ] = shiftNode( b, { [axis]: overlapAmount[axis] } );
					changed = true;
				}
			}
		}
	}

	return resolved;
}
