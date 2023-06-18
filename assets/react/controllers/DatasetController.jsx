import React, { useEffect, useState } from 'react';
import Params from "../components/fields/Params";
import { Tab, TabContent, Tabs } from "react-bootstrap";

export default function DatasetController( props ) {

	const {
		args = {},
		value = {},
		onChange,
	} = props;

	const {
	} = args;

	const [ config, setConfig ] = useState( value );

	const columns = config.columns ?? [];
	const data = config.data ?? [];

	/**
	 * Update parent value.
	 * This needs to be an effect since the state update is async.
	 */
	useEffect( () => {
		onChange( config );
	}, [ config ] );

	const updateColumns = ( newColumns ) => {
		setConfig( { ...config, columns: newColumns } );
	}

	const updateData = ( newData ) => {
		setConfig( { ...config, data: newData } );
	}

	const getColumns = () => {
		const obj = {};
		for ( let i = 0; i < columns.length; i++ ) {
			obj[ columns[ i ].key ] = { label: columns[ i ].name ?? columns[ i ].key };
		}
		return obj;
	}

	return (
		<Tabs className="mt-2">
			<Tab eventKey="columns" title="Columns">
				<TabContent className="p-2 border bg-body-tertiary">
					<Params
						value={ columns }
						onChange={ updateColumns }
						columns={ {
							key: {
								label: 'Key',
							},
							name: {
								label: 'Name',
							},
						} }
					/>
				</TabContent>
			</Tab>
			<Tab eventKey="data" title="Data">
				<TabContent className="p-2 border bg-body-tertiary">
					<Params
						value={ data }
						onChange={ updateData }
						columns={ getColumns() }
					/>
				</TabContent>
			</Tab>
		</Tabs>
	);
}
