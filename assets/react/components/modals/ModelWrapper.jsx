import React, { useCallback } from 'react';

export default function ModalWrapper( props ) {

	const prevent = useCallback( e => e.stopPropagation(), [] );

	return (
		<div
			className="d-none"
			onKeyDown={ prevent }
			onClick={ prevent }
			onFocus={ prevent }
			onMouseOver={ prevent }
		>
			{ props.children }
		</div>
	);
}
