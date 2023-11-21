import React from 'react';
import { useTranslation } from 'react-i18next';
import { ListGroup } from 'react-bootstrap';
import useEntities from '../../../hooks/useEntities';
import Toggle from '../../fields/Toggle';
import Collapsible from '../Collapsible';
import { objectToMappable } from '../../../utils/data';

export default function ContextScope( props ) {
	const { t } = useTranslation();
	const {
		context = {},
		toolbar,
	} = props;

	const parseContextScope = ( context ) => {
		return structuredClone( context.scope ?? [] );
	}

	const currentContext = parseContextScope( context );
	//const globalScope = window.app.context.scope ?? [];

	if ( ! currentContext.length ) {
		return;
	}

	/**
	 * @todo Create context selector (Automation, Flow, Step) and make sure the options reflect the other options.
	 *       For example, if a flow is selected, you can only select Automations and Steps that are related to this Flow.
	 */

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
		<Collapsible trigger={ ( attr, open ) => <Toggle { ...attr } value={ true === open } label={ t('Use current context') } /> }>
			<ListGroup gap={2}>
				{
					objectToMappable( context.scope ).map( ( item, index ) => {
						return <ListGroup.Item key={ index }><b>{ item._entity }:</b> { item.name }</ListGroup.Item>
					} )
				}
			</ListGroup>
			{ toolbar }
		</Collapsible>
	);
}
