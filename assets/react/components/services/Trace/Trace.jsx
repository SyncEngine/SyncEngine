import React from 'react';
import { Button, OverlayTrigger, Stack, Tooltip } from 'react-bootstrap';
import useDateFormatter from '../../../hooks/useDateFormatter';
import TraceLog from './Log';
import Traces from './Traces';
import { useTranslation } from 'react-i18next';
import useToggle from '../../../hooks/useToggle';
import Icon from '../../partials/Icon';
import Badge from '../../partials/Badge';
import { DurationValue } from '../../views/Blocks/Value';

export default function Trace( props ) {
	const { t } = useTranslation();
	const dateFormatter = useDateFormatter();

	const {
		item = {},
	} = props;

	const {
		trace = {},
		_timestamp: timestamp,
		_duration,
		_isLog: isLog,
		_isError: isError,
	} = item;

	const [ raw, toggleRaw ] = useToggle( isLog || isError );

	const toggle = ! ( isLog || isError ) && (
		<OverlayTrigger
			key={ raw ? 'raw' : 'display' }
			overlay={ <Tooltip id="export-format">{ raw ? t( 'Display' ) : t( 'Raw' ) }</Tooltip> }
		>
			<Button size="sm" variant={ raw ? 'secondary' : 'outline-secondary ' } onClick={ toggleRaw }>
				<Icon icon="code" />
			</Button>
		</OverlayTrigger>
	)

	let start = timestamp[0] ?? timestamp;
	let end = timestamp[1] ?? null;
	let duration = _duration || ( end && end - start );

	return (
		<div className="position-relative">
			<Stack direction="horizontal" gap={3} className="mb-2 justify-content-between">
				<Stack direction="horizontal" gap={3} className="flex-wrap text-secondary small">
				{ start &&
					<small title={ t( 'Start' ) }>
						<Icon icon="trace-start" className="me-2" />
						{ dateFormatter.format( start ) }
					</small>
				}
				{ end &&
					<small title={ t( 'End' ) }>
						<Icon icon="trace-end" className="me-2" />
						{ dateFormatter.format( end ) }
					</small>
				}
				{ duration &&
				    <Badge subtle>
					    <Icon icon="trace-duration" className="me-2" />
					    { ( item.count && 1 < item.count ) &&
					      <span>~<DurationValue value={ Math.round( ( duration / item.count ) * 1000 ) } ms={ true } /> / </span>
					    }
					    <DurationValue value={ Math.round( duration * 1000 ) } ms={ true } />
					</Badge>
				}
				</Stack>
				{ toggle }
			</Stack>
			{ item.message && <small className="mb-2">{ item.message }</small> }
			{ raw ? <TraceLog data={ item } /> : <Traces data={ trace } /> }
		</div>
	);
}
