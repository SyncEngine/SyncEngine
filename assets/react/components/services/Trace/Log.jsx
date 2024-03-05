import React from 'react';
import Code from '../../fields/Code';

export default function TraceLog( props ) {
	return (
		<Code value={ JSON.stringify( props.data, null, 2 ) } language="json" readOnly />
	);
}
