import React from 'react';
import { Tab, Tabs } from 'react-bootstrap';
import Code from '../../fields/Code';
import { deepClone, objectToMappable } from '../../../utils/data';
import { ucfirst } from '../../../utils/globals';
import TraceControl from '../Trace';

export default function ResponseTabs( props ) {
	return (
		<div className={ props.contained && "tabs-contained h-100" }>
			<Tabs>
				{
					objectToMappable( deepClone( props.data ), 'name', 'content', true ).map( tab => {
						const json = 'object' === typeof tab.content;

						let view = null;
						switch ( tab.name.toLowerCase() ) {
							case 'trace':
								view = <TraceControl data={ deepClone( tab.content ) } />
								break;
							default:
								view =
									<Code
										contained={ props.contained }
										height="100%"
										language={ json ? 'json' : undefined }
										editable={ false }
										value={ json ? JSON.stringify( tab.content, null, 2 ) : tab.content }
									/>;
								break;
						}

						return (
							<Tab eventKey={ tab.name } key={ tab.name } title={ ucfirst( tab.name ) } className={ props.contained && "overflow-hidden w-100" }>
								{ view }
							</Tab>
						)
					} )
				}
			</Tabs>
		</div>
	)
}
