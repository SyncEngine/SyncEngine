import React from 'react';
import { Alert, Tabs, Tab, Stack } from 'react-bootstrap';

import FieldsItem from '../Fields/Item';
import { objectToMappable } from "../../../utils/data";
import { isEmpty } from '../../../utils/conditionals';

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
		<Stack gap={0}>
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
								<FieldsItem field={ { ...tab, label: null, description: null } } wrap={ isEmpty( tab.type ) } updateField={ onChange } values={ values } />
							</Tab>
						)
					} )
				}
			</Tabs>
		</Stack>
	);
}
