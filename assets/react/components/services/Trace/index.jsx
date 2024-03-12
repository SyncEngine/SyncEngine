import React, { useCallback, useRef, useState } from 'react';
import { Card, Stack, Tab, Tabs } from 'react-bootstrap';
import useDateFormatter from '../../../hooks/useDateFormatter';
import Badge from '../../partials/Badge';
import OverlayToggle from '../OverlayToggle';
import Trace from './Trace';
import TraceLog from './Log';
import { objectToMappable } from '../../../utils/data';
import { useTranslation } from 'react-i18next';

export default function TraceControl( props ) {
	const { t } = useTranslation();
	const dateFormatter = useDateFormatter();
	const [ selected, setSelected ] = useState();

	const {
		data,
	} = props;

	const parent = useRef();

	const parseTrace = useCallback( ( traceData, callbacks ) => {
		traceData = objectToMappable( traceData, '_key', 'message' ).map( ( step ) => {

			step._ref = useRef();

			step._isLog = step._key.startsWith( 'Log:' );
			step._isError = step._key.startsWith( 'Error:' );

			if ( step._isLog || step._isError ) {
				step._timestamp = step._key.split(':')[1].trim() * 1000;
			} else {
				if ( ! step.time_leave || step.time_leave === step.time_enter ) {
					step._timestamp = step.time_enter * 1000;
				} else {
					step._timestamp = [
						step.time_enter * 1000,
						step.time_leave * 1000
					]
				}
			}

			// Parse recursively.
			step.trace = parseTrace( step.trace, callbacks );

			if ( step._isLog && callbacks.hasOwnProperty( 'addLog' ) ) {
				step._isLog && callbacks.addLog( step );
			}
			if ( step._isError && callbacks.hasOwnProperty( 'addError' ) ) {
				step._isError && callbacks.addError( step );
			}

			return step;
		} );

		return traceData;
	}, [] );

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


						const errors = [];
						const logs = [];

						const addLog = ( log ) => {
							logs.push( log );
						}
						const addError = ( error ) => {
							errors.push( error );
						}

						const traceData = parseTrace( trace, {
							addLog: addLog,
							addError: addError
						}  );

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
										<Trace data={ traceData } accordionProps={ { defaultActiveKey: 0 } } />
									</Card.Body>
									{ ( logs.length > 0 ) &&
										<Card.Body>
											<Card.Title>{ t('Logs') }</Card.Title>
											<Trace data={ logs } />
										</Card.Body>
									}
									{ ( errors.length > 0 ) &&
										<Card.Body>
											<Card.Title>{ t('Errors') }</Card.Title>
											<Trace data={ errors } />
										</Card.Body>
									}
								</Card>
							</Tab>
						)
					} )
				}
			</Tabs>
		</Stack>
	);
}
