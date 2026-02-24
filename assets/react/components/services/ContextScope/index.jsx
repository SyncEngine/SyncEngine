import React from 'react';
import { useTranslation } from 'react-i18next';
import { ListGroup } from 'react-bootstrap';
import Toggle from '../../fields/Toggle';
import Collapsible from '../Collapsible';
import { deepClone, objectToMappable } from '../../../utils/data';
import FlowContextItem from './FlowContextItem';

function parseContextScope( scope ) {
	return deepClone( scope || [] );
}

export default function ContextScope( props ) {
	const { t } = useTranslation();
	//const app = useGlobal();
	const {
		context = {},
		scope = context?.scope,
		toolbar,
		enableCallback,
		disableCallback,
	} = props;

	const currentScope = parseContextScope( scope );
	//const globalScope = app.context.scope ?? [];

	if ( ! currentScope.length ) {
		return;
	}

	/**
	 * @todo Create context selector (Automation, Flow, Routine) and make sure the options reflect the other options.
	 *       For example, if a flow is selected, you can only select Automations and Routines that are related to this Flow.
	 */

	/*const [ automation, setAutomation ] = useState( originalContext.automation );
	const [ flow, setFlow ] = useState( originalContext.flow );
	const [ routine, setRoutine ] = useState( originalContext.routine );*/

	//const [ automations, automationCallbacks ] = useEntities( 'automation' );
	//const [ flows, flowCallbacks ] = useEntities( 'flow' );
	//const [ routines, routineCallbacks ] = useEntities( 'routine' );

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
		if ( routine ) {
			app.context.scope.push(
				routineCallbacks.get( routine )
			)
		}
	}, [ automation, flow, routine ] );*/

	/*const reset = () => {
		setAutomation( null );
		setFlow( null );
		setRoutine( null );
	}*/

	return (
		<Collapsible
			trigger={ ( attr, open ) => <Toggle { ...attr } value={ true === open } label={ t('Use current context') } /> }
			openCallback={ enableCallback }
			closeCallback={ disableCallback }
		>
			<ListGroup gap={2} className="sequence my-3">
				{
					objectToMappable( currentScope ).map( ( item, index ) => {
						if ( 'flow' === item._entity?.toLowerCase() ) {
							return <ListGroup.Item key={ index }><FlowContextItem item={ item } /></ListGroup.Item>
						}
						return <ListGroup.Item key={ index }><b>{ item._entity }:</b> { item.name }</ListGroup.Item>
					} )
				}
			</ListGroup>
			{ toolbar }
		</Collapsible>
	);
}
