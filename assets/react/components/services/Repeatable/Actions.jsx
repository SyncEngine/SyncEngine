import React, { cloneElement } from 'react';
import { FormCheck, Stack } from "react-bootstrap";
import DeleteModal from "../../modals/DeleteModal";

export default function Actions( props ) {
	if ( ! props.actions ) {
		return;
	}

	let actions = [];

	if ( ! React.isValidElement( props.actions ) ) {
		if ( 'object' !== typeof props.actions ) {
			return;
		}

		// Move internal actions to last.
		if ( props.actions.hasOwnProperty( 'disable' ) ) {
			let action = props.actions.disable;
			delete props.actions.disable;
			props.actions.disable = action;
		}
		if ( props.actions.hasOwnProperty( 'delete' ) ) {
			let action = props.actions.delete;
			delete props.actions.delete;
			props.actions.delete = action;
		}

		for ( let key in props.actions ) {
			const action = props.actions[ key ];

			if ( React.isValidElement( action ) ) {
				actions.push( cloneElement( action, { key: key } ) );
				continue;
			}

			if ( 'function' === typeof action ) {
				switch ( key ) {
					case 'delete':
						actions.push(
							<DeleteModal key={ key } callback={ () => action( props._ref ) } />
						);
						break;
					case 'disable':
						actions.push(
							<FormCheck
								key={ key }
								aria-label="Disable"
								className="mt-n1 no-label"
								type="switch"
								defaultChecked={ ! ( props._disabled ?? false ) }
								onClick={ ( e ) => {
									e.stopPropagation();
									action( props._ref );
								} }
							/>
						)
						break;
				}
			}
		}
	}

	return (
		<Stack gap={ 3 } className={ props.className ?? {} } direction="horizontal">
			{ actions }
		</Stack>
	);
}
