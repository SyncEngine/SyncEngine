import React from 'react';
import { Tooltip, OverlayTrigger, Stack } from 'react-bootstrap';
import CopyToClipboard from '../../partials/CopyToClipboard';

export default function Ref( props ) {
	const {
		item,
	} = props;

	return (
		<OverlayTrigger overlay={ <Tooltip id={ item.ref }>{ item.ref }</Tooltip> }>
			<Stack direction="horizontal" gap={1}>
				<span className="bi-c">{ item.id }</span>
				<CopyToClipboard
					className="icon-btn small"
					value={ item.ref }
				/>
			</Stack>
		</OverlayTrigger>
	)
}
