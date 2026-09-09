import { createContext } from 'react';

/**
 * Context for the parent field context in nested form structures.
 * @type {import('react').Context<Object<string, any>>}
 */
export const ParentContext = createContext( {} );
