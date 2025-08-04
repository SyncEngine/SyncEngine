import React, { useCallback, useEffect, useState } from 'react';
import {
	addEdge,
	Background,
	BaseEdge,
	Controls,
	MarkerType,
	MiniMap,
	ReactFlow,
	useEdgesState,
	useNodesState,
} from '@xyflow/react';

import useGlobal from '../../../hooks/useGlobal';
import { createRefId } from '../../../utils/globals';
import { debounce } from '../../../utils/events';
import { deepClone, objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditions';

import '@xyflow/react/dist/style.css';

function parseValue( value ) {
	return value.map( ( node, index ) => {
		if ( ! node.hasOwnProperty( '_ref' ) ) {
			node._ref = createRefId();
		}

		node.id = node._ref;
		node.type = node.type || 'default';
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

		return node;
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

export default function Flow( props ) {
	const app = useGlobal();
	const {
		onChange,
	} = props;

	const value = parseValue( objectToMappable( props.value || [] ) );

	const [ theme ] = useState( app.theme.getTheme() );
	const [ nodes, setNodes, onNodesChange ] = useNodesState( value );
	const [ edges, setEdges, onEdgesChange ] = useEdgesState( parseEdges( value ) );

	const handleUpdate = React.useRef(
		debounce( ( nodes, onChange ) => {
			if ( isEmpty( nodes ) ) {
				return;
			}
			onChange( nodes.map( node => {
				const parsedNode = deepClone( { ...node.data, target: node.target, type: node.type, position: node.position } );
				return parsedNode;
			} ) );
		}, 100 )
	).current;

	const onConnect = useCallback(
		( params ) => setEdges( ( edgesSnapshot ) => addEdge( { ...params, animated: true }, edgesSnapshot ) ),
		[],
	);

	const edgeTypes = {
		step: StepEdge,
	};

	useEffect( () => {
		handleUpdate( nodes, onChange );
	}, [ nodes, onChange ] );

	const snapGrid = undefined;//[ 20, 20 ];

	return (
		<div className="flow-container" style={ { width: '100%', height: 500 } }>
			<ReactFlow
				className="bg-transparent"
				colorMode={ theme === 'dark' ? 'dark' : 'light' }
				nodes={ nodes }
				edges={ edges }
				onNodesChange={ onNodesChange }
				onEdgesChange={ onEdgesChange }
				onConnect={ onConnect }
				snapGrid={ snapGrid }
				snapToGrid
				fitView
			>
				<Background bgColor="transparent" />
				<Controls />
				<MiniMap />
			</ReactFlow>
		</div>
	);
}

function StepEdge({ id, sourceX, sourceY, targetX, targetY }) {
	const centerY = (targetY - sourceY) / 2 + sourceY;

	const edgePath = `M ${sourceX} ${sourceY} L ${sourceX} ${centerY} L ${targetX} ${centerY} L ${targetX} ${targetY}`;

	return <BaseEdge id={id} path={edgePath} />;
}
