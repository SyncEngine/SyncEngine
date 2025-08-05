import React, { useCallback, useEffect, useState } from 'react';
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

import useGlobal from '../../../hooks/useGlobal';
import { createRefId } from '../../../utils/globals';
import { debounce } from '../../../utils/events';
import { deepClone, objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditions';

const edgeIdSeparator = '->';
const edgeDefaults = {
	animated: true,
	style: {
		strokeWidth: 3,
	},
	markerEnd: {
		type: MarkerType.ArrowClosed,
	},
}

export default ( props ) => (
	<ReactFlowProvider>
		<Flow { ...props } />
	</ReactFlowProvider>
);

function parseValue( value, defaults ) {
	return sortNodesByTarget( value.map( ( node, index ) => {
		if ( ! node.hasOwnProperty( '_ref' ) ) {
			node._ref = createRefId();
		}

		node = {
			data: node,
		};

		node.id = node.data._ref;
		node.type = node.data.type || 'step';
		node.position = node.position || { x: 0, y: 0 };

		const previousNode = value[ index - 1 ] ?? null;
		const nextNode = value[ index + 1 ] ?? null;

		/*if ( ! node.source && previousNode ) {
			node.source = previousNode._ref;
		}*/

		if ( ! node.target && nextNode ) {
			if ( ! nextNode.hasOwnProperty( '_ref' ) ) {
				nextNode._ref = createRefId();
			}
			node.target = nextNode._ref;
		}

		return { ...defaults, ...node };
	} );
}

function parseEdges( nodes ) {
	return nodes
		.filter( node => node.target )
		.map( node => (
			{
				id: `${ node.id }${ edgeIdSeparator }${ node.target }`,
				source: node.id,
				target: node.target,
				...edgeDefaults
			}
		) );
}

function Flow( props ) {
	const app = useGlobal();
	const {
		onChange,
		spacing = 100,
	} = props;

	const value = parseValue( objectToMappable( props.value || [] ) );

	const handleOverlaps = ( nodes ) => {
		return hasOverlaps( nodes, { spacing: spacing } ) ? resolveOverlaps( nodes, { spacing: spacing } ) : nodes
	}

	const [ theme ] = useState( app.theme.getTheme() );
	const [ nodes, setNodes, onNodesChange ] = useNodesState( handleOverlaps( value ) );
	const [ edges, setEdges, onEdgesChange ] = useEdgesState( parseEdges( value ) );
	const { getNodes, getEdges } = useReactFlow();

	const handleUpdate = React.useRef(
		debounce( ( nodes, edges, onChange ) => {
			if ( isEmpty( nodes ) ) {
				return;
			}
			// Validate edges and update node targets to match current edges
			const validEdges = edges.filter( edge => edge.target && edge.source );
			const validTargets = Object.fromEntries( validEdges.map( edge => [ edge.source, edge.target ] ) );

			onChange( sortNodesByTarget( nodes ).map( node => {
				const parsedNode = deepClone({
					...node.data,
					target: validTargets[ node.id ] || null,
					type: node.type,
					position: node.position,
				});
				delete node.onChange;
				return parsedNode;
			} ) );
		}, 100 ),
	).current;

	const onConnect = useCallback( ( params ) => {
		setEdges( ( edgesSnapshot ) => addEdge( { ...edgeDefaults, ...params }, edgesSnapshot ) );
	}, [ setEdges ] );

	const onNodeDragStop = useCallback( () => {
		setNodes(( nodes) => handleOverlaps( nodes ) );
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
						outgoers.map( ( { id: target } ) => ( {
							id: `${ source }${ edgeIdSeparator }${ target }`,
							source,
							target,
						} ) ),
					);

					remainingNodes = remainingNodes.filter( rn => rn.id !== node.id );

					return [ ...remainingEdges, ...createdEdges ];
				}, edges),
			);
		},
		[ nodes, edges ],
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

		setNodes( nodes.map( node => {
			node.data.onChange = onNodeChange;
			return node;
		} ) );
	}, [] );

	useEffect( () => {
		handleUpdate( nodes, edges, onChange );
	}, [ nodes, edges, onChange ] );

	const snapGrid = undefined;//[ 20, 20 ];
	const edgeTypes = { step: StepEdge };
	const nodeTypes = { step: StepNode };

	return (
		<div className="flow-container" style={ { width: '100%', height: 500 } }>
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
				isValidConnection={ isValidConnection }
				snapGrid={ snapGrid }
				snapToGrid
				fitView
			>
				<Background bgColor="transparent"/>
				<Controls/>
				<MiniMap/>
			</ReactFlow>
		</div>
	);
}

const StepEdge = ( { id, sourceX, sourceY, targetX, targetY } ) => {
	const centerY = ( targetY - sourceY ) / 2 + sourceY;

	const edgePath = `M ${ sourceX } ${ sourceY } L ${ sourceX } ${ centerY } L ${ targetX } ${ centerY } L ${ targetX } ${ targetY }`;

	return <BaseEdge id={ id } path={ edgePath }/>;
};

/**
 * Sorts nodes so that the first node (with no incoming target) is first,
 * and each subsequent node is the target of the previous node.
 * Any additional root nodes (with no incoming target) are appended at the end in order.
 * @param {Array} nodes - Array of nodes with `id` and `target` properties.
 * @returns {Array} Sorted array of nodes.
 */
function sortNodesByTarget( nodes ) {
	const nodeMap = Object.fromEntries( nodes.map( node => [ node.id, node ] ) );
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
