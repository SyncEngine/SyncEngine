import React from 'react';
import { array, bool, object, oneOfType, string } from 'prop-types';
import { InputGroup, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { objectToMappable } from '../../utils/data';
import Icon from '../partials/Icon';

export default function Help( {
	id,
	text,
	icon,
	inputGroup,
	className,
} ) {

	if ( ! text ) {
		return;
	}

	let button = React.isValidElement( icon ) ? icon : <Icon icon={ icon ?? 'help' } />;
	if ( inputGroup ) {
		button = <InputGroup.Text className={ className }>{ button }</InputGroup.Text>;
	} else {
		button = React.cloneElement( button, { className: className + ' mx-2' } );
	}

	let help = text;

	if ( 'object' === typeof text ) {
		help = (
			<ul className="text-start m-1 ms-n2">
				{
					objectToMappable( text, 'type', 'text' ).map( ( item, index ) => {
						return <li key={ index }>{ item.text }</li>
					} )
				}
			</ul>
		)
	}

	return (
		<OverlayTrigger overlay={ <Tooltip id={ id + '_help' }>{ help }</Tooltip> }>
			{ button }
		</OverlayTrigger>
	)
}

Help.propTypes = {
	id: string,
	text: oneOfType( [ string, array, object ] ),
	icon: oneOfType( [ string, object ] ),
	inputGroup: bool
}
