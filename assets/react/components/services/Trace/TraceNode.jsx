import React from 'react';
import { useTranslation } from 'react-i18next';
import { Button, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { DateValue } from '../../views/Blocks/Value';
import Icon from '../../partials/Icon';
import useDateFormatter from '../../../hooks/useDateFormatter';
import useToggle from '../../../hooks/useToggle';
import TraceLog from './TraceLog';
import Traces from './Traces';
import ProgressBar from '../../partials/Progress';
import { HStack, VStack } from '../../partials/Stack';

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

	let progress = ( item.progress && item.progress.percent ) ? item.progress : null;

	return (
		<VStack gap={ 2 } className="position-relative">
			{ ( progress && 100 > progress.percent ) && <ProgressBar variant="info" now={ progress.percent } label={ progress.label } animated /> }
			<HStack direction="horizontal" gap={3} className="justify-content-between">
				<HStack direction="horizontal" gap={3} className="flex-wrap text-secondary small">
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
				</HStack>
				{ toggle }
			</HStack>
			{ item.message && <small>{ item.message }</small> }
			{ raw ? <TraceLog data={ item } /> : <Traces data={ trace } /> }
		</VStack>
	);
}
