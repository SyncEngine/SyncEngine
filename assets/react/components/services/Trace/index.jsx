import React, { useRef } from 'react';
import { Card, Stack, Tab, Tabs } from 'react-bootstrap';
import useDateFormatter from '../../../hooks/useDateFormatter';
import Badge from '../../partials/Badge';
import OverlayToggle from '../OverlayToggle';
import Trace from './Trace';
import TraceLog from './Log';
import { objectToMappable } from '../../../utils/data';

export default function TraceControl( props ) {
	const dateFormatter = useDateFormatter();

	const {
		data,
	} = props;

	const parent = useRef();

	return (
		<Stack className="pt-2 mw-100 overflow-hidden" ref={ parent }>
			<Tabs gap={2}>
				{
					objectToMappable( data ).map( ( row, index ) => {
						const {
							iterator = {},
							trace = {},
							time_start,
						} = row;

						return (
							<Tab key={ index } eventKey={ index } title={ iterator.current }>
								<Card className="w-100">
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
										</Stack>
									</Card.Header>
									<Card.Body>
										<Trace data={ trace } accordionProps={ { defaultActiveKey: 0 } } />
									</Card.Body>
								</Card>
							</Tab>
						)
					} )
				}
			</Tabs>
		</Stack>
	);
}
