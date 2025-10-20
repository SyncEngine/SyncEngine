import React, { useContext, useState } from 'react';
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
import useToggle from '../../../hooks/useToggle';

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
	selected,
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
		setNodes( ( nodes ) => _FlowContext?.callbacks?.handleOverlaps( nodes.concat( newNode ) ) );

		// replace the current edge with two edges that go through the new node
		setEdges(( edges ) => {
			const current = edges.find( ( edge ) => edge.id === id );
			return [
				...edges.filter( ( edge ) => edge.id !== id ),
				parseEdge( { source: current.source, target: newNodeId } ),
				parseEdge( { source: newNodeId, target: current.target } ),
			];
		});
	}

	return (
		<>
			<BaseEdge id={id} path={edgePath} markerEnd={markerEnd} style={style} selected={ selected } />
			<EdgeLabelRenderer>
				<div
					className="nodrag nopan position-absolute transform-origin-center"
					style={{
						transform: `translate(-50%, -50%) translate(${labelX}px,${labelY}px)`,
						pointerEvents: 'all',
					}}
				>
					<ButtonGroup size="sm">
						<Button variant={ _FlowContext?.entity } subtle onClick={onEdgeStepAdd}>
							<Icon icon="add" />
						</Button>
						{ selected &&
							<Button variant="secondary" subtle onClick={onEdgeRemove}>
								<Icon icon="trash" />
							</Button>
						}
					</ButtonGroup>
				</div>
			</EdgeLabelRenderer>
		</>
	);
}
