import React, { useCallback } from 'react';
import Entity from '../Entity';
import { Position } from '@xyflow/react';
import LimitedHandle from './LimitedHandle';

export default function StepNode( props ) {

	const {
		entity: stepEntity = 'routine',
		data = {},
	} = props;

	const {
		onChange
	} = data;

	const handleChange = useCallback( newValue => {
		onChange( props.id, { ...data, [ stepEntity ]: newValue } );
	}, [ onChange, data, stepEntity ] );

	return (
		<>
			<LimitedHandle limit={1} type="target" position={ Position.Top } />
			<div className="p-2 bg-body border border-1 border-input" onClick={ e => e.stopPropagation() }>
				<h5 className="mb-0">Step: { data._ref }</h5>
				<Entity className="nodrag" entity={ stepEntity } value={ data[ stepEntity ] } onChange={ handleChange } />
			</div>
			<LimitedHandle limit={1} type="source" position={ Position.Bottom } />
		</>
	)
}
