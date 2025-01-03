import React from 'react';
import { useTranslation } from 'react-i18next';
import { Button, OverlayTrigger, Stack, Tooltip } from 'react-bootstrap';
import { DateValue } from '../../views/Blocks/Value';
import Icon from '../../partials/Icon';
import useDateFormatter from '../../../hooks/useDateFormatter';
import useToggle from '../../../hooks/useToggle';
import TraceLog from './TraceLog';
import Traces from './Traces';

export default function TraceNode( props ) {
	const { t } = useTranslation();
	const dateFormatter = useDateFormatter();

	const {
		item = {},
	} = props;

	const {
		trace = {},
		_timestamp: timestamp,
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

	return (
		<div className="position-relative">
			<Stack direction="horizontal" gap={3} className="mb-2 justify-content-between">
				<Stack direction="horizontal" gap={3} className="flex-wrap text-secondary small">
				{ start &&
					<small title={ t( 'Start' ) }>
						<Icon icon="trace-start" className="me-2" />
						<DateValue value={ start } />
					</small>
				}
				{ end &&
					<small title={ t( 'End' ) }>
						<Icon icon="trace-end" className="me-2" />
						<DateValue value={ end } />
					</small>
				}
				</Stack>
				{ toggle }
			</Stack>
			{ item.message && <small className="mb-2">{ item.message }</small> }
			{ raw ? <TraceLog data={ item } /> : <Traces data={ trace } /> }
		</div>
	);
}
