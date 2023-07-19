import React, { useState } from 'react';
import { Button, ButtonGroup } from 'react-bootstrap';
import { BiAccessibility, BiBracket, BiCheck, BiClipboard, BiCode } from 'react-icons/bi';

export default function ExportModalContent( props ) {
	const [ formatted, setFormatted ] = useState( true );
	const [ copied, setCopied ] = useState( false );

	const handleCopy = () => {
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
	}

	return <>
		<div className="text-end">
			<ButtonGroup className="justify-content-end">
				<Button variant={ ( formatted ) ? 'primary' : 'secondary ' } onClick={() => { setFormatted( !formatted ) }}>
					{ formatted && <BiAccessibility/> }
					{ ! formatted && <BiCode/> }
				</Button>
				<Button variant={ ( copied ) ? 'primary' : 'secondary' } onClick={ handleCopy }>
					{ copied && <BiCheck /> }
					{ ! copied && <BiClipboard /> }
				</Button>
			</ButtonGroup>
		</div>
		<pre className="small p-3 bg-body-tertiary">
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
