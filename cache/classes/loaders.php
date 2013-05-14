<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cache loaders
 *
 * This file is part of Moodle's cache API, affectionately called MUC.
 * It contains the components that are required in order to use caching.
 *
 * @package    core
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The main cache class.
 *
 * This class if the first class that any end developer will interact with.
 * In order to create an instance of a cache that they can work with they must call one of the static make methods belonging
 * to this class.
 *
 * @package    core
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cache implements cache_loader {

    /**
     * We need a timestamp to use within the cache API.
     * This stamp needs to be used for all ttl and time based operations to ensure that we don't end up with
     * timing issues.
     * @var int
     */
    protected static $now;

    /**
     * The definition used when loading this cache if there was one.
     * @var cache_definition
     */
    private $definition = false;

    /**
     * The cache store that this loader will make use of.
     * @var cache_store
     */
    private $store;

    /**
     * The next cache loader in the chain if there is one.
     * If a cache request misses for the store belonging to this loader then the loader
     * stored here will be checked next.
     * If there is a loader here then $datasource must be false.
     * @var cache_loader|false
     */
    private $loader = false;

    /**
     * The data source to use if we need to load data (because if doesn't exist in the cache store).
     * If there is a data source here then $loader above must be false.
     * @var cache_data_source|false
     */
    private $datasource = false;

    /**
     * Used to quickly check if the store supports key awareness.
     * This is set when the cache is initialised and is used to speed up processing.
     * @var bool
     */
    private $supportskeyawareness = null;

    /**
     * Used to quickly check if the store supports ttl natively.
     * This is set when the cache is initialised and is used to speed up processing.
     * @var bool
     */
    private $supportsnativettl = null;

    /**
     * Gets set to true if the cache is going to be using the build in static "persist" cache.
     * The persist cache statically caches items used during the lifetime of the request. This greatly speeds up interaction
     * with the cache in areas where it will be repetitively hit for the same information such as with strings.
     * There are several other variables to control how this persist cache works.
     * @var bool
     */
    private $persist = false;

    /**
     * The persist cache itself.
     * Items will be stored in this cache as they were provided. This ensure there is no unnecessary processing taking place.
     * @var array
     */
    private $persistcache = array();

    /**
     * The number of items in the persist cache. Avoids count calls like you wouldn't believe.
     * @var int
     */
    private $persistcount = 0;

    /**
     * An array containing just the keys being used in the persist cache.
     * This seems redundant perhaps but is used when managing the size of the persist cache.
     * @var array
     */
    private $persistkeys = array();

    /**
     * The maximum size of the persist cache. If set to false there is no max size.
     * Caches that make use of the persist cache should seriously consider setting this to something reasonably small, but
     * still large enough to offset repetitive calls.
     * @var int|false
     */
    private $persistmaxsize = false;

    /**
     * Gets set to true during initialisation if the definition is making use of a ttl.
     * Used to speed up processing.
     * @var bool
     */
    private $hasattl = false;

    /**
     * Gets set to the class name of the store during initialisation. This is used several times in the cache class internally
     * and having it here helps speed up processing.
     * @var strubg
     */
    protected $storetype = 'unknown';

    /**
     * Gets set to true if we want to collect performance information about the cache API.
     * @var bool
     */
    protected $perfdebug = false;

    /**
     * Determines if this loader is a sub loader, not the top of the chain.
     * @var bool
     */
    protected $subloader = false;

    /**
     * Creates a new cache instance for a pre-defined definition.
     *
     * @param string $component The component for the definition
     * @param string $area The area for the definition
     * @param array $identifiers Any additional identifiers that should be provided to the definition.
     * @param string $aggregate Super advanced feature. More docs later.
     * @return cache_application|cache_session|cache_store
     */
    public static function make($component, $area, array $identifiers = array(), $aggregate = null) {
        $factory = cache_factory::instance();
        return $factory->create_cache_from_definition($component, $area, $identifiers, $aggregate);
    }

    /**
     * Creates a new cache instance based upon the given params.
     *
     * @param int $mode One of cache_store::MODE_*
     * @param string $component The component this cache relates to.
     * @param string $area The area this cache relates to.
     * @param array $identifiers Any additional identifiers that should be provided to the definition.
     * @param array $options An array of options, available options are:
     *   - simplekeys : Set to true if the keys you will use are a-zA-Z0-9_
     *   - simpledata : Set to true if the type of the data you are going to store is scalar, or an array of scalar vars
     *   - persistent : If set to true the cache will persist construction requests.
     * @return cache_application|cache_session|cache_store
     */
    public static function make_from_params($mode, $component, $area, array $identifiers = array(), array $options = array()) {
        $factory = cache_factory::instance();
        return $factory->create_cache_from_params($mode, $component, $area, $identifiers, $options);
    }

    /**
     * Constructs a new cache instance.
     *
     * You should not call this method from your code, instead you should use the cache::make methods.
     *
     * This method is public so that the cache_factory is able to instantiate cache instances.
     * Ideally we would make this method protected and expose its construction to the factory method internally somehow.
     * The factory class is responsible for this in order to centralise the storage of instances once created. This way if needed
     * we can force a reset of the cache API (used during unit testing).
     *
     * @param cache_definition $definition The definition for the cache instance.
     * @param cache_store $store The store that cache should use.
     * @param cache_loader|cache_data_source $loader The next loader in the chain or the data source if there is one and there
     *      are no other cache_loaders in the chain.
     */
    public function __construct(cache_definition $definition, cache_store $store, $loader = null) {
        global $CFG;
        $this->definition = $definition;
        $this->store = $store;
        $this->storetype = get_class($store);
        $this->perfdebug = !empty($CFG->perfdebug);
        if ($loader instanceof cache_loader) {
            $this->loader = $loader;
            // Mark the loader as a sub (chained) loader.
            $this->loader->set_is_sub_loader(true);
        } else if ($loader instanceof cache_data_source) {
            $this->datasource = $loader;
        }
        $this->definition->generate_definition_hash();
        $this->persist = $this->definition->should_be_persistent();
        if ($this->persist) {
            $this->persistmaxsize = $this->definition->get_persistent_max_size();
        }
        $this->hasattl = ($this->definition->get_ttl() > 0);
    }

    /**
     * Used to inform the loader of its state as a sub loader, or as the top of the chain.
     *
     * This is important as it ensures that we do not have more than one loader keeping persistent data.
     * Subloaders need to be "pure" loaders in the sense that they are used to store and retrieve information from stores or the
     * next loader/data source in the chain.
     * Nothing fancy, nothing flash.
     *
     * @param bool $setting
     */
    protected function set_is_sub_loader($setting = true) {
        if ($setting) {
            $this->subloader = true;
            // Subloaders should not keep persistent data.
            $this->persist = false;
            $this->persistmaxsize = false;
        } else {
            $this->subloader = true;
            $this->persist = $this->definition->should_be_persistent();
            if ($this->persist) {
                $this->persistmaxsize = $this->definition->get_persistent_max_size();
            }
        }
    }

    /**
     * Alters the identifiers that have been provided to the definition.
     *
     * This is an advanced method and should not be used unless really needed.
     * It allows the developer to slightly alter the definition without having to re-establish the cache.
     * It will cause more processing as the definition will need to clear and reprepare some of its properties.
     *
     * @param array $identifiers
     */
    public function set_identifiers(array $identifiers) {
        $this->definition->set_identifiers($identifiers);
    }

    /**
     * Retrieves the value for the given key from the cache.
     *
     * @param string|int $key The key for the data being requested.
     *      It can be any structure although using a scalar string or int is recommended in the interests of performance.
     *      In advanced cases an array may be useful such as in situations requiring the multi-key functionality.
     * @param int $strictness One of IGNORE_MISSING | MUST_EXIST
     * @return mixed|false The data from the cache or false if the key did not exist within the cache.
     * @throws moodle_exception
     */
    public function get($key, $strictness = IGNORE_MISSING) {
        // 1. Parse the key.
        $parsedkey = $this->parse_key($key);
        // 2. Get it from the persist cache if we can (only when persist is enabled and it has already been requested/set).
        $result = false;
        if ($this->is_using_persist_cache()) {
            $result = $this->get_from_persist_cache($parsedkey);
        }
        if ($result !== false) {
            if (!is_scalar($result)) {
                // If data is an object it will be a reference.
                // If data is an array if may contain references.
                // We want to break references so that the cache cannot be modified outside of itself.
                // Call the function to unreference it (in the best way possible).
                $result = $this->unref($result);
            }
            return $result;
        }
        // 3. Get it from the store. Obviously wasn't in the persist cache.
        $result = $this->store->get($parsedkey);
        if ($result !== false) {
            if ($result instanceof cache_ttl_wrapper) {
                if ($result->has_expired()) {
                    $this->store->delete($parsedkey);
                    $result = false;
                } else {
                    $result = $result->data;
                }
            }
            if ($result instanceof cache_cached_object) {
                $result = $result->restore_object();
            }
            if ($this->is_using_persist_cache()) {
                $this->set_in_persist_cache($parsedkey, $result);
            }
        }
        // 4. Load if from the loader/datasource if we don't already have it.
        $setaftervalidation = false;
        if ($result === false) {
            if ($this->perfdebug) {
                cache_helper::record_cache_miss($this->storetype, $this->definition->get_id());
            }
            if ($this->loader !== false) {
                // We must pass the original (unparsed) key to the next loader in the chain.
                // The next loader will parse the key as it sees fit. It may be parsed differently
                // depending upon the capabilities of the store associated with the loader.
                $result = $this->loader->get($key);
            } else if ($this->datasource !== false) {
                $result = $this->datasource->load_for_cache($key);
            }
            $setaftervalidation = ($result !== false);
        } else if ($this->perfdebug) {
            cache_helper::record_cache_hit($this->storetype, $this->definition->get_id());
        }
        // 5. Validate strictness.
        if ($strictness === MUST_EXIST && $result === false) {
            throw new moodle_exception('Requested key did not exist in any cache stores and could not be loaded.');
        }
        // 6. Set it to the store if we got it from the loader/datasource.
        if ($setaftervalidation) {
            $this->set($key, $result);
        }
        // 7. Make sure we don't pass back anything that could be a reference.
        //    We don't want people modifying the data in the cache.
        if (!is_scalar($result)) {
            // If data is an object it will be a reference.
            // If data is an array if may contain references.
            // We want to break references so that the cache cannot be modified outside of itself.
            // Call the function to unreference it (in the best way possible).
            $result = $this->unref($result);
        }
        return $result;
    }

    /**
     * Retrieves an array of values for an array of keys.
     *
     * Using this function comes with potential performance implications.
     * Not all cache stores will support get_many/set_many operations and in order to replicate this functionality will call
     * the equivalent singular method for each item provided.
     * This should not deter you from using this function as there is a performance benefit in situations where the cache store
     * does support it, but you should be aware of this fact.
     *
     * @param array $keys The keys of the data being requested.
     *      Each key can be any structure although using a scalar string or int is recommended in the interests of performance.
     *      In advanced cases an array may be useful such as in situations requiring the multi-key functionality.
     * @param int $strictness One of IGNORE_MISSING or MUST_EXIST.
     * @return array An array of key value pairs for the items that could be retrieved from the cache.
     *      If MUST_EXIST was used and not all keys existed within the cache then an exception will be thrown.
     *      Otherwise any key that did not exist will have a data value of false within the results.
     * @throws moodle_exception
     */
    public function get_many(array $keys, $strictness = IGNORE_MISSING) {

        $keysparsed = array();
        $parsedkeys = array();
        $resultpersist = array();
        $resultstore = array();
        $keystofind = array();

        // First up check the persist cache for each key.
        $isusingpersist = $this->is_using_persist_cache();
        foreach ($keys as $key) {
            $pkey = $this->parse_key($key);
            $keysparsed[$key] = $pkey;
            $parsedkeys[$pkey] = $key;
            $keystofind[$pkey] = $key;
            if ($isusingpersist) {
                $value = $this->get_from_persist_cache($pkey);
                if ($value !== false) {
                    $resultpersist[$pkey] = $value;
                    unset($keystofind[$pkey]);
                }
            }
        }

        // Next assuming we didn't find all of the keys in the persist cache try loading them from the store.
        if (count($keystofind)) {
            $resultstore = $this->store->get_many(array_keys($keystofind));
            // Process each item in the result to "unwrap" it.
            foreach ($resultstore as $key => $value) {
                if ($value instanceof cache_ttl_wrapper) {
                    if ($value->has_expired()) {
                        $value = false;
                    } else {
                        $value = $value->data;
                    }
                }
                if ($value instanceof cache_cached_object) {
                    $value = $value->restore_object();
                }
                $resultstore[$key] = $value;
            }
        }

        // Merge the result from the persis cache with the results from the store load.
        $result = $resultpersist + $resultstore;
        unset($resultpersist);
        unset($resultstore);

        // Next we need to find any missing values and load them from the loader/datasource next in the chain.
        $usingloader = ($this->loader !== false);
        $usingsource = (!$usingloader && ($this->datasource !== false));
        if ($usingloader || $usingsource) {
            $missingkeys = array();
            foreach ($result as $key => $value) {
                if ($value === false) {
                    $missingkeys[] = ($usingloader) ? $key : $parsedkeys[$key];
                }
            }
            if (!empty($missingkeys)) {
                if ($usingloader) {
                    $resultmissing = $this->loader->get_many($missingkeys);
                } else {
                    $resultmissing = $this->datasource->load_many_for_cache($missingkeys);
                }
                foreach ($resultmissing as $key => $value) {
                    $pkey = ($usingloader) ? $key : $keysparsed[$key];
                    $realkey = ($usingloader) ? $parsedkeys[$key] : $key;
                    $result[$pkey] = $value;
                    if ($value !== false) {
                        $this->set($realkey, $value);
                    }
                }
                unset($resultmissing);
            }
            unset($missingkeys);
        }

        // Create an array with the original keys and the found values. This will be what we return.
        $fullresult = array();
        foreach ($result as $key => $value) {
            $fullresult[$parsedkeys[$key]] = $value;
        }
        unset($result);

        // Final step is to check strictness.
        if ($strictness === MUST_EXIST) {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $fullresult)) {
                    throw new moodle_exception('Not all the requested keys existed within the cache stores.');
                }
            }
        }

        // Return the result. Phew!
        return $fullresult;
    }

    /**
     * Sends a key => value pair to the cache.
     *
     * <code>
     * // This code will add four entries to the cache, one for each url.
     * $cache->set('main', 'http://moodle.org');
     * $cache->set('docs', 'http://docs.moodle.org');
     * $cache->set('tracker', 'http://tracker.moodle.org');
     * $cache->set('qa', 'http://qa.moodle.net');
     * </code>
     *
     * @param string|int $key The key for the data being requested.
     *      It can be any structure although using a scalar string or int is recommended in the interests of performance.
     *      In advanced cases an array may be useful such as in situations requiring the multi-key functionality.
     * @param mixed $data The data to set against the key.
     * @return bool True on success, false otherwise.
     */
    public function set($key, $data) {
        if ($this->perfdebug) {
            cache_helper::record_cache_set($this->storetype, $this->definition->get_id());
        }
        if (is_object($data) && $data instanceof cacheable_object) {
            $data = new cache_cached_object($data);
        } else if (!is_scalar($data)) {
            // If data is an object it will be a reference.
            // If data is an array if may contain references.
            // We want to break references so that the cache cannot be modified outside of itself.
            // Call the function to unreference it (in the best way possible).
            $data = $this->unref($data);
        }
        if ($this->has_a_ttl() && !$this->store_supports_native_ttl()) {
            $data = new cache_ttl_wrapper($data, $this->definition->get_ttl());
        }
        $parsedkey = $this->parse_key($key);
        if ($this->is_using_persist_cache()) {
            $this->set_in_persist_cache($parsedkey, $data);
        }
        return $this->store->set($parsedkey, $data);
    }

    /**
     * Removes references where required.
     *
     * @param stdClass|array $data
     */
    protected function unref($data) {
        if ($this->definition->uses_simple_data()) {
            return $data;
        }
        // Check if it requires serialisation in order to produce a reference free copy.
        if ($this->requires_serialisation($data)) {
            // Damn, its going to have to be serialise.
            $data = serialize($data);
            // We unserialise immediately so that we don't have to do it every time on get.
            $data = unserialize($data);
        } else if (!is_scalar($data)) {
            // Its safe to clone, lets do it, its going to beat the pants of serialisation.
            $data = $this->deep_clone($data);
        }
        return $data;
    }

    /**
     * Checks to see if a var requires serialisation.
     *
     * @param mixed $value The value to check.
     * @param int $depth Used to ensure we don't enter an endless loop (think recursion).
     * @return bool Returns true if the value is going to require serialisation in order to ensure a reference free copy
     *      or false if its safe to clone.
     */
    protected function requires_serialisation($value, $depth = 1) {
        if (is_scalar($value)) {
            return false;
        } else if (is_array($value) || $value instanceof stdClass || $value instanceof Traversable) {
            if ($depth > 5) {
                // Skrew it, mega-deep object, developer you suck, we're just going to serialise.
                return true;
            }
            foreach ($value as $key => $subvalue) {
                if ($this->requires_serialisation($subvalue, $depth++)) {
                    return true;
                }
            }
        }
        // Its not scalar, array, or stdClass so we'll need to serialise.
        return true;
    }

    /**
     * Creates a reference free clone of the given value.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function deep_clone($value) {
        if (is_object($value)) {
            // Objects must be cloned to begin with.
            $value = clone $value;
        }
        if (is_array($value) || is_object($value)) {
            foreach ($value as $key => $subvalue) {
                $value[$key] = $this->deep_clone($subvalue);
            }
        }
        return $value;
    }

    /**
     * Sends several key => value pairs to the cache.
     *
     * Using this function comes with potential performance implications.
     * Not all cache stores will support get_many/set_many operations and in order to replicate this functionality will call
     * the equivalent singular method for each item provided.
     * This should not deter you from using this function as there is a performance benefit in situations where the cache store
     * does support it, but you should be aware of this fact.
     *
     * <code>
     * // This code will add four entries to the cache, one for each url.
     * $cache->set_many(array(
     *     'main' => 'http://moodle.org',
     *     'docs' => 'http://docs.moodle.org',
     *     'tracker' => 'http://tracker.moodle.org',
     *     'qa' => ''http://qa.moodle.net'
     * ));
     * </code>
     *
     * @param array $keyvaluearray An array of key => value pairs to send to the cache.
     * @return int The number of items successfully set. It is up to the developer to check this matches the number of items.
     *      ... if they care that is.
     */
    public function set_many(array $keyvaluearray) {
        $data = array();
        $simulatettl = $this->has_a_ttl() && !$this->store_supports_native_ttl();
        $usepersistcache = $this->is_using_persist_cache();
        foreach ($keyvaluearray as $key => $value) {
            if (is_object($value) && $value instanceof cacheable_object) {
                $value = new cache_cached_object($value);
            } else if (!is_scalar($value)) {
                // If data is an object it will be a reference.
                // If data is an array if may contain references.
                // We want to break references so that the cache cannot be modified outside of itself.
                // Call the function to unreference it (in the best way possible).
                $value = $this->unref($value);
            }
            if ($simulatettl) {
                $value = new cache_ttl_wrapper($value, $this->definition->get_ttl());
            }
            $data[$key] = array(
                'key' => $this->parse_key($key),
                'value' => $value
            );
            if ($usepersistcache) {
                $this->set_in_persist_cache($data[$key]['key'], $value);
            }
        }
        if ($this->perfdebug) {
            cache_helper::record_cache_set($this->storetype, $this->definition->get_id());
        }
        return $this->store->set_many($data);
    }

    /**
     * Test is a cache has a key.
     *
     * The use of the has methods is strongly discouraged. In a high load environment the cache may well change between the
     * test and any subsequent action (get, set, delete etc).
     * Instead it is recommended to write your code in such a way they it performs the following steps:
     * <ol>
     * <li>Attempt to retrieve the information.</li>
     * <li>Generate the information.</li>
     * <li>Attempt to set the information</li>
     * </ol>
     *
     * Its also worth mentioning that not all stores support key tests.
     * For stores that don't support key tests this functionality is mimicked by using the equivalent get method.
     * Just one more reason you should not use these methods unless you have a very good reason to do so.
     *
     * @param string|int $key
     * @param bool $tryloadifpossible If set to true, the cache doesn't contain the key, and there is another cache loader or
     *      data source then the code will try load the key value from the next item in the chain.
     * @return bool True if the cache has the requested key, false otherwise.
     */
    public function has($key, $tryloadifpossible = false) {
        $parsedkey = $this->parse_key($key);
        if ($this->is_in_persist_cache($parsedkey)) {
            // Hoorah, that was easy. It exists in the persist cache so we definitely have it.
            return true;
        }
        if ($this->has_a_ttl() && !$this->store_supports_native_ttl()) {
            // The data has a TTL and the store doesn't support it natively.
            // We must fetch the data and expect a ttl wrapper.
            $data = $this->store->get($parsedkey);
            $has = ($data instanceof cache_ttl_wrapper && !$data->has_expired());
        } else if (!$this->store_supports_key_awareness()) {
            // The store doesn't support key awareness, get the data and check it manually... puke.
            // Either no TTL is set of the store supports its handling natively.
            $data = $this->store->get($parsedkey);
            $has = ($data !== false);
        } else {
            // The store supports key awareness, this is easy!
            // Either no TTL is set of the store supports its handling natively.
            $has = $this->store->has($parsedkey);
        }
        if (!$has && $tryloadifpossible) {
            if ($this->loader !== false) {
                $result = $this->loader->get($parsedkey);
            } else if ($this->datasource !== null) {
                $result = $this->datasource->load_for_cache($key);
            }
            $has = ($result !== null);
            if ($has) {
                $this->set($key, $result);
            }
        }
        return $has;
    }

    /**
     * Test is a cache has all of the given keys.
     *
     * It is strongly recommended to avoid the use of this function if not absolutely required.
     * In a high load environment the cache may well change between the test and any subsequent action (get, set, delete etc).
     *
     * Its also worth mentioning that not all stores support key tests.
     * For stores that don't support key tests this functionality is mimicked by using the equivalent get method.
     * Just one more reason you should not use these methods unless you have a very good reason to do so.
     *
     * @param array $keys
     * @return bool True if the cache has all of the given keys, false otherwise.
     */
    public function has_all(array $keys) {
        if (($this->has_a_ttl() && !$this->store_supports_native_ttl()) || !$this->store_supports_key_awareness()) {
            foreach ($keys as $key) {
                if (!$this->has($key)) {
                    return false;
                }
            }
            return true;
        }
        $parsedkeys = array_map(array($this, 'parse_key'), $keys);
        return $this->store->has_all($parsedkeys);
    }

    /**
     * Test if a cache has at least one of the given keys.
     *
     * It is strongly recommended to avoid the use of this function if not absolutely required.
     * In a high load environment the cache may well change between the test and any subsequent action (get, set, delete etc).
     *
     * Its also worth mentioning that not all stores support key tests.
     * For stores that don't support key tests this functionality is mimicked by using the equivalent get method.
     * Just one more reason you should not use these methods unless you have a very good reason to do so.
     *
     * @param array $keys
     * @return bool True if the cache has at least one of the given keys
     */
    public function has_any(array $keys) {
        if (($this->has_a_ttl() && !$this->store_supports_native_ttl()) || !$this->store_supports_key_awareness()) {
            foreach ($keys as $key) {
                if ($this->has($key)) {
                    return true;
                }
            }
            return false;
        }

        if ($this->is_using_persist_cache()) {
            $parsedkeys = array();
            foreach ($keys as $id => $key) {
                $parsedkey = $this->parse_key($key);
                if ($this->is_in_persist_cache($parsedkey)) {
                    return true;
                }
                $parsedkeys[] = $parsedkey;
            }
        } else {
            $parsedkeys = array_map(array($this, 'parse_key'), $keys);
        }
        return $this->store->has_any($parsedkeys);
    }

    /**
     * Delete the given key from the cache.
     *
     * @param string|int $key The key to delete.
     * @param bool $recurse When set to true the key will also be deleted from all stacked cache loaders and their stores.
     *     This happens by default and ensure that all the caches are consistent. It is NOT recommended to change this.
     * @return bool True of success, false otherwise.
     */
    public function delete($key, $recurse = true) {
        $parsedkey = $this->parse_key($key);
        $this->delete_from_persist_cache($parsedkey);
        if ($recurse && $this->loader !== false) {
            // Delete from the bottom of the stack first.
            $this->loader->delete($key, $recurse);
        }
        return $this->store->delete($parsedkey);
    }

    /**
     * Delete all of the given keys from the cache.
     *
     * @param array $keys The key to delete.
     * @param bool $recurse When set to true the key will also be deleted from all stacked cache loaders and their stores.
     *     This happens by default and ensure that all the caches are consistent. It is NOT recommended to change this.
     * @return int The number of items successfully deleted.
     */
    public function delete_many(array $keys, $recurse = true) {
        $parsedkeys = array_map(array($this, 'parse_key'), $keys);
        if ($this->is_using_persist_cache()) {
            foreach ($parsedkeys as $parsedkey) {
                $this->delete_from_persist_cache($parsedkey);
            }
        }
        if ($recurse && $this->loader !== false) {
            // Delete from the bottom of the stack first.
            $this->loader->delete_many($keys, $recurse);
        }
        return $this->store->delete_many($parsedkeys);
    }

    /**
     * Purges the cache store, and loader if there is one.
     *
     * @return bool True on success, false otherwise
     */
    public function purge() {
        // 1. Purge the persist cache.
        $this->persistcache = array();
        // 2. Purge the store.
        $this->store->purge();
        // 3. Optionally pruge any stacked loaders.
        if ($this->loader) {
            $this->loader->purge();
        }
        return true;
    }

    /**
     * Parses the key turning it into a string (or array is required) suitable to be passed to the cache store.
     *
     * @param string|int $key As passed to get|set|delete etc.
     * @return string|array String unless the store supports multi-identifiers in which case an array if returned.
     */
    protected function parse_key($key) {
        // First up if the store supports multiple keys we'll go with that.
        if ($this->store->supports_multiple_identifiers()) {
            $result = $this->definition->generate_multi_key_parts();
            $result['key'] = $key;
            return $result;
        }
        // If not we need to generate a hash and to for that we use the cache_helper.
        return cache_helper::hash_key($key, $this->definition);
    }

    /**
     * Returns true if the cache is making use of a ttl.
     * @return bool
     */
    protected function has_a_ttl() {
        return $this->hasattl;
    }

    /**
     * Returns true if the cache store supports native ttl.
     * @return bool
     */
    protected function store_supports_native_ttl() {
        if ($this->supportsnativettl === null) {
            $this->supportsnativettl = ($this->store->supports_native_ttl());
        }
        return $this->supportsnativettl;
    }

    /**
     * Returns the cache definition.
     *
     * @return cache_definition
     */
    protected function get_definition() {
        return $this->definition;
    }

    /**
     * Returns the cache store
     *
     * @return cache_store
     */
    protected function get_store() {
        return $this->store;
    }

    /**
     * Returns the loader associated with this instance.
     *
     * @since 2.4.4
     * @return cache_loader|false
     */
    protected function get_loader() {
        return $this->loader;
    }

    /**
     * Returns the data source associated with this cache.
     *
     * @since 2.4.4
     * @return cache_data_source|false
     */
    protected function get_datasource() {
        return $this->datasource;
    }

    /**
     * Returns true if the store supports key awareness.
     *
     * @return bool
     */
    protected function store_supports_key_awareness() {
        if ($this->supportskeyawareness === null) {
            $this->supportskeyawareness = ($this->store instanceof cache_is_key_aware);
        }
        return $this->supportskeyawareness;
    }

    /**
     * Returns true if the store natively supports locking.
     *
     * @return bool
     */
    protected function store_supports_native_locking() {
        if ($this->nativelocking === null) {
            $this->nativelocking = ($this->store instanceof cache_is_lockable);
        }
        return $this->nativelocking;
    }

    /**
     * Returns true if this cache is making use of the persist cache.
     *
     * @return bool
     */
    protected function is_using_persist_cache() {
        return $this->persist;
    }

    /**
     * Returns true if the requested key exists within the persist cache.
     *
     * @param string $key The parsed key
     * @return bool
     */
    protected function is_in_persist_cache($key) {
        if (is_array($key)) {
            $key = $key['key'];
        }
        // This could be written as a single line, however it has been split because the ttl check is faster than the instanceof
        // and has_expired calls.
        if (!$this->persist || !array_key_exists($key, $this->persistcache)) {
            return false;
        }
        if ($this->has_a_ttl() && $this->store_supports_native_ttl()) {
             return !($this->persistcache[$key] instanceof cache_ttl_wrapper && $this->persistcache[$key]->has_expired());
        }
        return true;
    }

    /**
     * Returns the item from the persist cache if it exists there.
     *
     * @param string $key The parsed key
     * @return mixed|false The data from the persist cache or false if it wasn't there.
     */
    protected function get_from_persist_cache($key) {
        if (is_array($key)) {
            $key = $key['key'];
        }
        if (!$this->persist || !array_key_exists($key, $this->persistcache)) {
            $result = false;
        } else {
            $data = $this->persistcache[$key];
            if (!$this->has_a_ttl() || !$data instanceof cache_ttl_wrapper) {
                if ($data instanceof cache_cached_object) {
                    $data = $data->restore_object();
                }
                $result = $data;
            } else if ($data->has_expired()) {
                $this->delete_from_persist_cache($key);
                $result = false;
            } else {
                if ($data instanceof cache_cached_object) {
                    $data = $data->restore_object();
                }
                $result = $data->data;
            }
        }
        if ($result) {
            if ($this->perfdebug) {
                cache_helper::record_cache_hit('** static persist **', $this->definition->get_id());
            }
            return $result;
        } else {
            if ($this->perfdebug) {
                cache_helper::record_cache_miss('** static persist **', $this->definition->get_id());
            }
            return false;
        }
    }

    /**
     * Sets a key value pair into the persist cache.
     *
     * @param string $key The parsed key
     * @param mixed $data
     * @return bool
     */
    protected function set_in_persist_cache($key, $data) {
        if (is_array($key)) {
            $key = $key['key'];
        }
        $this->persistcache[$key] = $data;
        if ($this->persistmaxsize !== false) {
            $this->persistcount++;
            if ($this->persistcount > $this->persistmaxsize) {
                $dropkey = array_shift($this->persistkeys);
                unset($this->persistcache[$dropkey]);
                $this->persistcount--;
            }
        }
        return true;
    }

    /**
     * Deletes an item from the persist cache.
     *
     * @param string|int $key As given to get|set|delete
     * @return bool True on success, false otherwise.
     */
    protected function delete_from_persist_cache($key) {
        unset($this->persistcache[$key]);
        if ($this->persistmaxsize !== false) {
            $dropkey = array_search($key, $this->persistkeys);
            if ($dropkey) {
                unset($this->persistkeys[$dropkey]);
                $this->persistcount--;
            }
        }
        return true;
    }

    /**
     * Returns the timestamp from the first request for the time from the cache API.
     *
     * This stamp needs to be used for all ttl and time based operations to ensure that we don't end up with
     * timing issues.
     *
     * @return int
     */
    public static function now() {
        if (self::$now === null) {
            self::$now = time();
        }
        return self::$now;
    }
}

