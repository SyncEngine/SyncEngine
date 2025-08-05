import React, { useCallback, useEffect, useState } from 'react';
import {
	addEdge,
	Background,
	BaseEdge,
	Controls,
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

export default ( props ) => (
	<ReactFlowProvider>
		<Flow { ...props } />
	</ReactFlowProvider>
);

function parseValue( value, defaults ) {
	return value.map( ( node, index ) => {
		if ( ! node.hasOwnProperty( '_ref' ) ) {
			node._ref = createRefId();
		}

		node = {
			data: node,
		}

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
				id: `${ node.id }-${ node.target }`,
				source: node.id,
				target: node.target,
				animated: true,
				markerEnd: {
					type: MarkerType.Arrow,
				},
			}
		) );
}

function Flow( props ) {
	const app = useGlobal();
	const {
		onChange,
	} = props;

	const value = parseValue( objectToMappable( props.value || [] ) );

	const [ theme ] = useState( app.theme.getTheme() );
	const [ nodes, setNodes, onNodesChange ] = useNodesState( value );
	const [ edges, setEdges, onEdgesChange ] = useEdgesState( parseEdges( value ) );
	const { getNodes, getEdges } = useReactFlow();

	const handleUpdate = React.useRef(
		debounce( ( nodes, onChange ) => {
			if ( isEmpty( nodes ) ) {
				return;
			}
			onChange( nodes.map( node => {
				const parsedNode = deepClone( { ...node.data, target: node.target, type: node.type, position: node.position } );
				delete node.onChange;
				return parsedNode;
			} ) );
		}, 100 )
	).current;

	const onConnect = useCallback(
		( params ) => setEdges( ( edgesSnapshot ) => addEdge( { ...params, animated: true }, edgesSnapshot ) ),
		[],
	);

	const isValidConnection = useCallback(
		( connection ) => {
			// https://reactflow.dev/examples/interaction/prevent-cycles
			// we are using getNodes and getEdges helpers here
			// to make sure we create isValidConnection function only once
			const nodes = getNodes();
			const edges = getEdges();
			const target = nodes.find( ( node ) => node.id === connection.target );
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
		handleUpdate( nodes, onChange );
	}, [ nodes, onChange ] );

	const snapGrid = undefined;//[ 20, 20 ];
	const edgeTypes = { step: StepEdge, };
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
}
