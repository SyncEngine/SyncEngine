import React from 'react';
import useModels from './useModels';

/**
 * @param {Object} items
 * @param {Object} query
 * @param {String} endpoint
 * @returns {[Object,{fetch:((function((Object|Function)): Promise<void>)|*),update,total}]}
 */
export default function useTasks( items = null, query = null, endpoint = null ) {
	return useModels( 'tasks', items, query, endpoint );
}
