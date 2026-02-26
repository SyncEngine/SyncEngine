import React from 'react';
import { HStack, VStack } from '../../partials/Stack';
import useEntities from '../../../hooks/useEntities';
import Icon from '../../partials/Icon';

export default function FlowContextItem( props ) {

	const {
		item
	} = props;

	const steps = item.config?.steps;

	const [ routines, entityCallbacks ] = useEntities( 'routine' );

	const scope = item._scope ?? {};

	let subs = [];

	if ( scope ) {
		for ( const index in steps ) {
			const step = steps[ index ];
			const number = parseInt( index, 10 ) + 1;

			if ( scope._ref === step._ref ) {
				break;
			}

			const routine = entityCallbacks.get( step.routine, true );
			subs.push(
				<div key={ index }>
					<Icon icon="arrow-return-right" />Step #{ number } | <b>{ routine?._entity ?? 'Routine' }:</b> { routine?.name }
				</div>
			);
		}
	}

	return (
		<>
			<b>{ item._entity }:</b> { item.name }
			{ subs && <VStack>{subs}</VStack>}
		</>
	);
}
