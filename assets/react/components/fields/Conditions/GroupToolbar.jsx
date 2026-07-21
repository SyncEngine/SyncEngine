import React from 'react';
import { string, number, bool, func } from 'prop-types';
import { useTranslation } from 'react-i18next';
import { ButtonGroup, Button } from 'react-bootstrap';

import Icon from '../../partials/Icon';
import HStack from '../../partials/Stack/HStack';
import { suppress } from '../../../utils/events';

export default function GroupToolbar( props ) {
	const { t } = useTranslation();

	const {
		index,
		operator,
		onOperatorChange,
		canMoveUp,
		canMoveDown,
		onMoveUp,
		onMoveDown,
		onDelete,
		onCopy,
	} = props;

	return (
		<div className="d-flex align-items-center justify-content-between gap-2 flex-wrap" onClick={ suppress }>
			<HStack gap={ 3 }>
				<ButtonGroup size="sm">
					<Button
						variant={ 'AND' === operator ? 'primary' : 'outline-secondary' }
						onClick={ () => onOperatorChange( 'AND' ) }
						size="sm"
					>
						AND
					</Button>
					<Button
						variant={ 'OR' === operator ? 'primary' : 'outline-secondary' }
						onClick={ () => onOperatorChange( 'OR' ) }
						size="sm"
					>
						OR
					</Button>
				</ButtonGroup>

				<Icon
					icon="arrow-up"
					title={ t( 'Move up' ) }
					role="button"
					className={ 'icon-link text-secondary-hover d-flex' }
					onClick={ canMoveUp ? onMoveUp : null }
					style={ canMoveUp ? {} : { opacity: 0.3, cursor: 'default' } }
				/>
				<Icon
					icon="arrow-down"
					title={ t( 'Move down' ) }
					role="button"
					className={ 'icon-link text-secondary-hover d-flex' }
					onClick={ canMoveDown ? onMoveDown : null }
					style={ canMoveDown ? {} : { opacity: 0.3, cursor: 'default' } }
				/>
				<Icon
					icon="copy"
					title={ t( 'Copy conditions' ) }
					role="button"
					className={ 'icon-link text-secondary-hover d-flex' }
					onClick={ onCopy }
				/>
				<Icon
					icon="remove"
					title={ t( 'Remove group' ) }
					role="button"
					className={ 'icon-link text-danger-hover d-flex' }
					onClick={ onDelete }
				/>

				{ props.children }
			</HStack>
		</div>
	);
}

GroupToolbar.propTypes = {
	index: number,
	operator: string,
	onOperatorChange: func,
	canMoveUp: bool,
	canMoveDown: bool,
	onMoveUp: func,
	onMoveDown: func,
	onDelete: func,
	onCopy: func,
};
