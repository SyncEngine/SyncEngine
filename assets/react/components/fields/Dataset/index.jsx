import React, { useState } from 'react';
import Params from "../Params";
import { Tab, TabContent, Tabs } from "react-bootstrap";

export default function Dataset( props ) {

	const {
		value = {},
		onChange,
	} = props;

	const [ dataset, setDataset ] = useState( value );

	const columns = dataset.columns ?? [];
	const data = dataset.data ?? [];

	const updateDataset = ( newDataset ) => {
		setDataset( newDataset );
		onChange( newDataset );
	}

	const updateColumns = ( newColumns ) => {
		updateDataset( { ...dataset, columns: newColumns } );
	}

	const updateData = ( newData ) => {
		updateDataset( { ...dataset, data: newData } );
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
				<TabContent className="p-1 border bg-body-tertiary">
					<div className="bg-body p-3">
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
					</div>
				</TabContent>
			</Tab>
			<Tab eventKey="data" title="Data">
				<TabContent className="p-1 border bg-body-tertiary">
					<div className="bg-body p-3">
						<Params
							value={ data }
							onChange={ updateData }
							columns={ getColumns() }
						/>
					</div>
				</TabContent>
			</Tab>
		</Tabs>
	);
}
