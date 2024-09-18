import React, { createContext } from 'react';
import { Card, Stack } from 'react-bootstrap';
import OverlayToggle from '../OverlayToggle';
import TraceLog from './Log';
import Badge from '../../partials/Badge';
import Traces from './Traces';
import { useTranslation } from 'react-i18next';
import useDateFormatter from '../../../hooks/useDateFormatter';
import { sleep } from '../../../utils/globals';
import Icon from '../../partials/Icon';

export const TraceIteratorContext = createContext( {} );

export default function TraceContainer( props ) {
	const { t } = useTranslation();
	const dateFormatter = useDateFormatter();

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

	return (
		<TraceIteratorContext.Provider value={ iterator }>
			<Card className="w-100 overflow-y-auto">
				<Card.Header>
					<Stack direction="horizontal" gap={2} className="flex-wrap">
						{ iterator &&
						  <OverlayToggle overlay={ <div><TraceLog data={ iterator } /></div> } trigger={ [ 'hover' ] }>
							  <Badge>
								  <Icon icon="trace-info" className="me-2"/>
								  Iteration { iterator.current }
								  <Icon icon="trace-iteration" className="mx-2"/>
								  { iterator.offset } - { iterator.offset + iterator.limit }
							  </Badge>
						  </OverlayToggle>
						}
						{ time_start &&
						  <Badge><Icon icon="trace-start" className="me-2" />{ dateFormatter.format( time_start * 1000 ) }</Badge>
						}
						{ time_end &&
						  <Badge><Icon icon="trace-end" className="me-2" />{ dateFormatter.format( time_end * 1000 ) }</Badge>
						}
						{ ( time_start && time_end ) &&
						  <Badge><Icon icon="trace-duration" className="me-2" />{ Math.round( ( time_end - time_start ) * 1000 ) }ms</Badge>
						}
					</Stack>
				</Card.Header>
				<Card.Body>
					<Traces data={ trace } accordionProps={ { defaultActiveKey: 0 } } />
				</Card.Body>
				{ ( errors.length > 0 ) &&
				  <Card.Body>
					  <Card.Title>{ t('Errors') }</Card.Title>
					  <Traces data={ errors } find={ openTrace } />
				  </Card.Body>
				}
				{ ( logs.length > 0 ) &&
				  <Card.Body>
					  <Card.Title>{ t('Logs') }</Card.Title>
					  <Traces data={ logs } find={ openTrace } />
				  </Card.Body>
				}
			</Card>
		</TraceIteratorContext.Provider>
	);
}
