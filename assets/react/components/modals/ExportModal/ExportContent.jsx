import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, ButtonGroup, OverlayTrigger, Tooltip } from 'react-bootstrap';
import Code from '../../fields/Code';
import useClipboard from '../../../hooks/useClipboard';
import useToggle from '../../../hooks/useToggle';
import Icon from '../../partials/Icon';

export default function ExportModalContent( props ) {
	const { t } = useTranslation();
	const [ formatted, toggleFormatted ] = useToggle( false );
	const [ copied, setCopied ] = useState( false );
	const [ clipboard, updateClipboard ] = useClipboard( '', '', false );

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
				<OverlayTrigger
					key={ formatted ? 'formatted' : 'compact' }
					overlay={ <Tooltip id="export-format">{ ( formatted ) ? t('Compact') : t('Format') }</Tooltip> }
				>
					<Button variant={ ( formatted ) ? 'secondary' : 'outline-secondary ' } onClick={ toggleFormatted }>
						<Icon icon={ formatted ? "code" : "code-formatted" } />
					</Button>
				</OverlayTrigger>
				{ null !== clipboard &&
				    <OverlayTrigger overlay={ <Tooltip id="export-copy">{ t('Copy') }</Tooltip> }>
					    <Button variant={ ( copied ) ? 'secondary' : 'outline-secondary' } onClick={ handleCopy }>
						    <Icon icon={ copied ? "check" : "clipboard" }/>
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
