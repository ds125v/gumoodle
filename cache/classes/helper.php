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
 * Cache helper class
 *
 * This file is part of Moodle's cache API, affectionately called MUC.
 * It contains the components that are requried in order to use caching.
 *
 * @package    core
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The cache helper class.
 *
 * The cache helper class provides common functionality to the cache API and is useful to developers within to interact with
 * the cache API in a general way.
 *
 * @package    core
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cache_helper {

    /**
     * Statistics gathered by the cache API during its operation will be used here.
     * @static
     * @var array
     */
    protected static $stats = array();

    /**
     * The instance of the cache helper.
     * @var cache_helper
     */
    protected static $instance;

    /**
     * The site identifier used by the cache.
     * Set the first time get_site_identifier is called.
     * @var string
     */
    protected static $siteidentifier = null;

    /**
     * Returns true if the cache API can be initialised before Moodle has finished initialising itself.
     *
     * This check is essential when trying to cache the likes of configuration information. It checks to make sure that the cache
     * configuration file has been created which allows use to set up caching when ever is required.
     *
     * @return bool
     */
    public static function ready_for_early_init() {
        return cache_config::config_file_exists();
    }

    /**
     * Returns an instance of the cache_helper.
     *
     * This is designed for internal use only and acts as a static store.
     * @staticvar null $instance
     * @return cache_helper
     */
    protected static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new cache_helper();
        }
        return self::$instance;
    }

    /**
     * Constructs an instance of the cache_helper class. Again for internal use only.
     */
    protected function __construct() {
        // Nothing to do here, just making sure you can't get an instance of this.
    }

    /**
     * Used as a data store for initialised definitions.
     * @var array
     */
    protected $definitions = array();

    /**
     * Used as a data store for initialised cache stores
     * We use this because we want to avoid establishing multiple instances of a single store.
     * @var array
     */
    protected $stores = array();

    /**
     * Returns the class for use as a cache loader for the given mode.
     *
     * @param int $mode One of cache_store::MODE_
     * @return string
     * @throws coding_exception
     */
    public static function get_class_for_mode($mode) {
        switch ($mode) {
            case cache_store::MODE_APPLICATION :
                return 'cache_application';
            case cache_store::MODE_REQUEST :
                return 'cache_request';
            case cache_store::MODE_SESSION :
                return 'cache_session';
        }
        throw new coding_exception('Unknown cache mode passed. Must be one of cache_store::MODE_*');
    }

    /**
     * Returns the cache stores to be used with the given definition.
     * @param cache_definition $definition
     * @return array
     */
    public static function get_cache_stores(cache_definition $definition) {
        $instance = cache_config::instance();
        $stores = $instance->get_stores_for_definition($definition);
        $stores = self::initialise_cachestore_instances($stores, $definition);
        return $stores;
    }

    /**
     * Internal function for initialising an array of stores against a given cache definition.
     *
     * @param array $stores
     * @param cache_definition $definition
     * @return array
     */
    protected static function initialise_cachestore_instances(array $stores, cache_definition $definition) {
        $return = array();
        $factory = cache_factory::instance();
        foreach ($stores as $name => $details) {
            $store = $factory->create_store_from_config($name, $details, $definition);
            if ($store !== false) {
                $return[] = $store;
            }
        }
        return $return;
    }

    /**
     * Returns a cache_lock instance suitable for use with the store.
     *
     * @param cache_store $store
     * @return cache_lock_interface
     */
    public static function get_cachelock_for_store(cache_store $store) {
        $instance = cache_config::instance();
        $lockconf = $instance->get_lock_for_store($store->my_name());
        $factory = cache_factory::instance();
        return $factory->create_lock_instance($lockconf);
    }

    /**
     * Returns an array of plugins without using core methods.
     *
     * This function explicitly does NOT use core functions as it will in some circumstances be called before Moodle has
     * finished initialising. This happens when loading configuration for instance.
     *
     * @return string
     */
    public static function early_get_cache_plugins() {
        global $CFG;
        $result = array();
        $ignored = array('CVS', '_vti_cnf', 'simpletest', 'db', 'yui', 'tests');
        $fulldir = $CFG->dirroot.'/cache/stores';
        $items = new DirectoryIterator($fulldir);
        foreach ($items as $item) {
            if ($item->isDot() or !$item->isDir()) {
                continue;
            }
            $pluginname = $item->getFilename();
            if (in_array($pluginname, $ignored)) {
                continue;
            }
            if (!is_valid_plugin_name($pluginname)) {
                // Better ignore plugins with problematic names here.
                continue;
            }
            $result[$pluginname] = $fulldir.'/'.$pluginname;
            unset($item);
        }
        unset($items);
        return $result;
    }

    /**
     * Invalidates a given set of keys from a given definition.
     *
     * @todo Invalidating by definition should also add to the event cache so that sessions can be invalidated (when required).
     *
     * @param string $component
     * @param string $area
     * @param array $identifiers
     * @param array $keys
     * @return boolean
     */
    public static function invalidate_by_definition($component, $area, array $identifiers = array(), $keys = array()) {
        $cache = cache::make($component, $area, $identifiers);
        if (is_array($keys)) {
            $cache->delete_many($keys);
        } else if (is_scalar($keys)) {
            $cache->delete($keys);
        } else {
            throw new coding_exception('cache_helper::invalidate_by_definition only accepts $keys as array, or scalar.');
        }
        return true;
    }

    /**
     * Invalidates a given set of keys by means of an event.
     *
     * @todo add support for identifiers to be supplied and utilised.
     *
     * @param string $event
     * @param array $keys
     */
    public static function invalidate_by_event($event, array $keys) {
        $instance = cache_config::instance();
        $invalidationeventset = false;
        $factory = cache_factory::instance();
        foreach ($instance->get_definitions() as $name => $definitionarr) {
            $definition = cache_definition::load($name, $definitionarr);
            if ($definition->invalidates_on_event($event)) {
                // OK at this point we know that the definition has information to invalidate on the event.
                // There are two routes, either its an application cache in which case we can invalidate it now.
                // or it is a persistent cache that also needs to be invalidated now.
                if ($definition->get_mode() === cache_store::MODE_APPLICATION || $definition->should_be_persistent()) {
                    $cache = $factory->create_cache_from_definition($definition->get_component(), $definition->get_area());
                    $cache->delete_many($keys);
                }

                // We need to flag the event in the "Event invalidation" cache if it hasn't already happened.
                if ($invalidationeventset === false) {
                    // Get the event invalidation cache.
                    $cache = cache::make('core', 'eventinvalidation');
                    // Get any existing invalidated keys for this cache.
                    $data = $cache->get($event);
                    if ($data === false) {
                        // There are none.
                        $data = array();
                    }
                    // Add our keys to them with the current cache timestamp.
                    foreach ($keys as $key) {
                        $data[$key] = cache::now();
                    }
                    // Set that data back to the cache.
                    $cache->set($event, $data);
                    // This only needs to occur once.
                    $invalidationeventset = true;
                }
            }
        }
    }

    /**
     * Purges the cache for a specific definition.
     *
     * @todo MDL-36660: Change the signature: $aggregate must be added.
     *
     * @param string $component
     * @param string $area
     * @param array $identifiers
     * @return bool
     */
    public static function purge_by_definition($component, $area, array $identifiers = array()) {
        // Create the cache.
        $cache = cache::make($component, $area, $identifiers);
        // Initialise, in case of a store.
        if ($cache instanceof cache_store) {
            $factory = cache_factory::instance();
            // TODO MDL-36660: Providing $aggregate is required for purging purposes: $definition->get_id()
            $definition = $factory->create_definition($component, $area, null);
            $definition->set_identifiers($identifiers);
            $cache->initialise($definition);
        }
        // Purge baby, purge.
        $cache->purge();
        return true;
    }

    /**
     * Purges a cache of all information on a given event.
     *
     * @param string $event
     */
    public static function purge_by_event($event) {
        $instance = cache_config::instance();
        $invalidationeventset = false;
        $factory = cache_factory::instance();
        foreach ($instance->get_definitions() as $name => $definitionarr) {
            $definition = cache_definition::load($name, $definitionarr);
            if ($definition->invalidates_on_event($event)) {
                // Check if this definition would result in a persistent loader being in use.
                if ($definition->should_be_persistent()) {
                    // There may be a persistent cache loader. Lets purge that first so that any persistent data is removed.
                    $cache = $factory->create_cache_from_definition($definition->get_component(), $definition->get_area());
                    $cache->purge();
                }
                // Get all of the store instances that are in use for this store.
                $stores = $factory->get_store_instances_in_use($definition);
                foreach ($stores as $store) {
                    // Purge each store individually.
                    $store->purge();
                }
                // We need to flag the event in the "Event invalidation" cache if it hasn't already happened.
                if ($invalidationeventset === false) {
                    // Get the event invalidation cache.
                    $cache = cache::make('core', 'eventinvalidation');
                    // Create a key to invalidate all.
                    $data = array(
                        'purged' => cache::now()
                    );
                    // Set that data back to the cache.
                    $cache->set($event, $data);
                    // This only needs to occur once.
                    $invalidationeventset = true;
                }
            }
        }
    }

    /**
     * Ensure that the stats array is ready to collect information for the given store and definition.
     * @param string $store
     * @param string $definition
     */
    protected static function ensure_ready_for_stats($store, $definition) {
        if (!array_key_exists($definition, self::$stats)) {
            self::$stats[$definition] = array(
                $store => array(
                    'hits' => 0,
                    'misses' => 0,
                    'sets' => 0,
                )
            );
        } else if (!array_key_exists($store, self::$stats[$definition])) {
            self::$stats[$definition][$store] = array(
                'hits' => 0,
                'misses' => 0,
                'sets' => 0,
            );
        }
    }

    /**
     * Record a cache hit in the stats for the given store and definition.
     *
     * @param string $store
     * @param string $definition
     */
    public static function record_cache_hit($store, $definition) {
        self::ensure_ready_for_stats($store, $definition);
        self::$stats[$definition][$store]['hits']++;
    }

    /**
     * Record a cache miss in the stats for the given store and definition.
     *
     * @param string $store
     * @param string $definition
     */
    public static function record_cache_miss($store, $definition) {
        self::ensure_ready_for_stats($store, $definition);
        self::$stats[$definition][$store]['misses']++;
    }

    /**
     * Record a cache set in the stats for the given store and definition.
     *
     * @param string $store
     * @param string $definition
     */
    public static function record_cache_set($store, $definition) {
        self::ensure_ready_for_stats($store, $definition);
        self::$stats[$definition][$store]['sets']++;
    }

    /**
     * Return the stats collected so far.
     * @return array
     */
    public static function get_stats() {
        return self::$stats;
    }

    /**
     * Purge all of the cache stores of all of their data.
     *
     * Think twice before calling this method. It will purge **ALL** caches regardless of whether they have been used recently or
     * anything. This will involve full setup of the cache + the purge operation. On a site using caching heavily this WILL be
     * painful.
     */
    public static function purge_all() {
        $config = cache_config::instance();

        foreach ($config->get_all_stores() as $store) {
            self::purge_store($store['name']);
        }
    }

    /**
     * Purges a store given its name.
     *
     * @param string $storename
     * @return bool
     */
    public static function purge_store($storename) {
        $config = cache_config::instance();

        $stores = $config->get_all_stores();
        if (!array_key_exists($storename, $stores)) {
            // The store does not exist.
            return false;
        }

        $store = $stores[$storename];
        $class = $store['class'];

        // Found the store: is it ready?
        $instance = new $class($store['name'], $store['configuration']);
        if (!$instance->is_ready()) {
            unset($instance);
            return false;
        }

        foreach ($config->get_definitions_by_store($storename) as $id => $definition) {
            $definition = cache_definition::load($id, $definition);
            $instance = new $class($store['name'], $store['configuration']);
            $instance->initialise($definition);
            $instance->purge();
            unset($instance);
        }

        return true;
    }

    /**
     * Returns the translated name of the definition.
     *
     * @param cache_definition $definition
     * @return lang_string
     */
    public static function get_definition_name($definition) {
        if ($definition instanceof cache_definition) {
            return $definition->get_name();
        }
        $identifier = 'cachedef_'.clean_param($definition['area'], PARAM_STRINGID);
        $component = $definition['component'];
        if ($component === 'core') {
            $component = 'cache';
        }
        return new lang_string($identifier, $component);
    }

    /**
     * Hashes a descriptive key to make it shorter and still unique.
     * @param string|int $key
     * @param cache_definition $definition
     * @return string
     */
    public static function hash_key($key, cache_definition $definition) {
        if ($definition->uses_simple_keys()) {
            if (debugging() && preg_match('#[^a-zA-Z0-9_]#', $key)) {
                throw new coding_exception('Cache definition '.$definition->get_id().' requires simple keys. Invalid key provided.', $key);
            }
            // We put the key first so that we can be sure the start of the key changes.
            return (string)$key . '-' . $definition->generate_single_key_prefix();
        }
        $key = $definition->generate_single_key_prefix() . '-' . $key;
        return sha1($key);
    }

    /**
     * Finds all definitions and updates them within the cache config file.
     *
     * @param bool $coreonly If set to true only core definitions will be updated.
     */
    public static function update_definitions($coreonly = false) {
        global $CFG;
        // Include locallib.
        require_once($CFG->dirroot.'/cache/locallib.php');
        // First update definitions.
        cache_config_writer::update_definitions($coreonly);
        // Second reset anything we have already initialised to ensure we're all up to date.
        cache_factory::reset();
    }

    /**
     * Update the site identifier stored by the cache API.
     *
     * @param string $siteidentifier
     * @return string The new site identifier.
     */
    public static function update_site_identifier($siteidentifier) {
        global $CFG;
        // Include locallib.
        require_once($CFG->dirroot.'/cache/locallib.php');
        $factory = cache_factory::instance();
        $factory->updating_started();
        $config = $factory->create_config_instance(true);
        $siteidentifier = $config->update_site_identifier($siteidentifier);
        $factory->updating_finished();
        cache_factory::reset();
        return $siteidentifier;
    }

    /**
     * Returns the site identifier.
     *
     * @return string
     */
    public static function get_site_identifier() {
        global $CFG;
        if (!is_null(self::$siteidentifier)) {
            return self::$siteidentifier;
        }
        // If site identifier hasn't been collected yet attempt to get it from the cache config.
        $factory = cache_factory::instance();
        // If the factory is initialising then we don't want to try to get it from the config or we risk
        // causing the cache to enter an infinite initialisation loop.
        if (!$factory->is_initialising()) {
            $config = $factory->create_config_instance();
            self::$siteidentifier = $config->get_site_identifier();
        }
        if (is_null(self::$siteidentifier)) {
            // If the site identifier is still null then config isn't aware of it yet.
            // We'll see if the CFG is loaded, and if not we will just use unknown.
            // It's very important here that we don't use get_config. We don't want an endless cache loop!
            if (!empty($CFG->siteidentifier)) {
                self::$siteidentifier = self::update_site_identifier($CFG->siteidentifier);
            } else {
                // It's not being recorded in MUC's config and the config data hasn't been loaded yet.
                // Likely we are initialising.
                return 'unknown';
            }
        }
        return self::$siteidentifier;
    }

    /**
     * Returns the site version.
     *
     * @return string
     */
    public static function get_site_version() {
        global $CFG;
        return (string)$CFG->version;
    }

    /**
     * Runs cron routines for MUC.
     */
    public static function cron() {
        self::clean_old_session_data(true);
    }

    /**
     * Cleans old session data from cache stores used for session based definitions.
     *
     * @param bool $output If set to true output will be given.
     */
    public static function clean_old_session_data($output = false) {
        global $CFG;
        if ($output) {
            mtrace('Cleaning up stale session data from cache stores.');
        }
        $factory = cache_factory::instance();
        $config = $factory->create_config_instance();
        $definitions = $config->get_definitions();
        $purgetime = time() - $CFG->sessiontimeout;
        foreach ($definitions as $definitionarray) {
            // We are only interested in session caches.
            if (!($definitionarray['mode'] & cache_store::MODE_SESSION)) {
                continue;
            }
            $definition = $factory->create_definition($definitionarray['component'], $definitionarray['area']);
            $stores = $config->get_stores_for_definition($definition);
            // Turn them into store instances.
            $stores = self::initialise_cachestore_instances($stores, $definition);
            // Initialise all of the stores used for that definition.
            foreach ($stores as $store) {
                // If the store doesn't support searching we can skip it.
                if (!($store instanceof cache_is_searchable)) {
                    debugging('Cache stores used for session definitions should ideally be searchable.', DEBUG_DEVELOPER);
                    continue;
                }
                // Get all of the keys.
                $keys = $store->find_by_prefix(cache_session::KEY_PREFIX);
                $todelete = array();
                foreach ($store->get_many($keys) as $key => $value) {
                    if (strpos($key, cache_session::KEY_PREFIX) !== 0 || !is_array($value) || !isset($value['lastaccess'])) {
                        continue;
                    }
                    if ((int)$value['lastaccess'] < $purgetime || true) {
                        $todelete[] = $key;
                    }
                }
                if (count($todelete)) {
                    $outcome = (int)$store->delete_many($todelete);
                    if ($output) {
                        $strdef = s($definition->get_id());
                        $strstore = s($store->my_name());
                        mtrace("- Removed {$outcome} old {$strdef} sessions from the '{$strstore}' cache store.");
                    }
                }
            }
        }
    }
}
