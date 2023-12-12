import React from 'react';
import SelectSimple from './Simple';
import SelectAdvanced from './Advanced';

export default function Select( props ) {

	if ( props.customizable ) {
		return <SelectSimple { ...props } />
	}

	return <SelectAdvanced { ...props } />
}
