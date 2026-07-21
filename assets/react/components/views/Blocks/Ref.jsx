import React from 'react';
import PropTypes from 'prop-types';
import { OverlayTrigger, Stack, Tooltip } from 'react-bootstrap';
import CopyToClipboard from '../../services/CopyToClipboard';

export default function Ref( props ) {
	const {
		item,
		label = item.value ?? item.ref ?? item._ref,
		value = item.label ?? item.id,
	} = props;

	return (
		<OverlayTrigger overlay={ <Tooltip id={ value }>{ value }</Tooltip> }>
			<Stack direction="horizontal" gap={1}>
				<span>{ label }</span>
				<CopyToClipboard
					className="icon-btn small"
					value={ value }
				/>
			</Stack>
		</OverlayTrigger>
	)
}

Ref.propTypes = {
	item: PropTypes.object,
	label: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
	value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
};
