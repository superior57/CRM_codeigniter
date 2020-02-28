<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_merge_fields
{
    /**
     * Codeigniter instance
     * @var object
     */
    protected $ci;

    /**
     * The actual registered fields from the classes that extends
     * @var array
     */
    protected $fields = [];

    /**
     * Paths to load the classes
     * e.q. merge_fields/client
     * @var array
     */
    protected $registered = [];

    /**
     * Merge fields relation
     * @var mixed
     */
    protected $for = null;

    /**
     * All merge fields are stored here
     * @var array
     */
    protected $all_merge_fields = null;

    /**
     * Helper property
     * @var boolean
     */
    private $classes_for_merge_fields_initialized = false;

    public function __construct()
    {
        $this->ci = &get_instance();

        // Method exists only if App_merge_fields is extended by another merge fields class
        if (method_exists($this, 'build')) {
            $this->set($this->build());
        } else {
            // Run only once when class is autoloaded
            $this->registered = hooks()->apply_filters('register_merge_fields', []);
        }
    }

    public function get_by_name($name)
    {
        foreach ($this->all() as $key => $feature) {
            if (isset($feature[$name])) {
                return $feature[$name];
            }
        }

        return [];
    }

    public function format_feature($name, ...$params)
    {
        // Initialize all merge fields and load classes
        if ($this->classes_for_merge_fields_initialized === false) {
            $this->all();
            $this->classes_for_merge_fields_initialized = true;
        }

        $baseName = basename($name);

        $merge_fields     = $this->get_by_name($this->ci->{$baseName}->name());
        $uniqueFormatters = [];
        $uniqueClassLoad  = [];

        foreach ($merge_fields as $field) {
            $uniqueFormatters[]                             = $field['format']['base_name'];
            $uniqueClassLoad[$field['format']['base_name']] = $field['format']['file'];
        }

        $uniqueFormatters = array_unique($uniqueFormatters);

        $formatted = [];

        foreach ($uniqueFormatters as $classFormatter) {
            if (method_exists($this->ci->{$classFormatter}, 'format')) {
                if (!class_exists($classFormatter, false)) {
                    $this->ci->load->library($uniqueClassLoad[$classFormatter]);
                }

                $newFormatted = $this->ci->{$classFormatter}->format(...$params);

                if (is_array($newFormatted)) {
                    $formatted = array_merge($newFormatted, $formatted);
                }
            }
        }

        return $formatted;
    }

    /**
     * Get the registered class fields
     * @return array
     */
    public function get($name = null)
    {
        $for = !$name ? $this->name() : $name;

        return isset($this->fields[$for]) ? $this->fields[$for] : [];
    }

    /**
     * Set merge fields
     * @param array $fields
     */
    public function set($fields)
    {
        $for = $this->name();

        if (!isset($this->fields[$for])) {
            $this->fields[$for] = $fields;
        } else {
            $this->fields[$for][] = $fields;
        }

        return $this;
    }

    /**
     * Register merge field path class
     * @param  mixed $loadPath
     * @return object
     */
    public function register($loadPath)
    {
        if (is_array($loadPath)) {
            foreach ($loadPath as $merge_fields) {
                $this->register($merge_fields);
            }

            return;
        }
        $this->registered[] = $loadPath;

        return $this;
    }

    /**
     * Get all registered paths
     * @return array
     */
    public function get_registered()
    {
        return $this->registered;
    }

    /**
     * Get all merge fields
     * @return array
     */
    public function all($reBuild = false)
    {
        if ($reBuild !== true && !is_null($this->all_merge_fields)) {
            return $this->all_merge_fields;
        }

        $registered = $this->get_registered();

        $available = [];

        foreach ($registered as $merge_field) {
            $baseName = $this->load($merge_field);

            $fields = $this->ci->{$baseName}->get();

            $name  = $this->ci->{$baseName}->name();
            $index = $this->merge_field_exists_by_name($available, $name);

            $format = [
                    'base_name' => $baseName,
                    'file'      => $merge_field,
                ];
            foreach ($fields as $key => $field) {
                $fields[$key]['format'] = $format;
            }

            if ($index !== false) {
                $index                    = (int) $index;
                $available[$index][$name] = array_merge($available[$index][$name], $fields);
            } else {
                $available[][$name] = $fields;
            }
        }

        $available = $this->apply_custom_fields($available, $format);

        $this->all_merge_fields = $available;

        return hooks()->apply_filters('available_merge_fields', $available);
    }

    public function name()
    {
        if (is_null($this->for)) {
            $this->for = strtolower(strbefore(get_class($this), '_merge_fields'));
        }

        return $this->for;
    }

    public function load($merge_field)
    {
        $baseName = basename($merge_field);

        if (!class_exists($baseName, false)) {
            $this->ci->load->library($merge_field);
        }

        return $baseName;
    }

    public function get_flat($primary, $additional = [], $exclude_keys = [])
    {
        if (!is_array($primary)) {
            $primary = [$primary];
        }

        if (!is_array($additional)) {
            $additional = [$additional];
        }

        if (!is_array($exclude_keys)) {
            $exclude_keys = [$exclude_keys];
        }

        $registered = $this->all();
        $flat       = [];
        foreach ($registered as $key => $val) {
            foreach ($val as $type => $fields) {
                if (in_array($type, $primary)) {
                    if ($availableFields = $this->check_availability($fields, $type, $exclude_keys)) {
                        array_push($flat, $availableFields);
                    }
                } elseif (in_array($type, $additional)) {
                    if ($type == 'other') {
                        $other = [];
                        foreach ($fields as $field) {
                            if (!in_array($field['key'], $exclude_keys)) {
                                $other[] = $field;
                            }
                        }
                        array_push($flat, $other);
                    } else {
                        if ($availableFields = $this->check_availability($fields, $type, $exclude_keys)) {
                            array_push($flat, $availableFields);
                        }
                    }
                }
            }
        }

        return $flat;
    }

    private function merge_field_exists_by_name($available, $name)
    {
        foreach ($available as $key => $merge_fields) {
            if (array_key_exists($name, $merge_fields)) {
                return (string) $key;
            }
        }

        return false;
    }

    private function check_availability($fields, $type, $exclude_keys)
    {
        $retVal = [];
        foreach ($fields as $available) {
            foreach ($available['available'] as $av) {
                // Check also if the name is not empty in case conditional merge field e.q. like GDPR
                if ($av == $type && !empty($available['name']) && !in_array($available['key'], $exclude_keys)) {
                    array_push($retVal, $available);
                }
            }
        }

        return count($retVal) > 0 ? $retVal : false;
    }

    private function apply_custom_fields($registered, $format)
    {
        $i = 0;
        foreach ($registered as $fields) {
            $f = 0;
            // Fix for merge fields as custom fields not matching the names
            foreach ($fields as $key => $_fields) {
                switch ($key) {
                case 'client':
                    $_key = 'customers';

                    break;
                case 'proposals':
                    $_key = 'proposal';

                    break;
                case 'contract':
                    $_key = 'contracts';

                    break;
                case 'ticket':
                    $_key = 'tickets';

                    break;
                default:
                    $_key = $key;

                    break;
            }

                $custom_fields = get_custom_fields($_key, [], true);

                foreach ($custom_fields as $field) {
                    array_push($registered[$i][$key], [
                        'name'      => $field['name'],
                        'key'       => '{' . $field['slug'] . '}',
                        'available' => $registered[$i][$key][$f]['available'],
                        'format'    => $format,
                    ]);
                }
                $f++;
            }
            $i++;
        }

        return $registered;
    }
}
