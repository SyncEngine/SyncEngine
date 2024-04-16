import { createContext } from 'react';

export const FieldsContext = createContext(
	{
		parent: null,
		root: '',
		prefix: '',
		id: '',
		path: '',
		values: {},
	}
);
