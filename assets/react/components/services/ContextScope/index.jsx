import React from 'react';
import { useTranslation } from 'react-i18next';
import { ListGroup } from 'react-bootstrap';
import Toggle from '../../fields/Toggle';
import Collapsible from '../Collapsible';
import { deepClone, objectToMappable } from '../../../utils/data';

function parseContextScope( context ) {
	return deepClone( context.scope ?? [] );
}

export default function ContextScope( props ) {
	const { t } = useTranslation();
	//const app = useGlobal();
	const {
		context = {},
		toolbar,
		enableCallback,
		disableCallback,
	} = props;

	const currentContext = parseContextScope( context );
	//const globalScope = app.context.scope ?? [];

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

	//const [ automations, automationCallbacks ] = useEntities( 'automation' );
	//const [ flows, flowCallbacks ] = useEntities( 'flow' );
	//const [ steps, stepCallbacks ] = useEntities( 'step' );

	/*useEffect( () => {
		app.context.scope = [];
		// Fetch related.
		if ( automation ) {
			app.context.scope.push(
				automationCallbacks.get( automation )
			)
		}
		if ( flow ) {
			app.context.scope.push(
				flowCallbacks.get( flow )
			)
		}
		if ( step ) {
			app.context.scope.push(
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
		<Collapsible
			trigger={ ( attr, open ) => <Toggle { ...attr } value={ true === open } label={ t('Use current context') } /> }
			openCallback={ enableCallback }
			closeCallback={ disableCallback }
		>
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
