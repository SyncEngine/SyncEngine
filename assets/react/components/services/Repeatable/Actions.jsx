import React, { cloneElement } from 'react';
import { useTranslation } from 'react-i18next';
import { FormCheck, Stack } from 'react-bootstrap';

import useBreakpoint from '../../../hooks/useBreakpoint';

import Collapsible from '../Collapsible';
import DeleteModal from '../../modals/DeleteModal';
import CopyToClipboard from '../../partials/CopyToClipboard';
import Icon from '../../partials/Icon';

export default function Actions( props ) {
	if ( ! props.actions ) {
		return;
	}

	const { t } = useTranslation();
	const isMediumDisplay = useBreakpoint( 'md' );

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
					case 'copy':
						actions.push(
							<CopyToClipboard
								key={ key }
								aria-label={ t('Copy') }
								title={ t('Copy') }
								value={ props.value }
								className="icon-btn"
								onClick={ ( e ) => {
									e.stopPropagation();
									action( props.value );
								} }
							/>
						)
						break;
					case 'delete':
						actions.push(
							<DeleteModal key={ key } callback={ () => action( props._ref ) } />
						);
						break;
					case 'disable':
						actions.push(
							<FormCheck
								key={ key }
								title={ t('Disable') }
								aria-label={ t('Disable') }
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

	if ( ! isMediumDisplay ) {
		return (
			<div className="position-relative">
				<Collapsible autoClose={ true } trigger={ <Icon icon="toolbar-menu" className="p-3" /> } dimension="width">
					<Stack gap={ 3 } className={ props.className + ' position-absolute top-0 end-100 bg-body p-2 mt-n2 me-0' } direction="horizontal">
						{ actions }
					</Stack>
				</Collapsible>
			</div>
		);
	}

	return (
		<Stack gap={ 3 } className={ props.className } direction="horizontal">
			{ actions }
		</Stack>
	);
}
