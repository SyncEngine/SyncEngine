import React from 'react';
import { objectToMappable } from '../../../utils/data';
import TraceLog from './Log';
import Badge from '../../partials/Badge';
import AccordionSticky from '../../partials/AccordionSticky';

export default function Trace( props ) {
	const {
		data,
	} = props;

	return (
		<AccordionSticky className="w-100">
			{
				objectToMappable( data, '_key' ).map( ( step, index ) => {

					const {
						info,
						count = 1,
						trace = {},
					} = step;

					const title = step.message ?? ( 'string' === typeof info ? info : step._key );

					return (
						<AccordionSticky.Item eventKey={ step._key } key={ step._key }>
							<AccordionSticky.Header>{ title }<Badge className="ms-2" subtle>{ count }x</Badge></AccordionSticky.Header>
							<AccordionSticky.Body>
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
