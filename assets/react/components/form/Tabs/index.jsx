import React from 'react';
import { Alert, Tabs, Tab } from "react-bootstrap";

import Group from "../Fields/Group";
import Fields from "../Fields/index";
import Field from '../Field';
import { objectToMappable } from "../../../utils/data";

export default function TabsControl( props ) {

	const {
		tabs,
		values,
		onChange,
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
						<Tab key={ name } eventKey={ name } title={ label } className="p-3 border bg-body">
							{ tab.description &&
								<p className="form-text text-muted">{ tab.description }</p>
							}
							{ tab.type &&
								<Field { ...tab } wrap={ false } value={ values[ tab.name ] ?? {} } onChange={ ( value ) => { onChange( value, tab.name ) } } />
							}
							{ tab.fields &&
								<Group fields={ tab.fields } updateField={ onChange } values={ values }></Group>
							}
							{ tab.nested &&
								<Fields fields={ tab.nested } value={ values[ tab.name ] ?? {} } onChange={ ( value ) => { onChange( value, tab.name ) } } />
							}
						</Tab>
					)
				} )
			}
		</Tabs>
	);
}
