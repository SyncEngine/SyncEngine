import React from 'react';
import Placeholder from 'react-bootstrap/Placeholder';

export default function LoadingPlaceholder() {
	return (
		<Placeholder animation="glow" className="w-100 d-flex">
			<Placeholder xs={12} size="lg" />
		</Placeholder>
	);
}
