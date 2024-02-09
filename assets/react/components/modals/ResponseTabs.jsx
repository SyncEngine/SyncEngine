import React from 'react';
import { Tab, Tabs } from 'react-bootstrap';
import Code from '../fields/Code';
import { objectToMappable } from '../../utils/data';
import { ucfirst } from '../../utils/globals';

export default function ResponseTabs( props ) {
	return (
		<div className={ props.contained && "tabs-contained h-100" }>
			<Tabs>
				{
					objectToMappable( props.data, 'name', 'content', true ).map( tab => {
						const json = 'object' === typeof tab.content;
						return (
							<Tab eventKey={ tab.name } key={ tab.name } title={ ucfirst( tab.name ) } className={ props.contained && "overflow-hidden w-100" }>
								<Code
									contained={ props.contained }
									height="100%"
									language={ json ? 'json' : undefined }
									editable={ false }
									value={ json ? JSON.stringify( tab.content, null, 2 ) : tab.content }
								/>
							</Tab>
						)
					} )
				}
			</Tabs>
		</div>
	)
}
