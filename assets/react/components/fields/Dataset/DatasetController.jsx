import React, { useEffect, useState } from 'react';
import Params from "../Params";
import { Tab, TabContent, Tabs } from "react-bootstrap";

export default function DatasetField( props ) {

	const {
		value = {},
		onChange,
	} = props;

	const [ dataset, setDataset ] = useState( value );

	const columns = dataset.columns ?? [];
	const data = dataset.data ?? [];

	const updateColumns = ( newColumns ) => {
		setDataset( { ...dataset, columns: newColumns } );
		onChange( dataset );
	}

	const updateData = ( newData ) => {
		setDataset( { ...dataset, data: newData } );
		onChange( dataset );
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
