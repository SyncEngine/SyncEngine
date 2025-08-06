import React, { useCallback } from 'react';
import Entity from '../Entity';
import { Position } from '@xyflow/react';
import LimitedHandle from './LimitedHandle';

export default function StepNode( props ) {

	const {
		data = {},
	} = props;

	const {
		entity: entity = '',
		onChange
	} = data;

	const handleChange = useCallback( newValue => {
		const entityId = newValue.id;
		delete newValue.id;
		onChange( props.id, { ...data, [ entity ]: entityId, config: newValue } );
	}, [ onChange, data, entity ] );

	const actions = [ 'config', 'edit' ];
	if ( ! data[ entity ] ) {
		actions.push( 'create' );
	}

	console.log( data );

	return (
		<>
			<LimitedHandle limit={1} type="target" position={ Position.Top } />
			<div className="p-2 bg-body border border-1 border-input" onClick={ e => e.stopPropagation() }>
				<h5 className="mb-0">Step: { data._ref }</h5>
				<Entity className="nodrag" entity={ entity } value={ { id: data[ entity ], ...( data.config ?? {} ) } } onChange={ handleChange } config={ 'entity:_step.fields' } actions={ actions } />
			</div>
			<LimitedHandle limit={1} type="source" position={ Position.Bottom } />
		</>
	)
}
