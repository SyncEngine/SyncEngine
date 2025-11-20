import React, { useContext } from 'react';
import { useTranslation } from 'react-i18next';

import { TagsContext } from '../../../context/TagsContext';

import TagsList from './List';
import ModalToggle from '../ModalToggle';

import { isEmpty } from '../../../utils/conditions';
import Modal from '../../modals/Modal';
import { TAG_END_CHAR, TAG_SEPARATOR, TAG_START_CHAR } from '../../../utils/tags';

export default function Tags( props ) {
	const { t } = useTranslation();
	const {
		startChar = TAG_START_CHAR + ' ',
		endChar = ' ' + TAG_END_CHAR,
		separator = TAG_SEPARATOR,
		tags = useContext( TagsContext ),
		callback,
		trigger,
	} = props;

	if ( isEmpty( tags ) ) {
		return;
	}

	const selectTag = ( tag ) => {
		callback( startChar + tag + endChar );
	}

	const body = (
		<TagsList tags={ { ...tags, _storage: {} } } callback={ selectTag } startChar={ startChar } endChar={ endChar } separator={ separator } />
	);

	if ( trigger ) {
		return (
			<ModalToggle trigger={ trigger }>
				<Modal.Header closeButton>{ t('Select tag') }</Modal.Header>
				{ body }
			</ModalToggle>
		);
	}

	return body;
}
