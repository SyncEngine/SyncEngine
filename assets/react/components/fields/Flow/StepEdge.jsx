import React, { useContext } from 'react';
import {
	BaseEdge,
	EdgeLabelRenderer,
	getBezierPath,
} from '@xyflow/react';
import Button from '../../partials/Button';
import Icon from '../../partials/Icon';
import { ButtonGroup } from 'react-bootstrap';
import { FlowContext } from './index';

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

	const onEdgeRemove = () => {
		// Goes through the parent's removeEdge callback (see FlowContext),
		// which is a plain setEdges - onEdgesChange still handles updating
		// nodes for this one since removing an edge alone doesn't need a
		// repositioning pass.
		_FlowContext?.callbacks?.removeEdge?.( id );
	};

	const onEdgeStepAdd = () => {
		const posX = ( sourceX + targetX ) / 2;
		const posY = ( sourceY + targetY ) / 2 - 25;

		// Goes through the parent's addNodeBetween callback (see
		// FlowContext) instead of calling useReactFlow().setNodes/setEdges
		// directly here. A direct call bypasses the parent's
		// onNodesChange/onEdgesChange entirely, so the new node would never
		// get laid out with the rest of the chain - it would land at this
		// raw midpoint, overlapping its neighbours, until some later
		// unrelated change happened to fix it.
		_FlowContext?.callbacks?.addNodeBetween?.( id, { x: posX, y: posY } );
	}

	return (
		<>
			<BaseEdge id={ id } path={ edgePath } markerEnd={ markerEnd } style={ style } selected={ selected } />
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
