import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import useEntities from '../../../hooks/useEntities';
import Toggle from '../../fields/Toggle';
import { ListGroup } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';

export default function Context( props ) {
	const { t } = useTranslation();
	const {
		context = {},
	} = props;

	const parseContextScope = ( context ) => {
		return structuredClone( context.scope ?? [] );
	}

	const currentContext = parseContextScope( context );
	//const globalScope = window.app.context.scope ?? [];

	const [ useCurrent, setUseCurrent ] = useState( currentContext.length ?? false );

	/*const [ automation, setAutomation ] = useState( originalContext.automation );
	const [ flow, setFlow ] = useState( originalContext.flow );
	const [ step, setStep ] = useState( originalContext.step );*/

	const [ automations, automationCallbacks ] = useEntities( 'automation' );
	const [ flows, flowCallbacks ] = useEntities( 'flow' );
	const [ steps, stepCallbacks ] = useEntities( 'step' );

	/*useEffect( () => {
		window.app.context.scope = [];
		// Fetch related.
		if ( automation ) {
			window.app.context.scope.push(
				automationCallbacks.get( automation )
			)
		}
		if ( flow ) {
			window.app.context.scope.push(
				flowCallbacks.get( flow )
			)
		}
		if ( step ) {
			window.app.context.scope.push(
				stepCallbacks.get( step )
			)
		}
	}, [ automation, flow, step ] );*/

	/*const reset = () => {
		setAutomation( null );
		setFlow( null );
		setStep( null );
	}*/

	return (
		<>
			<Toggle onChange={ setUseCurrent } value={ useCurrent } label={ t('Use current context') } />
			{ useCurrent &&
				<ListGroup gap={2}>
					{
						objectToMappable( currentContext ).map( ( item, index ) => {
							return <ListGroup.Item key={ index }>{ item._entity }: { item.name }</ListGroup.Item>
						} )
					}
				</ListGroup>
				/*:
				<Stack gap={2}>
					<Entity entity="automation" choices={ automations } value={ automation } />
					<Entity entity="flow" choices={ flows } value={ flow } />
					<Entity entity="step" choices={ steps } value={ step } />
				</Stack>*/
			}
		</>
	);
}
