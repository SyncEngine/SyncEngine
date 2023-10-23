import React, { useContext, useEffect, useState } from 'react';
import useEntities from '../../../hooks/useEntities';
import Entity from '../../fields/Entity';
import Toggle from '../../fields/Toggle';

export default function Context( props ) {
	const {
		context,
	} = props;

	const parseContext = ( context ) => {
		if ( context.execute ) {
			return structuredClone( context.execute );
		}
		if ( context.parentEntity && context.parentEntity._entity ) {
			return {
				[ context.parentEntity._entity.toLowerCase() ]: structuredClone( context.parentEntity._entity )
			}
		}
		return structuredClone( context );
	}

	const originalContext = parseContext( context );

	const [ useCurrent, setUseCurrent ] = useState( originalContext ?? false );

	const [ automation, setAutomation ] = useState( originalContext.automation );
	const [ flow, setFlow ] = useState( originalContext.flow );
	const [ step, setStep ] = useState( originalContext.step );

	const [ automations, automationCallbacks ] = useEntities( 'automation' );
	const [ flows, flowCallbacks ] = useEntities( 'flow' );
	const [ steps, stepCallbacks ] = useEntities( 'step' );

	useEffect( () => {
		if ( automation ) {
			// Fetch related flows and steps.

		}
	}, [ automation ] );

	useEffect( () => {
		if ( flow ) {
			// Fetch related automations and steps.
		}
	}, [ flow ] );

	useEffect( () => {
		if ( step ) {
			// Fetch related automations and flows.
		}
	}, [ step ] );

	const reset = () => {
		setAutomation( null );
		setFlow( null );
		setStep( null );
	}

	const toggleUseCurrent = bool => {
		if ( bool ) {
			setAutomation( originalContext.automation );
			setFlow( originalContext.flow );
			setStep( originalContext.step );
		}
		setUseCurrent( bool );
	}

	return (
		<>
			<Toggle onChange={ toggleUseCurrent } value={ useCurrent } title="Use current context" />
			<Entity entity="automation" choices={ automations } value={ automation } />
			<Entity entity="flow" choices={ flows } value={ flow } />
			<Entity entity="step" choices={ steps } value={ step } />
		</>
	);
}
