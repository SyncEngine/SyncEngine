import React from 'react';
import { Card, Stack } from 'react-bootstrap';
import OverlayToggle from '../OverlayToggle';
import TraceLog from './Log';
import Badge from '../../partials/Badge';
import Traces from './Traces';
import { useTranslation } from 'react-i18next';
import useDateFormatter from '../../../hooks/useDateFormatter';

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

	return (
		<Card className="w-100 overflow-y-auto">
			<Card.Header>
				<Stack direction="horizontal" gap={2} className="flex-wrap">
					{ iterator &&
					  <OverlayToggle overlay={ <div><TraceLog data={ iterator } /></div> } trigger={ [ 'hover' ] }>
						  <Badge>
							  <span className="bi bi-info-circle me-2"/>
							  Iteration { iterator.current }
							  <span className="bi bi-hash mx-2" />
							  { iterator.offset } - { iterator.offset + iterator.limit }
						  </Badge>
					  </OverlayToggle>
					}
					{ time_start &&
					  <Badge><span className="bi bi-calendar me-2" />{ dateFormatter.format( time_start * 1000 ) }</Badge>
					}
					{ time_end &&
					  <Badge><span className="bi bi-calendar-check me-2" />{ dateFormatter.format( time_end * 1000 ) }</Badge>
					}
					{ ( time_start && time_end ) &&
					  <Badge><span className="bi bi-stopwatch me-2" />{ Math.round( ( time_end - time_start ) * 1000 ) }ms</Badge>
					}
				</Stack>
			</Card.Header>
			<Card.Body>
				<Traces data={ trace } accordionProps={ { defaultActiveKey: 0 } } />
			</Card.Body>
			{ ( errors.length > 0 ) &&
			  <Card.Body>
				  <Card.Title>{ t('Errors') }</Card.Title>
				  <Traces data={ errors } />
			  </Card.Body>
			}
			{ ( logs.length > 0 ) &&
			  <Card.Body>
				  <Card.Title>{ t('Logs') }</Card.Title>
				  <Traces data={ logs } />
			  </Card.Body>
			}
		</Card>
	);
}
