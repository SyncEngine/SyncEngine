import { createContext } from 'react';

/**
 * Context for available tags in the current scope.
 * @type {import('react').Context<Object<string, string>>}
 */
export const TagsContext = createContext( {} );
