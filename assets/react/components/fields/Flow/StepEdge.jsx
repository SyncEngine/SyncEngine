import React, { useContext } from 'react';
import {
	BaseEdge,
	EdgeLabelRenderer,
	getBezierPath,
	useReactFlow,
} from '@xyflow/react';
import Button from '../../partials/Button';
import Icon from '../../partials/Icon';
import { createRefId } from '../../../utils/globals';
import { ButtonGroup } from 'react-bootstrap';
import { FlowContext, parseEdge, parseNode } from './index';

export default function StepEdge({
	id,
	sourceX,
	sourceY,
	targetX,
	targetY,
	sourcePosition,
	targetPosition,
	style = {},
	markerEnd,
}) {
	const _FlowContext = useContext( FlowContext );
	const [edgePath, labelX, labelY] = getBezierPath({
		sourceX,
		sourceY,
		sourcePosition,
		targetX,
		targetY,
		targetPosition,
	});

	const { setEdges, setNodes } = useReactFlow();

	const onEdgeRemove = () => {
		setEdges( ( edges ) => edges.filter( ( edge ) => edge.id !== id ) );
	};

	const onEdgeStepAdd = () => {
		const newNodeId = createRefId();
		const posX = ( sourceX + targetX ) / 2 - 75; // adjust offsets as needed
		const posY = ( sourceY + targetY ) / 2 - 25;

		const newNode = parseNode( {
			id: newNodeId,
			position: { x: posX, y: posY },
		}, _FlowContext?.nodeDefaults );

		// add the new node
		setNodes( ( nodes ) => nodes.concat( newNode ) );

		// replace the current edge with two edges that go through the new node
		setEdges((edges) => {
			const current = edges.find((e) => e.id === id);
			const remaining = edges.filter((e) => e.id !== id);
			const e1 = parseEdge( { source: current.source, target: newNodeId } );
			const e2 = parseEdge( { source: newNodeId, target: current.target } );
			return [...remaining, e1, e2];
		});
	}

	return (
		<>
			<BaseEdge id={id} path={edgePath} markerEnd={markerEnd} style={style} />
			<EdgeLabelRenderer>
				<div
					className="nodrag nopan position-absolute transform-origin-center"
					style={{
						transform: `translate(-50%, -50%) translate(${labelX}px,${labelY}px)`,
						pointerEvents: 'all',
					}}
				>
					<ButtonGroup size="sm">
						<Button variant="secondary" subtle onClick={onEdgeRemove}>
							<Icon icon="trash" />
						</Button>
						<Button variant={ _FlowContext?.entity } subtle onClick={onEdgeStepAdd}>
							<Icon icon="add" />
						</Button>
					</ButtonGroup>
				</div>
			</EdgeLabelRenderer>
		</>
	);
}
