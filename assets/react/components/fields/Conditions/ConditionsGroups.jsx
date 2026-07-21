import React from 'react';
import { string, array, bool, func, object, oneOfType } from 'prop-types';
import { useTranslation } from 'react-i18next';

import { createRefId } from '../../../utils/globals';
import ConditionsGroup from './ConditionsGroup';
import Sortable from '../../services/Sortable';
import { HStack, VStack } from '../../partials/Stack';
import Button from '../../partials/Button';
import Icon from '../../partials/Icon';
import { ConditionsOperatorToggle } from './GroupToolbar';

export default function ConditionsGroups( props ) {
	const { t } = useTranslation();

	const {
		groups,
		operator,
		onGroupsChange,
		onOperatorChange,
		onConditionsChange,
		editable,
		source,
		conditionTypes,
		id,
		taggable,
		sortable,
		disabled,
	} = props;

	const addGroup = () => {
		const newGroups = [
			...groups,
			{ _ref: createRefId(), operator: 'AND', conditions: [] },
		];
		onGroupsChange( newGroups );
	};

	const deleteGroup = ( index ) => {
		if ( 1 >= groups.length ) {
			return;
		}
		const newGroups = [ ...groups ];
		newGroups.splice( index, 1 );
		onGroupsChange( newGroups );
	};

	const handleGroupChange = ( index, updatedGroup ) => {
		const newGroups = [ ...groups ];
		newGroups[ index ] = updatedGroup;
		onGroupsChange( newGroups );
	};

	const OperatorToggle = (
		<ConditionsOperatorToggle operator={ operator } onOperatorChange={ onOperatorChange } />
	);

	return (
		<VStack>
			{ ( editable && groups.length > 1 ) &&
				<HStack className="justify-content-center mb-3" gap={ 3 }>
					<span className="d-none text-uppercase small text-secondary fw-semibold" aria-hidden="true">
						{ t( 'Combine' ) }
					</span>
					{ OperatorToggle }
				</HStack>
			}

			<Sortable
				setItems={ onGroupsChange }
				items={ groups.map( ( group, index ) => ( {
					_ref: group._ref,
					_key: group._ref,
					value: group,
					component: ConditionsGroup,
					attributes: {
						group,
						groupIndex: index,
						totalGroups: groups.length,
						onGroupChange: handleGroupChange,
						onConditionsChange,
						onDeleteGroup: deleteGroup,
						onGroupsChange,
						groups,
						editable,
						disabled,
						sortable,
						taggable,
						source,
						conditionTypes,
						id,
						showSeparator: index < groups.length - 1,
						combineOperator: operator,
					},
					handle: 'custom',
				} ) ) }
			/>

			{ editable && (
				<HStack className="justify-content-center mt-3" gap={ 3 }>
					{ groups.length > 1 && OperatorToggle }
					<Button size="sm" variant="outline-secondary" onClick={ addGroup }>
						<Icon icon="plus" className="me-1" />
						{ t( 'Add conditions group' ) }
					</Button>
				</HStack>
			) }
		</VStack>
	);
}

ConditionsGroups.propTypes = {
	groups: array,
	operator: string,
	onGroupsChange: func,
	onOperatorChange: func,
	onConditionsChange: func,
	editable: bool,
	disabled: bool,
	sortable: bool,
	taggable: bool,
	source: object,
	conditionTypes: oneOfType( [ object, array ] ),
	id: oneOfType( [ string, object ] ),
};
