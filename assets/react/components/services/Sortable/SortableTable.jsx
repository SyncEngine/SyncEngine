import React from 'react';
import Sortable from './index';

export default function SortableTable( props ) {
	return <Sortable { ...props } view={ 'table' }/>
}
