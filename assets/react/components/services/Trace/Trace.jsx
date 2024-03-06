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

					const start = time_enter && new Date( time_enter * 1000 );
					const end = time_leave && new Date( time_leave * 1000 );

					const title = step.message ?? ( 'string' === typeof info ? info : step._key );

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
								{
									( step._key.startsWith( 'Log:' ) || step._key.startsWith( 'Error:' ) )
									?
									<TraceLog data={ step } />
									:
									<Trace data={ trace } />
								}
							</AccordionSticky.Body>
						</AccordionSticky.Item>
					);
				} )
			}
		</AccordionSticky>
	);
}
