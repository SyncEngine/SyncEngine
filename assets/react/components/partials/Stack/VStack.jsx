import React, { forwardRef } from 'react';
import { Stack } from 'react-bootstrap';

export default forwardRef( function VStack( props, ref ) {
	return <Stack { ...props } direction="vertical" ref={ ref } />
} );
