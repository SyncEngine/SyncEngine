import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, ButtonGroup, OverlayTrigger, Tooltip } from 'react-bootstrap';
import Code from '../../fields/Code';
import useClipboard from '../../../hooks/useClipboard';

export default function ExportModalContent( props ) {
	const { t } = useTranslation();
	const [ formatted, setFormatted ] = useState( false );
	const [ copied, setCopied ] = useState( false );
	const [ clipboard, updateClipboard ] = useClipboard( '' );

	const handleCopy = useCallback( () => {
		setCopied( true );
		updateClipboard( JSON.stringify(
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
		<div className="text-end position-sticky top-0 z-1">
			<ButtonGroup className="justify-content-end">
				<OverlayTrigger key={ formatted ? 'formatted' : 'compact' } overlay={ <Tooltip id="export-format">{ ( formatted ) ? t('Compact') : t('Format') }</Tooltip> }>
					<Button variant={ ( formatted ) ? 'secondary' : 'outline-secondary ' } onClick={() => { setFormatted( !formatted ) }}>
						{ formatted && <span className="bi bi-code" /> }
						{ ! formatted && <span className="bi bi-chevron-expand" /> }
					</Button>
				</OverlayTrigger>
				{ null !== clipboard &&
				    <OverlayTrigger overlay={ <Tooltip id="export-copy">{ t('Copy') }</Tooltip> }>
					    <Button variant={ ( copied ) ? 'secondary' : 'outline-secondary' } onClick={ handleCopy }>
						    { copied && <span className="bi bi-check" /> }
						    { ! copied && <span className="bi bi-clipboard" /> }
					    </Button>
				    </OverlayTrigger>
				}
			</ButtonGroup>
		</div>
		{ formatted
			?
			<Code language="json" value={ JSON.stringify( props.data, null, 2 ) }/>
			:
			<pre className="small p-3 bg-body-tertiary border" style={ styles }>{ JSON.stringify( props.data ) }</pre>
		}
	</>
}
