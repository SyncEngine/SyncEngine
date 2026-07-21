import React from 'react';
import { string, array, bool, func, object, oneOfType } from 'prop-types';
import { useTranslation } from 'react-i18next';

import { createRefId } from '../../../utils/globals';
import ConditionsGroup from './ConditionsGroup';
import Sortable from '../../services/Sortable';

export default function ConditionsGroups( props ) {
	const { t } = useTranslation();

	const {
		groups,
		onGroupsChange,
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

	return (
		<>
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
					},
					handle: 'custom',
				} ) ) }
			/>

			{ editable && (
				<button
					type="button"
					className="btn btn-sm btn-outline-secondary mt-2"
					onClick={ addGroup }
				>
					{ t( 'Add conditions group' ) }
				</button>
			) }
		</>
	);
}

ConditionsGroups.propTypes = {
	groups: array,
	onGroupsChange: func,
	onConditionsChange: func,
	editable: bool,
	disabled: bool,
	sortable: bool,
	taggable: bool,
	source: object,
	conditionTypes: oneOfType( [ object, array ] ),
	id: oneOfType( [ string, object ] ),
};
