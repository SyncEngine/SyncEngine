import React from 'react';
import {
	BaseEdge,
	EdgeLabelRenderer,
	getBezierPath,
	useReactFlow,
} from '@xyflow/react';
import Button from '../../partials/Button';
import Icon from '../../partials/Icon';
import { ButtonGroup } from 'react-bootstrap';

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
		setEdges((edges) => edges.filter((edge) => edge.id !== id));
	};
	const onEdgeStepAdd = () => {
		// @todo
	}

	return (
		<>
			<BaseEdge path={edgePath} markerEnd={markerEnd} style={style} />
			<EdgeLabelRenderer>
				<div
					className="nodrag nopan position-absolute transform-origin-center"
					style={{
						transform: `translate(-50%, -50%) translate(${labelX}px,${labelY}px)`,
					}}
				>
					<ButtonGroup>
						<Button variant="secondary" onClick={onEdgeRemove}>
							<Icon icon="trash" />
						</Button>
						<Button variant="secondary" onClick={onEdgeStepAdd}>
							<Icon icon="add" />
						</Button>
					</ButtonGroup>
				</div>
			</EdgeLabelRenderer>
		</>
	);
}
