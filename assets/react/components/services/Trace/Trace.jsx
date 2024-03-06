import React from 'react';
import Badge from '../../partials/Badge';
import AccordionSticky from '../../partials/AccordionSticky';
import TraceLog from './Log';
import { objectToMappable } from '../../../utils/data';

export default function Trace( props ) {
	const {
		data,
	} = props;

	return (
		<AccordionSticky className="w-100">
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

					let start = time_enter && new Date( time_enter * 1000 );
					let end = time_leave && new Date( time_leave * 1000 );
					if ( isLog && ! start ) {
						start = new Date( step._key.split( ' ' )[1] * 1000 );
					}


					return (
						<AccordionSticky.Item eventKey={ step._key } key={ step._key }>
							<AccordionSticky.Header>
								{ title }
								<Badge className="ms-2" subtle>{ count }x</Badge>
							</AccordionSticky.Header>
							<AccordionSticky.Body>
								{ start &&
								    <small className="d-block mb-1">
									    { start.toLocaleString() }
									    { end && ' - ' + end.toLocaleString() }
								    </small>
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
