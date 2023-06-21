import React, { useEffect, useState } from 'react';
import Dataset from "../components/fields/Dataset";

export default function DatasetController( props ) {

	const {
		args = {},
		value = {},
		onChange,
	} = props;

	const {
		prop,
	} = args;

	switch ( prop ) {
		case 'data':
			return ( <Dataset value={ value } onChange={ onChange }></Dataset> );
		case 'config':
			// Todo
			return;
	}
}
