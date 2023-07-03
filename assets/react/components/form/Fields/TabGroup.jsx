import React from 'react';
import { Alert, Tabs, Tab, TabContent } from "react-bootstrap";
import Group from "./Group";
import { objectToMappable } from "../../../utils/format";

export default function TabGroup( props ) {

	const {
		tabs,
		values,
		updateField,
	} = props;

	if ( ! tabs ) {
		return (
			<Alert variant="warning">No tabs found.</Alert>
		)
	}

	return (
		<Tabs>
			{
				objectToMappable( tabs, 'name' ).map( ( tab, index ) => {
					const {
						name,
						label,
						fields,
					} = tab;

					if ( ! fields ) {
						return (
							<Alert variant="warning">No fields found.</Alert>
						)
					}

					return (
						<Tab eventKey={ name } title={ label }>
							<TabContent className="p-1 border bg-body-tertiary">
								<div className="bg-body p-3">
									<Group fields={ fields } updateField={ updateField } values={ values }></Group>
								</div>
							</TabContent>
						</Tab>
					)
				} )
			}
		</Tabs>
	);
}
