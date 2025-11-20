import React, { useContext } from 'react';
import { useTranslation } from 'react-i18next';

import { TagsContext } from '../../../context/TagsContext';

import TagsList from './List';
import ModalToggle from '../ModalToggle';

import { isEmpty } from '../../../utils/conditions';
import Modal from '../../modals/Modal';

export default function Tags( props ) {
	const { t } = useTranslation();
	const {
		startChar = '{{ ',
		endChar = ' }}',
		separator = '.',
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