/**
 * An application cache.
 *
 * This class is used for application caches returned by the cache::make methods.
 * On top of the standard functionality it also allows locking to be required and or manually operated.
 *
 * This cache class should never be interacted with directly. Instead you should always use the cache::make methods.
 * It is technically possible to call those methods through this class however there is no guarantee that you will get an
 * instance of this class back again.
 *
 * @internal don't use me directly.
 *
 * @package    core
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cache_application extends cache implements cache_loader_with_locking {

    /**
     * Lock identifier.
     * This is used to ensure the lock belongs to the cache instance + definition + user.
     * @var string
     */
    protected $lockidentifier;

    /**
     * Gets set to true if the cache's primary store natively supports locking.
     * If it does then we use that, otherwise we need to instantiate a second store to use for locking.
     * @var cache_store
     */
    protected $nativelocking = null;

    /**
     * Gets set to true if the cache is going to be using locking.
     * This isn't a requirement, it doesn't need to use locking (most won't) and this bool is used to quickly check things.
     * If required then locking will be forced for the get|set|delete operation.
     * @var bool
     */
    protected $requirelocking = false;

    /**
     * Gets set to true if the cache must use read locking (get|has).
     * @var bool
     */
    protected $requirelockingread = false;

    /**
     * Gets set to true if the cache must use write locking (set|delete)
     * @var bool
     */
    protected $requirelockingwrite = false;

    /**
     * Gets set to a cache_store to use for locking if the caches primary store doesn't support locking natively.
     * @var cache_lock_interface
     */
    protected $cachelockinstance;

    /**
     * Overrides the cache construct method.
     *
     * You should not call this method from your code, instead you should use the cache::make methods.
     *
     * @param cache_definition $definition
     * @param cache_store $store
     * @param cache_loader|cache_data_source $loader
     */
    public function __construct(cache_definition $definition, cache_store $store, $loader = null) {
        parent::__construct($definition, $store, $loader);
        $this->nativelocking = $this->store_supports_native_locking();
        if ($definition->require_locking()) {
            $this->requirelocking = true;
            $this->requirelockingread = $definition->require_locking_read();
            $this->requirelockingwrite = $definition->require_locking_write();
        }

        if ($definition->has_invalidation_events()) {
            $lastinvalidation = $this->get('lastinvalidation');
            if ($lastinvalidation === false) {
                // This is a new session, there won't be anything to invalidate. Set the time of the last invalidation and
                // move on.
                $this->set('lastinvalidation', cache::now());
                return;
            } else if ($lastinvalidation == cache::now()) {
                // We've already invalidated during this request.
                return;
            }

            // Get the event invalidation cache.
            $cache = cache::make('core', 'eventinvalidation');
            $events = $cache->get_many($definition->get_invalidation_events());
            $todelete = array();
            $purgeall = false;
            // Iterate the returned data for the events.
            foreach ($events as $event => $keys) {
                if ($keys === false) {
                    // No data to be invalidated yet.
                    continue;
                }
                // Look at each key and check the timestamp.
                foreach ($keys as $key => $timestamp) {
                    // If the timestamp of the event is more than or equal to the last invalidation (happened between the last
                    // invalidation and now)then we need to invaliate the key.
                    if ($timestamp >= $lastinvalidation) {
                        if ($key === 'purged') {
                            $purgeall = true;
                            break;
                        } else {
                            $todelete[] = $key;
                        }
                    }
                }
            }
            if ($purgeall) {
                $this->purge();
            } else if (!empty($todelete)) {
                $todelete = array_unique($todelete);
                $this->delete_many($todelete);
            }
            // Set the time of the last invalidation.
            $this->set('lastinvalidation', cache::now());
        }
    }

    /**
     * Returns the identifier to use
     *
     * @staticvar int $instances Counts the number of instances. Used as part of the lock identifier.
     * @return string
     */
    public function get_identifier() {
        static $instances = 0;
        if ($this->lockidentifier === null) {
            $this->lockidentifier = md5(
                $this->get_definition()->generate_definition_hash() .
                sesskey() .
                $instances++ .
                'cache_application'
            );
        }
        return $this->lockidentifier;
    }

    /**
     * Fixes the instance up after a clone.
     */
    public function __clone() {
        // Force a new idenfitier.
        $this->lockidentifier = null;
    }

    /**
     * Acquires a lock on the given key.
     *
     * This is done automatically if the definition requires it.
     * It is recommended to use a definition if you want to have locking although it is possible to do locking without having
     * it required by the definition.
     * The problem with such an approach is that you cannot ensure that code will consistently use locking. You will need to
     * rely on the integrators review skills.
     *
     * @param string|int $key The key as given to get|set|delete
     * @return bool Returns true if the lock could be acquired, false otherwise.
     */
    public function acquire_lock($key) {
        $key = $this->parse_key($key);
        if ($this->nativelocking) {
            return $this->get_store()->acquire_lock($key, $this->get_identifier());
        } else {
            $this->ensure_cachelock_available();
            return $this->cachelockinstance->lock($key, $this->get_identifier());
        }
    }

    /**
     * Checks if this cache has a lock on the given key.
     *
     * @param string|int $key The key as given to get|set|delete
     * @return bool|null Returns true if there is a lock and this cache has it, null if no one has a lock on that key, false if
     *      someone else has the lock.
     */
    public function check_lock_state($key) {
        $key = $this->parse_key($key);
        if ($this->nativelocking) {
            return $this->get_store()->check_lock_state($key, $this->get_identifier());
        } else {
            $this->ensure_cachelock_available();
            return $this->cachelockinstance->check_state($key, $this->get_identifier());
        }
    }

    /**
     * Releases the lock this cache has on the given key
     *
     * @param string|int $key
     * @return bool True if the operation succeeded, false otherwise.
     */
    public function release_lock($key) {
        $key = $this->parse_key($key);
        if ($this->nativelocking) {
            return $this->get_store()->release_lock($key, $this->get_identifier());
        } else {
            $this->ensure_cachelock_available();
            return $this->cachelockinstance->unlock($key, $this->get_identifier());
        }
    }

    /**
     * Ensure that the dedicated lock store is ready to go.
     *
     * This should only happen if the cache store doesn't natively support it.
     */
    protected function ensure_cachelock_available() {
        if ($this->cachelockinstance === null) {
            $this->cachelockinstance = cache_helper::get_cachelock_for_store($this->get_store());
        }
    }

    /**
     * Sends a key => value pair to the cache.
     *
     * <code>
     * // This code will add four entries to the cache, one for each url.
     * $cache->set('main', 'http://moodle.org');
     * $cache->set('docs', 'http://docs.moodle.org');
     * $cache->set('tracker', 'http://tracker.moodle.org');
     * $cache->set('qa', 'http://qa.moodle.net');
     * </code>
     *
     * @param string|int $key The key for the data being requested.
     * @param mixed $data The data to set against the key.
     * @return bool True on success, false otherwise.
     */
    public function set($key, $data) {
        if ($this->requirelockingwrite && !$this->acquire_lock($key)) {
            return false;
        }
        $result = parent::set($key, $data);
        if ($this->requirelockingwrite && !$this->release_lock($key)) {
            debugging('Failed to release cache lock on set operation... this should not happen.', DEBUG_DEVELOPER);
        }
        return $result;
    }

    /**
     * Sends several key => value pairs to the cache.
     *
     * Using this function comes with potential performance implications.
     * Not all cache stores will support get_many/set_many operations and in order to replicate this functionality will call
     * the equivalent singular method for each item provided.
     * This should not deter you from using this function as there is a performance benefit in situations where the cache store
     * does support it, but you should be aware of this fact.
     *
     * <code>
     * // This code will add four entries to the cache, one for each url.
     * $cache->set_many(array(
     *     'main' => 'http://moodle.org',
     *     'docs' => 'http://docs.moodle.org',
     *     'tracker' => 'http://tracker.moodle.org',
     *     'qa' => ''http://qa.moodle.net'
     * ));
     * </code>
     *
     * @param array $keyvaluearray An array of key => value pairs to send to the cache.
     * @return int The number of items successfully set. It is up to the developer to check this matches the number of items.
     *      ... if they care that is.
     */
    public function set_many(array $keyvaluearray) {
        if ($this->requirelockingwrite) {
            $locks = array();
            foreach ($keyvaluearray as $id => $pair) {
                $key = $pair['key'];
                if ($this->acquire_lock($key)) {
                    $locks[] = $key;
                } else {
                    unset($keyvaluearray[$id]);
                }
            }
        }
        $result = parent::set_many($keyvaluearray);
        if ($this->requirelockingwrite) {
            foreach ($locks as $key) {
                if ($this->release_lock($key)) {
                    debugging('Failed to release cache lock on set_many operation... this should not happen.', DEBUG_DEVELOPER);
                }
            }
        }
        return $result;
    }

    /**
     * Retrieves the value for the given key from the cache.
     *
     * @param string|int $key The key for the data being requested.
     * @param int $strictness One of IGNORE_MISSING | MUST_EXIST
     * @return mixed|false The data from the cache or false if the key did not exist within the cache.
     * @throws moodle_exception
     */
    public function get($key, $strictness = IGNORE_MISSING) {
        if ($this->requirelockingread && $this->check_lock_state($key) === false) {
            // Read locking required and someone else has the read lock.
            return false;
        }
        return parent::get($key, $strictness);
    }

    /**
     * Retrieves an array of values for an array of keys.
     *
     * Using this function comes with potential performance implications.
     * Not all cache stores will support get_many/set_many operations and in order to replicate this functionality will call
     * the equivalent singular method for each item provided.
     * This should not deter you from using this function as there is a performance benefit in situations where the cache store
     * does support it, but you should be aware of this fact.
     *
     * @param array $keys The keys of the data being requested.
     * @param int $strictness One of IGNORE_MISSING or MUST_EXIST.
     * @return array An array of key value pairs for the items that could be retrieved from the cache.
     *      If MUST_EXIST was used and not all keys existed within the cache then an exception will be thrown.
     *      Otherwise any key that did not exist will have a data value of false within the results.
     * @throws moodle_exception
     */
    public function get_many(array $keys, $strictness = IGNORE_MISSING) {
        if ($this->requirelockingread) {
            foreach ($keys as $id => $key) {
                $lock =$this->acquire_lock($key);
                if (!$lock) {
                    if ($strictness === MUST_EXIST) {
                        throw new coding_exception('Could not acquire read locks for all of the items being requested.');
                    } else {
                        // Can't return this as we couldn't get a read lock.
                        unset($keys[$id]);
                    }
                }

            }
        }
        return parent::get_many($keys, $strictness);
    }

    /**
     * Delete the given key from the cache.
     *
     * @param string|int $key The key to delete.
     * @param bool $recurse When set to true the key will also be deleted from all stacked cache loaders and their stores.
     *     This happens by default and ensure that all the caches are consistent. It is NOT recommended to change this.
     * @return bool True of success, false otherwise.
     */
    public function delete($key, $recurse = true) {
        if ($this->requirelockingwrite && !$this->acquire_lock($key)) {
            return false;
        }
        $result = parent::delete($key, $recurse);
        if ($this->requirelockingwrite && !$this->release_lock($key)) {
            debugging('Failed to release cache lock on delete operation... this should not happen.', DEBUG_DEVELOPER);
        }
        return $result;
    }

    /**
     * Delete all of the given keys from the cache.
     *
     * @param array $keys The key to delete.
     * @param bool $recurse When set to true the key will also be deleted from all stacked cache loaders and their stores.
     *     This happens by default and ensure that all the caches are consistent. It is NOT recommended to change this.
     * @return int The number of items successfully deleted.
     */
    public function delete_many(array $keys, $recurse = true) {
        if ($this->requirelockingwrite) {
            $locks = array();
            foreach ($keys as $id => $key) {
                if ($this->acquire_lock($key)) {
                    $locks[] = $key;
                } else {
                    unset($keys[$id]);
                }
            }
        }
        $result = parent::delete_many($keys, $recurse);
        if ($this->requirelockingwrite) {
            foreach ($locks as $key) {
                if ($this->release_lock($key)) {
                    debugging('Failed to release cache lock on delete_many operation... this should not happen.', DEBUG_DEVELOPER);
                }
            }
        }
        return $result;
    }
}

