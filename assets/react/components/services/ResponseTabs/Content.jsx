import React from 'react';
import Code from '../../fields/Code';
import { deepClone } from '../../../utils/data';
import TraceControl from '../Trace';
import useToggle from '../../../hooks/useToggle';
import { Button, ButtonGroup, OverlayTrigger, Stack, Tooltip } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';

export default function ResponseTabContent( props ) {
	const { t } = useTranslation();
	const [ raw, toggleRaw ] = useToggle( false );

	const {
		name,
		content,
		contained,
	} = props;

	const json = 'object' === typeof content;

	let view = null;
	switch ( name.toLowerCase() ) {
		case 'trace':
			view = <TraceControl data={ deepClone( content ) } />
			break;
	}

	const toggle = view && (
		<div className="position-absolute top-0 end-0 mt-2 z-1">
			<ButtonGroup className="justify-content-end">
				<OverlayTrigger
					key={ raw ? 'raw' : 'display' }
					overlay={ <Tooltip id="export-format">{ raw ? t( 'Display' ) : t( 'Raw' ) }</Tooltip> }
				>
					<Button variant={ raw ? 'secondary' : 'outline-secondary ' } onClick={ toggleRaw }>
						<span className="bi bi-code" />
					</Button>
				</OverlayTrigger>
			</ButtonGroup>
		</div>
	)

	return (
		<Stack gap={2} className="mw-100 position-relative">
		{ toggle }
		{
			( raw || ! view )
			?
			<Code
				contained={ contained }
				height="100%"
				language={ json ? 'json' : undefined }
				editable={ false }
				value={ json ? JSON.stringify( content, null, 2 ) : content }
			/>
			:
			view
		}
		</Stack>
	);
}
