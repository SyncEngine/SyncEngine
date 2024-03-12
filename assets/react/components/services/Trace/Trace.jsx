import React from 'react';
import { Stack } from 'react-bootstrap';
import Badge from '../../partials/Badge';
import useDateFormatter from '../../../hooks/useDateFormatter';
import AccordionSticky from '../../partials/AccordionSticky';
import TraceLog from './Log';
import { useTranslation } from 'react-i18next';

export default function Trace( props ) {
	const { t } = useTranslation();
	const dateFormatter = useDateFormatter();

	const {
		data = [],
		ancestors = [],
		accordionProps = {},
	} = props;

	return (
		<AccordionSticky className="w-100" { ...accordionProps }>
			{
				data.map( ( step, index ) => {

					const {
						title,
						info,
						name,
						type,
						ref,
						count = 1,
						trace = {},
						_timestamp: timestamp,
						_isLog: isLog,
						_isError: isError,
					} = step;

					ancestors.push( step );

					let start = timestamp[0] ?? timestamp;
					let end = timestamp[1] ?? null;

					return (
						<AccordionSticky.Item eventKey={ index } key={ step._key } ref={ step._ref }>
							<AccordionSticky.Header>
								{ title }
								{ type && <Badge className="ms-2" subtle>{ type }</Badge> }
								{ ref && <Badge className="ms-2" subtle>{ ref }</Badge> }
								<Badge className="ms-2" subtle>{ count }x</Badge>
								{ end &&
								  <Badge className="ms-2" subtle>{ Math.round( end - start ) }ms</Badge>
								}
							</AccordionSticky.Header>
							<AccordionSticky.Body>
								{ start &&
									<Stack direction="horizontal" gap={2} className="flex-wrap mb-2">
										<small title={ t( 'Start' ) }>
											<span className="bi bi-calendar me-2" />
											{ dateFormatter.format( start ) }
										</small>
										{ end &&
										    <small title={ t( 'End' ) }>
												<span className="bi bi-calendar-check-fill me-2" />
												{ dateFormatter.format( end ) }
											</small>
										}
									</Stack>
								}
								{ step.message && <small className="mb-2">{ step.message }</small> }
								{ ( isLog || isError ) ? <TraceLog data={ step } /> : <Trace data={ trace } ancestors={ ancestors } /> }
							</AccordionSticky.Body>
						</AccordionSticky.Item>
					);
				} )
			}
		</AccordionSticky>
	);
}
