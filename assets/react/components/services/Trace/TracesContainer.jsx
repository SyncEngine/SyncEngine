import React, { createContext } from 'react';
import { Card, Stack } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import { DateValue, DurationValue } from '../../views/Blocks/Value';
import { sleep } from '../../../utils/globals';
import useDateFormatter from '../../../hooks/useDateFormatter';
import OverlayToggle from '../OverlayToggle';
import Badge from '../../partials/Badge';
import Icon from '../../partials/Icon';
import TraceLog from './TraceLog';
import Traces from './Traces';
import Toggle from '../../fields/Toggle';
import useToggle from '../../../hooks/useToggle';
import useSettings from '../../../hooks/useSettings';

export const TraceIteratorContext = createContext( {} );

export default function TracesContainer( props ) {
	const { t } = useTranslation();
	const dateFormatter = useDateFormatter();
	const [ groupDiagnostigsPreference, setGroupDiagnosticsPreference ] = useSettings( 'local', 'trace', 'groupDiagnostics', true, true );
	const [ groupDiagnostics, toggleGroupDiagnostics ] = useToggle( groupDiagnostigsPreference, setGroupDiagnosticsPreference, setGroupDiagnosticsPreference );

	const {
		time_start,
		time_end,
		iterator,
		trace = {},
		logs = [],
		errors = [],
	} = props;

	const openTrace = async ( target ) => {

		const items = [ ...target._ancestors, target ];

		for ( const index in items ) {
			const item = items[ index ];
			if ( item._ref.current ) {
				const element = item._ref.current;
				const trigger = element.querySelector( '.accordion-button' );
				if ( trigger.classList.contains( 'collapsed' ) ) {
					trigger.click();
				}
				await sleep( 500 );
			}
		}
	}

	let diagnostics;
	if ( groupDiagnostics ) {
		diagnostics = (
			<>
				{ ( errors.length > 0 ) &&
				  <Card.Body>
					  <Card.Title>{ t('Errors') }</Card.Title>
					  <Traces data={ errors.sort( ( a, b ) => a._timestamp - b._timestamp ) } find={ openTrace } />
				  </Card.Body>
				}
				{ ( logs.length > 0 ) &&
				  <Card.Body>
					  <Card.Title>{ t('Logs') }</Card.Title>
					  <Traces data={ logs.sort( ( a, b ) => a._timestamp - b._timestamp ) } find={ openTrace } />
				  </Card.Body>
				}
			</>
		)
	} else if ( errors.length > 0 || logs.length ) {
		diagnostics = (
			<Card.Body>
				<Traces data={ [ ...logs, ...errors ].sort( ( a, b ) => a._timestamp - b._timestamp ) } find={ openTrace } />
			</Card.Body>
		)
	}

	return (
		<TraceIteratorContext.Provider value={ iterator }>
			<Card className="w-100 overflow-y-auto">
				<Card.Header>
					<Stack direction="horizontal" gap={2} className="flex-wrap">
						{ iterator &&
							<OverlayToggle overlay={ <div><TraceLog data={ iterator } /></div> } trigger={ [ 'hover' ] }>
								<Badge>
									<Icon icon="trace-info" className="me-1"/>
									Iteration { iterator.current }
									{ iterator.limit &&
									    <>
											<Icon icon="trace-iteration" className="mx-1"/>
											{ iterator.offset } - { iterator.offset + iterator.limit }
									    </>
									}
								</Badge>
							</OverlayToggle>
						}
						{ time_start &&
						  <Badge><Icon icon="trace-start" className="me-1" /><DateValue value={ time_start } /></Badge>
						}
						{ time_end &&
						  <Badge><Icon icon="trace-end" className="me-1" /><DateValue value={ time_end } /></Badge>
						}
						{ ( time_start && time_end ) &&
						  <Badge><Icon icon="trace-duration" className="me-1" /><DurationValue value={ Math.round( ( time_end - time_start ) * 1000 ) } ms={ true } /></Badge>
						}
					</Stack>
				</Card.Header>
				<Card.Body>
					<Traces data={ trace } accordionProps={ { defaultActiveKey: 0 } } />
				</Card.Body>
				<Card.Footer>
					<Toggle
						type="switch"
						label={ t('Grouped diagnostics') }
						value={ groupDiagnostics }
						onChange={ toggleGroupDiagnostics }
					/>
				</Card.Footer>
				{ diagnostics }
			</Card>
		</TraceIteratorContext.Provider>
	);
}
