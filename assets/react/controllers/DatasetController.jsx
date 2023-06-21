import React, { useEffect, useState } from 'react';
import Params from "../components/fields/Params";
import { Tab, TabContent, Tabs } from "react-bootstrap";
import DatasetField from "../components/fields/Dataset/DatasetController";

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
			return ( <DatasetField value={ value } onChange={ onChange }></DatasetField> );
		case 'config':
			// Todo
			return;
	}
}
