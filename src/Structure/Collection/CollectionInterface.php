<?php

namespace SyncEngine\Structure\Collection;

/**
 * @template TKey
 * @template TValue
 *
 * @extends \ArrayAccess<TKey, TValue>
 * @extends \Iterator<TKey, TValue>
 */
interface CollectionInterface extends \ArrayAccess, \Iterator, \Countable
{

}
