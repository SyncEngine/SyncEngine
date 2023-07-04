import React from 'react';
import { FormCheck, Stack } from "react-bootstrap";
import ConfirmDelete from "../../modals/ConfirmDelete";
import { BiClipboard } from "react-icons/bi";

export default function Actions( props ) {
	if ( ! props.actions ) {
		return;
	}

	let actions = [];

	if ( ! React.isValidElement( props.actions ) ) {
		if ( 'object' !== typeof props.actions ) {
			return;
		}

		for ( let key in props.actions ) {
			const action = props.actions[ key ];

			if ( React.isValidElement( action ) ) {
				actions.push( action );
				continue;
			}

			if ( 'function' === typeof action ) {
				switch ( key ) {
					case 'delete':
						actions.push(
							<ConfirmDelete key={ key } callback={ () => action( props._ref ) } />
						);
						break;
					case 'disable':
						actions.push(
							<FormCheck
								key={ key }
								className="mt-n1"
								type="switch"
								defaultChecked={ ! ( props._disabled ?? false ) }
								onClick={ ( e ) => {
									e.stopPropagation();
									action( props._ref );
								} }
							/>
						)
						break;
					case 'test':
						actions.push(
							<BiClipboard key={ key } onClick={ () => action( props._ref ) } />
							// Alternative icons: Play Record
						);
						break;
				}
			}
		}
	}

	return (
		<Stack direction="horizontal">
			{ actions }
		</Stack>
	);
}
