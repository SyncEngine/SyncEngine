import React from 'react';
import PropTypes from 'prop-types';
import Modal from '../../modals/Modal';


export default function DatasetEdit( props ) {

	const {
		value,
		callback,
	} = props;

	return (
		<Modal>

		</Modal>
	)
}

DatasetEdit.propTypes = {
	value: PropTypes.any,
	callback: PropTypes.func,
};
