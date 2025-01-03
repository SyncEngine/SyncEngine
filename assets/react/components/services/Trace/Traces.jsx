import React from 'react';
import { HStack, Stack } from '../../partials/Stack';
import { DurationValue, MemoryValue } from '../../views/Blocks/Value';
import Badge from '../../partials/Badge';
import Icon from '../../partials/Icon';
import AccordionSticky from '../../partials/AccordionSticky';
import TraceNode from './TraceNode';
import OverlayToggle from '../OverlayToggle';
import usePagination from '../../../hooks/usePagination';
import { suppress } from '../../../utils/events';
import { isString } from '../../../utils/conditions';

function iconType( type ) {
	return type.toLowerCase().replace( ':', '-' ).replace( ' ', '') ;
}

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
						return <TradeItem key={ index } item={ item } index={ index } find={ find } />;
					} )
				}
			</AccordionSticky>
			{ toolbar &&
				<div className="bg-body-tertiary p-2">{ toolbar }</div>
			}
		</Stack>
	);
}

function TradeItem( props ) {
	const {
		item,
		index,
		find,
	} = props;

	let disabled = item.config?._disabled;

	return (
			<AccordionSticky.Item eventKey={ index } key={ index + item._key } ref={ item._ref }>
				<AccordionSticky.Header className={ disabled && 'opacity-75 grayscale' }>
					<HStack className="justify-content-between w-100 me-2" gap={2}>
						{ item.log ?
							<TraceLogHeader item={ item } find={ find } />
								:
							<TraceNodeHeader item={ item } />
						}
					</HStack>
				</AccordionSticky.Header>
				<AccordionSticky.Collapse eventKey={ index } unmountOnExit>
					<AccordionSticky.Body>
						<TraceNode item={ item } />
					</AccordionSticky.Body>
				</AccordionSticky.Collapse>
			</AccordionSticky.Item>
	);
}

function TraceNodeHeader( props ) {
	const {
		item,
	} = props;

	let {
		title,
		type,
		ref,
		count = 1,
		_timestamp: timestamp,
		_duration,
	} = item;

	let start = timestamp[0] ?? timestamp;
	let end = timestamp[1] ?? null;
	let duration = _duration || ( end && end - start )

	let disabled = item.config?._disabled;
	let skipped  = item.config?._skipped;

	let variant = skipped ? 'warning' : undefined;

	return (
		<HStack className="flex-wrap" gap={2}>
			<Badge bg={ variant } subtle>{ count }x</Badge>
			{ disabled && <OverlayToggle overlay={ isString( disabled ) ? disabled : 'Disabled' }><Icon icon={ 'trace-disabled' } /></OverlayToggle> }
			{ skipped && <OverlayToggle overlay={ isString( skipped ) ? skipped : 'Skipped' }><Icon variant={ variant } icon={ 'trace-skipped' } /></OverlayToggle> }
			{ title && <small>{ title }</small> }
			{ type && <Badge bg={ variant } subtle><Icon icon={ iconType( type ) } className="me-1" />{ type }</Badge> }
			{ ( ref && ref !== title ) && <Badge bg={ variant } subtle><Icon icon="ref" className="me-1" />{ ref }</Badge> }
			{ duration &&
			  <Badge bg={ variant } subtle>
				  <Icon icon="trace-duration" className="me-1" />
				  { ( item.count && 1 < item.count ) &&
				    <span>~<DurationValue value={ Math.round( ( duration / item.count ) * 1000 ) } ms={ true } /> / </span>
				  }
				  <DurationValue value={ Math.round( duration * 1000 ) } ms={ true } />
			  </Badge>
			}
			{ item.memory_total &&
			  <Badge bg={ variant } subtle>
				  <Icon icon="memory" className="me-1" />
				  { ( item.count && 1 < item.count ) &&
				    <span>~<MemoryValue value={ Math.round( item.memory_total / item.count ) } initialView="K" /> | </span>
				  }
				  <span>^<MemoryValue value={ item.memory_peak  } initialView="K" /></span>
				  <span> | <MemoryValue value={ item.memory_enter  } initialView="M" /> > <MemoryValue value={ item.memory_leave } initialView="M" /></span>
			  </Badge>
			}
		</HStack>
	)
}

function TraceLogHeader( props ) {
	const {
		item,
		variant,
		find,
	} = props;

	const parent = item._ancestors[ item._ancestors.length - 1 ];

	const title = item.title;
	const count = item.count || 1;
	const type  = type || parent.type;
	const ref   = ref || parent.ref;

	return (
		<>
			<HStack className="flex-wrap" gap={2}>
				<Badge bg={ variant } subtle>{ count }x</Badge>
				{ title && <small>{ title }</small> }
				{ type && <Badge bg={ variant } subtle><Icon icon={ iconType( type ) } className="me-1" />{ type }</Badge> }
				{ ( ref && ref !== title ) && <Badge bg={ variant } subtle><Icon icon="ref" className="me-1" />{ ref }</Badge> }
			</HStack>
			{ ( 'function' === typeof find && item._ancestors.length ) &&
			  <Icon
					  icon="trace-follow"
					  onClick={ e => { suppress( e ); find( item ) } }
			  />
			}
		</>
	)
}
