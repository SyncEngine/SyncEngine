import React, { useCallback } from 'react';
import Entity from '../Entity';
import { Handle, Position } from '@xyflow/react';

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
			<Handle type="target" position={ Position.Top } />
			<div onClick={ e => e.stopPropagation() }>
				<Entity entity={ stepEntity } value={ data[ stepEntity ] } onChange={ handleChange } />
			</div>
			<Handle type="source" position={ Position.Bottom } />
		</>
	)
}
