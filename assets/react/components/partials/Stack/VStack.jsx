import React, { forwardRef } from 'react';
import PropTypes from 'prop-types';
import { Stack } from 'react-bootstrap';

const VStack = forwardRef( function VStack( props, ref ) {
	return <Stack { ...props } direction="vertical" ref={ ref } />
} );

VStack.propTypes = {
	children: PropTypes.node,
	gap: PropTypes.number,
	align: PropTypes.string,
	wrap: PropTypes.bool,
};

export default VStack;
