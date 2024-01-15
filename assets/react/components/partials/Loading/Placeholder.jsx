import React, { forwardRef } from 'react';
import Placeholder from 'react-bootstrap/Placeholder';

export default forwardRef( function LoadingPlaceholder( props, ref ) {
	return (
		<Placeholder ref={ ref } animation="glow" className="w-100 d-flex" { ...props }>
			<Placeholder xs={12} size="lg" />
		</Placeholder>
	);
} )
