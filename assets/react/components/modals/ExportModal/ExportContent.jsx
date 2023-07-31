import React, { useCallback, useState } from 'react';
import { Button, ButtonGroup, OverlayTrigger, Tooltip } from 'react-bootstrap';

export default function ExportModalContent( props ) {
	const [ formatted, setFormatted ] = useState( false );
	const [ copied, setCopied ] = useState( false );

	const handleCopy = useCallback( () => {
		setCopied( true );
		navigator.clipboard.writeText( JSON.stringify(
			props.data,
			null,
			(
				(
					formatted
				) ? 2 : null
			)
		) );

		setTimeout( () => {
			setCopied( false );
		}, 1000 )
	}, [ props.data ] );

	const styles = {
		marginTop: '-1px',
		whiteSpace: 'pre-wrap',
		wordWrap: 'break-word',
	};

	return <>
		<div className="text-end">
			<ButtonGroup className="justify-content-end">
				<OverlayTrigger overlay={ <Tooltip id="export-format">{ ( formatted ) ? 'Compact' : 'Format' }</Tooltip> } trigger="hover">
					<Button variant={ ( formatted ) ? 'secondary' : 'outline-secondary ' } onClick={() => { setFormatted( !formatted ) }}>
						{ formatted && <span className="bi bi-code" /> }
						{ ! formatted && <span className="bi bi-chevron-expand" /> }
					</Button>
				</OverlayTrigger>
				<OverlayTrigger overlay={ <Tooltip id="export-copy">Copy</Tooltip> } trigger="hover">
					<Button variant={ ( copied ) ? 'secondary' : 'outline-secondary' } onClick={ handleCopy }>
						{ copied && <span className="bi bi-check" /> }
						{ ! copied && <span className="bi bi-clipboard" /> }
					</Button>
				</OverlayTrigger>
			</ButtonGroup>
		</div>
		<pre className="small p-3 bg-body-tertiary border" style={ styles }>
			{
				JSON.stringify(
					props.data,
					null,
					(
						(
							formatted
						) ? 2 : null
					)
				)
			}
		</pre>
	</>
}
