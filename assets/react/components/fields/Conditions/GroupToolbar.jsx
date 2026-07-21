import React from 'react';
import { string, number, bool, func } from 'prop-types';
import { useTranslation } from 'react-i18next';
import { ButtonGroup } from 'react-bootstrap';

import Icon from '../../partials/Icon';
import HStack from '../../partials/Stack/HStack';
import Button from '../../partials/Button';
import { suppress } from '../../../utils/events';

export function ConditionsOperatorToggle( props ) {
	const { operator, onOperatorChange } = props;
	return (
		<ButtonGroup size="sm">
			<Button
				onClick={ () => onOperatorChange( 'AND' ) }
				variant={ 'AND' === operator ? 'primary' : 'secondary' }
				subtle={ 'AND' === operator }
				outline size="sm"
			>
				AND
			</Button>
			<Button
				onClick={ () => onOperatorChange( 'OR' ) }
				variant={ 'OR' === operator ? 'primary' : 'secondary' }
				subtle={ 'OR' === operator }
				outline size="sm"
			>
				OR
			</Button>
		</ButtonGroup>
	)
}

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
		<div className="d-flex align-items-center justify-content-between gap-2 flex-wrap mx-3" onClick={ suppress }>
			<HStack gap={ 3 }>
				<ConditionsOperatorToggle operator={ operator } onOperatorChange={ onOperatorChange } />

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
