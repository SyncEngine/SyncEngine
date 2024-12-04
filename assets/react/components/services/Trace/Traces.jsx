import React from 'react';
import Badge from '../../partials/Badge';
import { HStack, Stack } from '../../partials/Stack';
import AccordionSticky from '../../partials/AccordionSticky';
import Trace from './Trace';
import usePagination from '../../../hooks/usePagination';
import Icon from '../../partials/Icon';
import { suppress } from '../../../utils/events';
import { DurationValue } from '../../views/Blocks/Value';

export default function Traces( props ) {
	const {
		data = [],
		accordionProps = {},
		find,
	} = props;

	const { items, toolbar } = usePagination( data );

	return (
		<Stack className={ props.className }>
			<AccordionSticky className="w-100 accordion-sm" { ...accordionProps } alwaysOpen>
				{
					items.map( ( item, index ) => {

						let {
							title,
							type,
							ref,
							count = 1,
							_timestamp: timestamp,
							_duration,
						} = item;

						if ( item.log ) {
							const parent = item._ancestors[ item._ancestors.length - 1 ];
							type = type || parent.type;
							ref  = ref || parent.ref;
						}

						let start = timestamp[0] ?? timestamp;
						let end = timestamp[1] ?? null;
						let duration = _duration || ( end && end - start );

						return (
							<AccordionSticky.Item eventKey={ index } key={ index + item._key } ref={ item._ref }>
								<AccordionSticky.Header>
									<HStack className="justify-content-between w-100 me-2" gap={2}>
										<HStack className="flex-wrap" gap={2}>
											<Badge subtle>{ count }x</Badge>
											{ title && <small>{ title }</small> }
											{ type && <Badge subtle>{ type }</Badge> }
											{ ( ref && ref !== title ) && <Badge subtle>{ ref }</Badge> }
											{ duration &&
												<Badge subtle><DurationValue value={ Math.round( duration * 1000 ) } ms={ true } /></Badge>
											}
										</HStack>
										{ ( 'function' === typeof find && item._ancestors.length ) &&
										    <Icon
												icon="trace-follow"
												onClick={ e => { suppress( e ); find( item ) } }
											/>
										}
									</HStack>
								</AccordionSticky.Header>
								<AccordionSticky.Collapse eventKey={ index } unmountOnExit>
									<AccordionSticky.Body>
										<Trace item={ item } />
									</AccordionSticky.Body>
								</AccordionSticky.Collapse>
							</AccordionSticky.Item>
						);
					} )
				}
			</AccordionSticky>
			{ toolbar &&
				<div className="bg-body-tertiary p-2">{ toolbar }</div>
			}
		</Stack>
	);
}
