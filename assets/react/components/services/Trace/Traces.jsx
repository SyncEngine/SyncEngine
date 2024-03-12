import React from 'react';
import Badge from '../../partials/Badge';
import AccordionSticky from '../../partials/AccordionSticky';
import Trace from './Trace';

export default function Traces( props ) {
	const {
		data = [],
		ancestors = [],
		accordionProps = {},
	} = props;

	return (
		<AccordionSticky className="w-100" { ...accordionProps }>
			{
				data.map( ( item, index ) => {

					const {
						title,
						type,
						ref,
						count = 1,
						_timestamp: timestamp,
					} = item;

					ancestors.push( item );

					let start = timestamp[0] ?? timestamp;
					let end = timestamp[1] ?? null;

					return (
						<AccordionSticky.Item eventKey={ index } key={ item._key } ref={ item._ref }>
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
								<Trace item={ item } ancestors={ ancestors } />
							</AccordionSticky.Body>
						</AccordionSticky.Item>
					);
				} )
			}
		</AccordionSticky>
	);
}