/**
 * A session cache.
 *
 * This class is used for session caches returned by the cache::make methods.
 *
 * It differs from the application loader in a couple of noteable ways:
 *    1. Sessions are always expected to be persistent.
 *       Because of this we don't ever use the persist cache and instead a session array
 *       containing all of the data is maintained by this object.
 *    2. Session data for a loader instance (store + definition) is consolidate into a
 *       single array for storage within the store.
 *       Along with this we embed a lastaccessed time with the data. This way we can
 *       check sessions for a last access time.
 *    3. Session stores are required to support key searching and must
 *       implement cache_is_searchable. This ensures stores used for the cache can be
 *       targetted for garbage collection of session data.
 *
 * This cache class should never be interacted with directly. Instead you should always use the cache::make methods.
 * It is technically possible to call those methods through this class however there is no guarantee that you will get an
 * instance of this class back again.
 *
 * @todo we should support locking in the session as well. Should be pretty simple to set up.
 *
 * @internal don't use me directly.
 *
 * @package    core
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cache_session extends cache {
    /**
     * The user the session has been established for.
     * @var int
     */
    protected static $loadeduserid = null;

    /**
     * The userid this cache is currently using.
     * @var int
     */
    protected $currentuserid = null;

    /**
     * The session id we are currently using.
     * @var array
     */
    protected $sessionid = null;

    /**
     * The session data for the above session id.
     * @var array
     */
    protected $session = null;

    /**
     * Constant used to prefix keys.
     */
    const KEY_PREFIX = 'sess_';

    /**
     * Override the cache::construct method.
     *
     * This function gets overriden so that we can process any invalidation events if need be.
     * If the definition doesn't have any invalidation events then this occurs exactly as it would for the cache class.
     * Otherwise we look at the last invalidation time and then check the invalidation data for events that have occured
     * between then now.
     *
     * You should not call this method from your code, instead you should use the cache::make methods.
     *
     * @param cache_definition $definition
     * @param cache_store $store
     * @param cache_loader|cache_data_source $loader
     * @return void
     */
    public function __construct(cache_definition $definition, cache_store $store, $loader = null) {
        // First up copy the loadeduserid to the current user id.
        $this->currentuserid = self::$loadeduserid;
        parent::__construct($definition, $store, $loader);
        if ($definition->has_invalidation_events()) {
            $lastinvalidation = $this->get('lastsessioninvalidation');
            if ($lastinvalidation === false) {
                // This is a new session, there won't be anything to invalidate. Set the time of the last invalidation and
                // move on.
                $this->set('lastsessioninvalidation', cache::now());
                return;
            } else if ($lastinvalidation == cache::now()) {
                // We've already invalidated during this request.
                return;
            }

            // Get the event invalidation cache.
            $cache = cache::make('core', 'eventinvalidation');
            $events = $cache->get_many($definition->get_invalidation_events());
            $todelete = array();
            $purgeall = false;
            // Iterate the returned data for the events.
            foreach ($events as $event => $keys) {
                if ($keys === false) {
                    // No data to be invalidated yet.
                    continue;
                }
                // Look at each key and check the timestamp.
                foreach ($keys as $key => $timestamp) {
                    // If the timestamp of the event is more than or equal to the last invalidation (happened between the last
                    // invalidation and now)then we need to invaliate the key.
                    if ($timestamp >= $lastinvalidation) {
                        if ($key === 'purged') {
                            $purgeall = true;
                            break;
                        } else {
                            $todelete[] = $key;
                        }
                    }
                }
            }
            if ($purgeall) {
                $this->purge();
            } else if (!empty($todelete)) {
                $todelete = array_unique($todelete);
                $this->delete_many($todelete);
            }
            // Set the time of the last invalidation.
            $this->set('lastsessioninvalidation', cache::now());
        }
    }

    /**
     * Parses the key turning it into a string (or array is required) suitable to be passed to the cache store.
     *
     * This function is called for every operation that uses keys. For this reason we use this function to also check
     * that the current user is the same as the user who last used this cache.
     *
     * On top of that if prepends the string 'sess_' to the start of all keys. The _ ensures things are easily identifiable.
     *
     * @param string|int $key As passed to get|set|delete etc.
     * @return string|array String unless the store supports multi-identifiers in which case an array if returned.
     */
    protected function parse_key($key) {
        if ($key === 'lastaccess') {
            $key = '__lastaccess__';
        }
        return 'sess_'.parent::parse_key($key);
    }

    /**
     * Check that this cache instance is tracking the current user.
     */
    protected function check_tracked_user() {
        if (isset($_SESSION['USER']->id)) {
            // Get the id of the current user.
            $new = $_SESSION['USER']->id;
        } else {
            // No user set up yet.
            $new = 0;
        }
        if ($new !== self::$loadeduserid) {
            // The current user doesn't match the tracked userid for this request.
            if (!is_null(self::$loadeduserid)) {
                // Purge the data we have for the old user.
                // This way we don't bloat the session.
                $this->purge();
                // Update the session id just in case!
                $this->sessionid = session_id();
            }
            self::$loadeduserid = $new;
            $this->currentuserid = $new;
        } else if ($new !== $this->currentuserid) {
            // The current user matches the loaded user but not the user last used by this cache.
            $this->purge();
            $this->currentuserid = $new;
            // Update the session id just in case!
            $this->sessionid = session_id();
        }
    }

    /**
     * Gets the session data.
     *
     * @param bool $force If true the session data will be loaded from the store again.
     * @return array An array of session data.
     */
    protected function get_session_data($force = false) {
        if ($this->sessionid === null) {
            $this->sessionid = session_id();
        }
        if (is_array($this->session) && !$force) {
            return $this->session;
        }
        $session = parent::get($this->sessionid);
        if ($session === false) {
            $session = array();
        }
        // We have to write here to ensure that the lastaccess time is recorded.
        // And also in order to ensure the session entry exists as when we save it on __destruct
        // $CFG is likely to have already been destroyed.
        $this->save_session($session);
        return $this->session;
    }

    /**
     * Saves the session data.
     *
     * This function also updates the last access time.
     *
     * @param array $session
     * @return bool
     */
    protected function save_session(array $session) {
        $session['lastaccess'] = time();
        $this->session = $session;
        return parent::set($this->sessionid, $this->session);
    }

    /**
     * Retrieves the value for the given key from the cache.
     *
     * @param string|int $key The key for the data being requested.
     *      It can be any structure although using a scalar string or int is recommended in the interests of performance.
     *      In advanced cases an array may be useful such as in situations requiring the multi-key functionality.
     * @param int $strictness One of IGNORE_MISSING | MUST_EXIST
     * @return mixed|false The data from the cache or false if the key did not exist within the cache.
     * @throws moodle_exception
     */
    public function get($key, $strictness = IGNORE_MISSING) {
        // Check the tracked user.
        $this->check_tracked_user();
        // 2. Parse the key.
        $parsedkey = $this->parse_key($key);
        // 3. Get it from the store.
        $result = false;
        $session = $this->get_session_data();
        if (array_key_exists($parsedkey, $session)) {
            $result = $session[$parsedkey];
            if ($result instanceof cache_ttl_wrapper) {
                if ($result->has_expired()) {
                    $this->get_store()->delete($parsedkey);
                    $result = false;
                } else {
                    $result = $result->data;
                }
            }
            if ($result instanceof cache_cached_object) {
                $result = $result->restore_object();
            }
        }
        // 4. Load if from the loader/datasource if we don't already have it.
        $setaftervalidation = false;
        if ($result === false) {
            if ($this->perfdebug) {
                cache_helper::record_cache_miss('**static session**', $this->get_definition()->get_id());
            }
            if ($this->get_loader() !== false) {
                // We must pass the original (unparsed) key to the next loader in the chain.
                // The next loader will parse the key as it sees fit. It may be parsed differently
                // depending upon the capabilities of the store associated with the loader.
                $result = $this->get_loader()->get($key);
            } else if ($this->get_datasource() !== false) {
                $result = $this->get_datasource()->load_for_cache($key);
            }
            $setaftervalidation = ($result !== false);
        } else if ($this->perfdebug) {
            cache_helper::record_cache_hit('**static session**', $this->get_definition()->get_id());
        }
        // 5. Validate strictness.
        if ($strictness === MUST_EXIST && $result === false) {
            throw new moodle_exception('Requested key did not exist in any cache stores and could not be loaded.');
        }
        // 6. Set it to the store if we got it from the loader/datasource.
        if ($setaftervalidation) {
            $this->set($key, $result);
        }
        // 7. Make sure we don't pass back anything that could be a reference.
        //    We don't want people modifying the data in the cache.
        if (!is_scalar($result)) {
            // If data is an object it will be a reference.
            // If data is an array if may contain references.
            // We want to break references so that the cache cannot be modified outside of itself.
            // Call the function to unreference it (in the best way possible).
            $result = $this->unref($result);
        }
        return $result;
    }

    /**
     * Sends a key => value pair to the cache.
     *
     * <code>
     * // This code will add four entries to the cache, one for each url.
     * $cache->set('main', 'http://moodle.org');
     * $cache->set('docs', 'http://docs.moodle.org');
     * $cache->set('tracker', 'http://tracker.moodle.org');
     * $cache->set('qa', 'http://qa.moodle.net');
     * </code>
     *
     * @param string|int $key The key for the data being requested.
     *      It can be any structure although using a scalar string or int is recommended in the interests of performance.
     *      In advanced cases an array may be useful such as in situations requiring the multi-key functionality.
     * @param mixed $data The data to set against the key.
     * @return bool True on success, false otherwise.
     */
    public function set($key, $data) {
        $this->check_tracked_user();
        if ($this->perfdebug) {
            cache_helper::record_cache_set('**static session**', $this->get_definition()->get_id());
        }
        if (is_object($data) && $data instanceof cacheable_object) {
            $data = new cache_cached_object($data);
        } else if (!is_scalar($data)) {
            // If data is an object it will be a reference.
            // If data is an array if may contain references.
            // We want to break references so that the cache cannot be modified outside of itself.
            // Call the function to unreference it (in the best way possible).
            $data = $this->unref($data);
        }
        // We dont' support native TTL here as we consolidate data for sessions.
        if ($this->has_a_ttl()) {
            $data = new cache_ttl_wrapper($data, $this->get_definition()->get_ttl());
        }
        $session = $this->get_session_data();
        $session[$this->parse_key($key)] = $data;
        return $this->save_session($session);
    }

    /**
     * Delete the given key from the cache.
     *
     * @param string|int $key The key to delete.
     * @param bool $recurse When set to true the key will also be deleted from all stacked cache loaders and their stores.
     *     This happens by default and ensure that all the caches are consistent. It is NOT recommended to change this.
     * @return bool True of success, false otherwise.
     */
    public function delete($key, $recurse = true) {
        $this->check_tracked_user();
        $parsedkey = $this->parse_key($key);
        if ($recurse && $this->get_loader() !== false) {
            // Delete from the bottom of the stack first.
            $this->get_loader()->delete($key, $recurse);
        }
        $session = $this->get_session_data();
        unset($session[$parsedkey]);
        return $this->save_session($session);
    }

    /**
     * Retrieves an array of values for an array of keys.
     *
     * Using this function comes with potential performance implications.
     * Not all cache stores will support get_many/set_many operations and in order to replicate this functionality will call
     * the equivalent singular method for each item provided.
     * This should not deter you from using this function as there is a performance benefit in situations where the cache store
     * does support it, but you should be aware of this fact.
     *
     * @param array $keys The keys of the data being requested.
     *      Each key can be any structure although using a scalar string or int is recommended in the interests of performance.
     *      In advanced cases an array may be useful such as in situations requiring the multi-key functionality.
     * @param int $strictness One of IGNORE_MISSING or MUST_EXIST.
     * @return array An array of key value pairs for the items that could be retrieved from the cache.
     *      If MUST_EXIST was used and not all keys existed within the cache then an exception will be thrown.
     *      Otherwise any key that did not exist will have a data value of false within the results.
     * @throws moodle_exception
     */
    public function get_many(array $keys, $strictness = IGNORE_MISSING) {
        $this->check_tracked_user();
        $return = array();
        foreach ($keys as $key) {
            $return[$key] = $this->get($key, $strictness);
        }
        return $return;
    }

    /**
     * Delete all of the given keys from the cache.
     *
     * @param array $keys The key to delete.
     * @param bool $recurse When set to true the key will also be deleted from all stacked cache loaders and their stores.
     *     This happens by default and ensure that all the caches are consistent. It is NOT recommended to change this.
     * @return int The number of items successfully deleted.
     */
    public function delete_many(array $keys, $recurse = true) {
        $this->check_tracked_user();
        $parsedkeys = array_map(array($this, 'parse_key'), $keys);
        if ($recurse && $this->get_loader() !== false) {
            // Delete from the bottom of the stack first.
            $this->get_loader()->delete_many($keys, $recurse);
        }
        $session = $this->get_session_data();
        foreach ($parsedkeys as $parsedkey) {
            unset($session[$parsedkey]);
        }
        $this->save_session($session);
        return count($keys);
    }

    /**
     * Sends several key => value pairs to the cache.
     *
     * Using this function comes with potential performance implications.
     * Not all cache stores will support get_many/set_many operations and in order to replicate this functionality will call
     * the equivalent singular method for each item provided.
     * This should not deter you from using this function as there is a performance benefit in situations where the cache store
     * does support it, but you should be aware of this fact.
     *
     * <code>
     * // This code will add four entries to the cache, one for each url.
     * $cache->set_many(array(
     *     'main' => 'http://moodle.org',
     *     'docs' => 'http://docs.moodle.org',
     *     'tracker' => 'http://tracker.moodle.org',
     *     'qa' => ''http://qa.moodle.net'
     * ));
     * </code>
     *
     * @param array $keyvaluearray An array of key => value pairs to send to the cache.
     * @return int The number of items successfully set. It is up to the developer to check this matches the number of items.
     *      ... if they care that is.
     */
    public function set_many(array $keyvaluearray) {
        $this->check_tracked_user();
        $session = $this->get_session_data();
        $simulatettl = $this->has_a_ttl();
        foreach ($keyvaluearray as $key => $value) {
            if (is_object($value) && $value instanceof cacheable_object) {
                $value = new cache_cached_object($value);
            } else if (!is_scalar($value)) {
                // If data is an object it will be a reference.
                // If data is an array if may contain references.
                // We want to break references so that the cache cannot be modified outside of itself.
                // Call the function to unreference it (in the best way possible).
                $value = $this->unref($value);
            }
            if ($simulatettl) {
                $value = new cache_ttl_wrapper($value, $this->get_definition()->get_ttl());
            }
            $parsedkey = $this->parse_key($key);
            $session[$parsedkey] = $value;
        }
        if ($this->perfdebug) {
            cache_helper::record_cache_set($this->storetype, $this->get_definition()->get_id());
        }
        $this->save_session($session);
        return count($keyvaluearray);
    }

    /**
     * Purges the cache store, and loader if there is one.
     *
     * @return bool True on success, false otherwise
     */
    public function purge() {
        // 1. Purge the session object.
        $this->session = array();
        // 2. Delete the record for this users session from the store.
        $this->get_store()->delete($this->sessionid);
        // 3. Optionally purge any stacked loaders in the same way.
        if ($this->get_loader()) {
            $this->get_loader()->delete($this->sessionid);
        }
        return true;
    }

    /**
     * Test is a cache has a key.
     *
     * The use of the has methods is strongly discouraged. In a high load environment the cache may well change between the
     * test and any subsequent action (get, set, delete etc).
     * Instead it is recommended to write your code in such a way they it performs the following steps:
     * <ol>
     * <li>Attempt to retrieve the information.</li>
     * <li>Generate the information.</li>
     * <li>Attempt to set the information</li>
     * </ol>
     *
     * Its also worth mentioning that not all stores support key tests.
     * For stores that don't support key tests this functionality is mimicked by using the equivalent get method.
     * Just one more reason you should not use these methods unless you have a very good reason to do so.
     *
     * @param string|int $key
     * @param bool $tryloadifpossible If set to true, the cache doesn't contain the key, and there is another cache loader or
     *      data source then the code will try load the key value from the next item in the chain.
     * @return bool True if the cache has the requested key, false otherwise.
     */
    public function has($key, $tryloadifpossible = false) {
        $this->check_tracked_user();
        $parsedkey = $this->parse_key($key);
        $session = $this->get_session_data();
        $has = false;
        if ($this->has_a_ttl()) {
            // The data has a TTL and the store doesn't support it natively.
            // We must fetch the data and expect a ttl wrapper.
            if (array_key_exists($parsedkey, $session)) {
                $data = $session[$parsedkey];
                $has = ($data instanceof cache_ttl_wrapper && !$data->has_expired());
            }
        } else {
            $has = array_key_exists($parsedkey, $session);
        }
        if (!$has && $tryloadifpossible) {
            if ($this->get_loader() !== false) {
                $result = $this->get_loader()->get($key);
            } else if ($this->get_datasource() !== null) {
                $result = $this->get_datasource()->load_for_cache($key);
            }
            $has = ($result !== null);
            if ($has) {
                $this->set($key, $result);
            }
        }
        return $has;
    }

    /**
     * Test is a cache has all of the given keys.
     *
     * It is strongly recommended to avoid the use of this function if not absolutely required.
     * In a high load environment the cache may well change between the test and any subsequent action (get, set, delete etc).
     *
     * Its also worth mentioning that not all stores support key tests.
     * For stores that don't support key tests this functionality is mimicked by using the equivalent get method.
     * Just one more reason you should not use these methods unless you have a very good reason to do so.
     *
     * @param array $keys
     * @return bool True if the cache has all of the given keys, false otherwise.
     */
    public function has_all(array $keys) {
        $this->check_tracked_user();
        $session = $this->get_session_data();
        foreach ($keys as $key) {
            $has = false;
            $parsedkey = $this->parse_key($key);
            if ($this->has_a_ttl()) {
                // The data has a TTL and the store doesn't support it natively.
                // We must fetch the data and expect a ttl wrapper.
                if (array_key_exists($parsedkey, $session)) {
                    $data = $session[$parsedkey];
                    $has = ($data instanceof cache_ttl_wrapper && !$data->has_expired());
                }
            } else {
                $has = array_key_exists($parsedkey, $session);
            }
            if (!$has) {
                return false;
            }
        }
        return true;
    }

    /**
     * Test if a cache has at least one of the given keys.
     *
     * It is strongly recommended to avoid the use of this function if not absolutely required.
     * In a high load environment the cache may well change between the test and any subsequent action (get, set, delete etc).
     *
     * Its also worth mentioning that not all stores support key tests.
     * For stores that don't support key tests this functionality is mimicked by using the equivalent get method.
     * Just one more reason you should not use these methods unless you have a very good reason to do so.
     *
     * @param array $keys
     * @return bool True if the cache has at least one of the given keys
     */
    public function has_any(array $keys) {
        $this->check_tracked_user();
        $session = $this->get_session_data();
        foreach ($keys as $key) {
            $has = false;
            $parsedkey = $this->parse_key($key);
            if ($this->has_a_ttl()) {
                // The data has a TTL and the store doesn't support it natively.
                // We must fetch the data and expect a ttl wrapper.
                if (array_key_exists($parsedkey, $session)) {
                    $data = $session[$parsedkey];
                    $has = ($data instanceof cache_ttl_wrapper && !$data->has_expired());
                }
            } else {
                $has = array_key_exists($parsedkey, $session);
            }
            if ($has) {
                return true;
            }
        }
        return false;
    }

    /**
     * The session loader never uses the persist cache.
     * Instead it stores things in the static $session variable. Shared between all session loaders.
     *
     * @return bool
     */
    protected function is_using_persist_cache() {
        return false;
    }
}

/**
 * An request cache.
 *
 * This class is used for request caches returned by the cache::make methods.
 *
 * This cache class should never be interacted with directly. Instead you should always use the cache::make methods.
 * It is technically possible to call those methods through this class however there is no guarantee that you will get an
 * instance of this class back again.
 *
 * @internal don't use me directly.
 *
 * @package    core
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cache_request extends cache {
    // This comment appeases code pre-checker ;) !
}