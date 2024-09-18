import React, { useCallback, useState } from 'react';
import Code from '../../fields/Code';
import { deepClone } from '../../../utils/data';
import TraceControl from '../Trace';
import useToggle from '../../../hooks/useToggle';
import { Button, ButtonGroup, OverlayTrigger, Stack, Tooltip } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import useClipboard from '../../../hooks/useClipboard';
import Icon from '../../partials/Icon';


export default function ResponseTabContent( props ) {
	const { t } = useTranslation();
	const [ raw, toggleRaw ] = useToggle( false );
	const [ copied, setCopied ] = useState( false );
	const [ clipboard, updateClipboard ] = useClipboard( '', '', false );

	const {
		name,
		content,
		contained,
	} = props;

	let view = null;
	switch ( name.toLowerCase() ) {
		case 'trace':
			view = <TraceControl data={ deepClone( content ) } />
			break;
		case 'config':
			if ( content.hasOwnProperty( 'Raw' ) && content.hasOwnProperty( 'Parsed' ) ) {
				// @todo Separate component that syncs scroll.
				view = (
					<Stack gap={2} direction="horizontal">
						<Code
							contained={ contained }
							height="100%"
							language="json"
							editable={ false }
							value={ JSON.stringify( content.Raw, null, 2 ) }
						/>
						<Code
							contained={ contained }
							height="100%"
							language="json"
							editable={ false }
							value={ JSON.stringify( content.Parsed, null, 2 ) }
						/>
					</Stack>
				)
			}
			break;
	}


	const json = 'string' !== typeof content;
	const codeView = raw || ! view;

	const handleCopy = useCallback( () => {
		setCopied( true );
		updateClipboard( json ? JSON.stringify( content, null, 2 ) : content );

		setTimeout( () => {
			setCopied( false );
		}, 1000 )
	}, [ content ] );

	const toolbar = (
		<div className={ "position-absolute top-0 end-0 mt-2 z-1" + ( codeView ? ' me-4' : '' ) }>
			<ButtonGroup className="justify-content-end">
				{ view &&
					<OverlayTrigger
						key={ raw ? 'raw' : 'display' }
						overlay={ <Tooltip id="export-format">{ raw ? t( 'Display' ) : t( 'Raw' ) }</Tooltip> }
					>
						<Button size="sm" variant={ raw ? 'secondary' : 'outline-secondary ' } onClick={ toggleRaw }>
							<Icon icon="code" />
						</Button>
					</OverlayTrigger>
				}
				{ null !== clipboard &&
				    <OverlayTrigger overlay={ <Tooltip id="export-copy">{ t('Copy') }</Tooltip> }>
					    <Button size="sm" variant={ ( copied ) ? 'secondary' : 'outline-secondary' } onClick={ handleCopy }>
						    <Icon icon={ ( copied ? 'check' : 'clipboard' ) }/>
					    </Button>
				    </OverlayTrigger>
				}
			</ButtonGroup>
		</div>
	)

	return (
		<Stack gap={2} className="mw-100 position-relative">
		{ toolbar }
		{
			codeView
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
