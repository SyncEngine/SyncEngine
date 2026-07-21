import React, { forwardRef } from 'react';
import PropTypes from 'prop-types';
import { Stack } from 'react-bootstrap';

const HStack = forwardRef( function HStack( props, ref ) {
	return <Stack { ...props } direction="horizontal" ref={ ref } />
} );

HStack.propTypes = {
	children: PropTypes.node,
	gap: PropTypes.number,
	align: PropTypes.string,
	wrap: PropTypes.bool,
};

export default HStack;
