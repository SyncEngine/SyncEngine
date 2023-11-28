import { useState } from 'react';
import { isEmpty } from '../utils/conditionals';
import { publish, subscribe } from '../utils/events';
import useStorage from './useStorage';

/**
 * @param {string} namespace
 * @param {string} key
 * @param {*} initial
 * @param {boolean} json
 * @returns {*,function,boolean}
 */
export default function usePreference( key, namespace = '', initial = null, json = false ) {
	return useStorage( 'local', namespace, key, initial, false );
}
