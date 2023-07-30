import React from 'react';
import Placeholder from 'react-bootstrap/Placeholder';

export default function LoadingPlaceholder() {
	return (
		<Placeholder animation="glow">
			<Placeholder xs={12} size="lg" />
		</Placeholder>
	);
}
