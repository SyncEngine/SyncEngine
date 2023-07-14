import React from 'react';
import { Alert, Tabs, Tab, TabContent } from "react-bootstrap";
import Group from "./Group";
import { objectToMappable } from "../../../utils/data";
import Fields from "./index";

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
					} = tab;

					return (
						<Tab key={ name } eventKey={ name } title={ label }>
							<TabContent className="p-3 border bg-body">
								{ tab.description &&
									<p className="form-text text-muted">{ tab.description }</p>
								}
								{ tab.fields &&
									<Group fields={ tab.fields } updateField={ updateField } values={ values }></Group>
								}
								{ tab.nested &&
									<Fields fields={ tab.nested } value={ values[ tab.name ] ?? {} } onChange={ ( value ) => { updateField( value, tab.name ) } } />
								}
							</TabContent>
						</Tab>
					)
				} )
			}
		</Tabs>
	);
}
