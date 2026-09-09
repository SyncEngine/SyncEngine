import { createContext } from 'react';

/**
 * Context for workflow element state (step, action, condition).
 * @type {import('react').Context<Object<string, any>>}
 */
export const ElementContext = createContext( {} );
