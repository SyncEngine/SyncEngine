import { createContext } from 'react';

/**
 * Context for entity data in workflow components.
 * @type {import('react').Context<Object<string, any>>}
 */
export const EntityContext = createContext( {} );
