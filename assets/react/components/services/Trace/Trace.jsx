import React from 'react';
import { Stack } from 'react-bootstrap';
import Badge from '../../partials/Badge';
import useDateFormatter from '../../../hooks/useDateFormatter';
import AccordionSticky from '../../partials/AccordionSticky';
import TraceLog from './Log';
import { objectToMappable } from '../../../utils/data';
import { useTranslation } from 'react-i18next';

export default function Trace( props ) {
	const { t } = useTranslation();
	const dateFormatter = useDateFormatter();

	const {
		data,
		accordionProps = {},
	} = props;

	return (
		<AccordionSticky className="w-100" { ...accordionProps }>
			{
				objectToMappable( data, '_key', 'message' ).map( ( step, index ) => {

					const {
						info,
						count = 1,
						trace = {},
						time_enter,
						time_leave,
					} = step;

					const isLog = ( step._key.startsWith( 'Log:' ) || step._key.startsWith( 'Error:' ) );
					const title = step.message ?? ( 'string' === typeof info ? info : step._key );

					let start = time_enter && time_enter * 1000;
					let end = time_leave && time_leave * 1000;
					if ( isLog && ! start ) {
						start = step._key.split( ' ' )[1] * 1000;
					}

					return (
						<AccordionSticky.Item eventKey={ index } key={ step._key }>
							<AccordionSticky.Header>
								{ title }
								<Badge className="ms-2" subtle>{ count }x</Badge>
							</AccordionSticky.Header>
							<AccordionSticky.Body>
								{ start &&
									<Stack direction="horizontal" gap={2} className="flex-wrap mb-2">
										<Badge bg="info" subtle title={ t( 'Start' ) }>
											<span className="bi bi-calendar me-2" />
											{ dateFormatter.format( start ) }
										</Badge>
										{ end &&
										    <Badge bg="info" subtle title={ t( 'End' ) }>
												<span className="bi bi-calendar-check-fill me-2" />
												{ dateFormatter.format( end ) }
											</Badge>
										}
									</Stack>
								}
								{ isLog ? <TraceLog data={ step } /> : <Trace data={ trace } /> }
							</AccordionSticky.Body>
						</AccordionSticky.Item>
					);
				} )
			}
		</AccordionSticky>
	);
}
