import React, { useEffect, useState } from 'react';
import { Tooltip, OverlayTrigger, Stack } from 'react-bootstrap';
import useClipboard from '../../../hooks/useClipboard';

export default function Ref( props ) {
	const [ clipboard, setClipboard ] = useClipboard( 'ref' );
	const [ copied, setCopied ] = useState( false );

	useEffect( () => {
		if ( copied ) {
			setTimeout( () => { setCopied( false ) }, 500 )
		}
	}, [ copied ] );

	const {
		item,
	} = props;

	let btnClass = 'btn btn-sm p-0 scale-110-hover transition-all transition-fast bi ';
	btnClass += ( copied ? 'bi-check' : ( item.ref === clipboard ) ? 'bi-clipboard-check' : 'bi-copy' );

	return (
		<OverlayTrigger overlay={ <Tooltip id={ item.ref }>{ item.ref }</Tooltip> }>
			<Stack direction="horizontal" gap={1}>
				<span className="bi-c">{ item.id }</span>
				<small
					className={ btnClass }
					onClick={ () => { setClipboard( item.ref ); setCopied( true ) } }
				/>
			</Stack>
		</OverlayTrigger>
	)
}
