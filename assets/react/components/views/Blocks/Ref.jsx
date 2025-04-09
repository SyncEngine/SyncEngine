import React from 'react';
import { OverlayTrigger, Stack, Tooltip } from 'react-bootstrap';
import CopyToClipboard from '../../services/CopyToClipboard';

export default function Ref( props ) {
	const {
		item,
	} = props;

	return (
		<OverlayTrigger overlay={ <Tooltip id={ item.ref }>{ item.ref }</Tooltip> }>
			<Stack direction="horizontal" gap={1}>
				<span>{ item.id }</span>
				<CopyToClipboard
					className="icon-btn small"
					value={ item.ref }
				/>
			</Stack>
		</OverlayTrigger>
	)
}
