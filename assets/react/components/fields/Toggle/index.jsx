import React from 'react';
import ToggleSingle from './Single';
import ToggleMulti from './Multi';

export default function Toggle( props ) {

	if ( props.choices ) {
		return <ToggleMulti { ...props } />
	}

	return <ToggleSingle { ...props } />
}
