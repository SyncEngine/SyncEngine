import React from 'react';
import { Accordion } from 'react-bootstrap';
import AccordionItemSticky from './Item';

import './styles.scss';

const AccordionSticky = ( props ) => {
	return <Accordion { ...props } className={ props.className ? props.className + ' sticky-header' : 'sticky-header' } />
}

AccordionSticky.Item = AccordionItemSticky;
AccordionSticky.Collapse = Accordion.Collapse;
AccordionSticky.Body = Accordion.Body;
AccordionSticky.Header = Accordion.Header;
AccordionSticky.Button = Accordion.Button;
AccordionSticky.propTypes = Accordion.propTypes;
AccordionSticky.contextTypes = Accordion.contextTypes;
AccordionSticky.displayName = Accordion.displayName + 'Sticky';

export default AccordionSticky;
