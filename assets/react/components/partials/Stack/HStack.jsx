import React, { forwardRef } from 'react';
import { Stack } from 'react-bootstrap';

export default forwardRef( function HStack( props, ref ) {
	return <Stack { ...props } direction="horizontal" ref={ ref } />
} );
