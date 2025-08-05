import React from 'react';
import { Handle, useNodeConnections } from '@xyflow/react';

const LimitedHandle = ( props ) => {
	const connections = useNodeConnections( {
		handleType: props.type,
	} );

	return (
		<Handle
			{ ...props }
			isConnectable={ connections.length < props.limit }
		/>
	);
};

export default LimitedHandle;
