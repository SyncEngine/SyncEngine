import React from 'react';
import Badge from '../../partials/Badge';
import AccordionSticky from '../../partials/AccordionSticky';
import Trace from './Trace';
import { Stack } from 'react-bootstrap';

export default function Traces( props ) {
	const {
		data = [],
		ancestors = [],
		accordionProps = {},
	} = props;

	return (
		<AccordionSticky className="w-100 accordion-sm" { ...accordionProps } alwaysOpen>
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
						<AccordionSticky.Item eventKey={ index } key={ index + item._key } ref={ item._ref }>
							<AccordionSticky.Header>
								<Stack direction="horizontal" className="flex-wrap" gap={2}>
									<Badge subtle>{ count }x</Badge>
									{ title && <small>{ title }</small> }
									{ type && <Badge subtle>{ type }</Badge> }
									{ ( ref && ref !== title ) && <Badge subtle>{ ref }</Badge> }
									{ end &&
									  <Badge subtle>{ Math.round( end - start ) }ms</Badge>
									}
								</Stack>
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
