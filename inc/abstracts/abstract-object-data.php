<?php

/**
 * Class LP_Abstract_Object_Data
 */
abstract class LP_Abstract_Object_Data {

	/**
	 * @var int
	 */
	protected $_id = 0;

	/**
	 * @var array
	 */
	protected $_data = array();

	/**
	 * @var array
	 */
	protected $_extra_data = array();

	/**
	 * @var bool
	 */
	protected $_no_cache = false;

	/**
	 * @var array
	 */
	protected $_supports = array();

	/**
	 * Store new changes
	 *
	 * @var array
	 */
	protected $_changes = array();

	/**
	 * @var array
	 */
	protected $_extra_data_changes = array();

	/**
	 * Object meta
	 *
	 * @var array
	 */
	protected $_meta_data = array();

	/**
	 * @var array
	 */
	protected $_meta_keys = array();

	/**
	 * CURD class to manipulation with database.
	 *
	 * @var null
	 */
	protected $_curd = null;

	/**
	 * LP_Abstract_Object_Data constructor.
	 *
	 * @param null $data
	 */
	public function __construct( $data = null ) {
		$this->_data = (array) $data;
		if ( array_key_exists( 'id', $this->_data ) ) {
			$this->set_id( absint( $this->_data['id'] ) );
			unset( $this->_data['id'] );
		}
		$this->load_curd();
	}

	protected function load_curd() {
		if ( is_string( $this->_curd ) && $this->_curd ) {
			$this->_curd = new $this->_curd();
		}
	}

	/**
	 * Set id of object in database
	 *
	 * @param $id
	 */
	public function set_id( $id ) {
		$this->_id = $id;
	}

	/**
	 * Get id of object in database
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->_id;
	}

	/**
	 * Get object data
	 *
	 * @param string $name - Optional. Name of data want to get, true if return all.
	 * @param mixed  $default
	 *
	 * @return array|mixed
	 */
	public function get_data( $name = '', $default = '' ) {
		if ( is_string( $name ) ) {
			// Check in data first then check in extra data
			return
				array_key_exists( $name, $this->_data ) ? $this->_data[ $name ] :
					( array_key_exists( $name, $this->_extra_data ) ? $this->_extra_data[ $name ] : $default );
		} elseif ( is_array( $name ) ) {
			$data = array();
			foreach ( $name as $key ) {
				$data[ $key ] = $this->get_data( $key, $default );
			}

			return $data;
		} elseif ( true === $name ) {
			return array_merge( $this->_data, $this->_extra_data );
		}

		return false;
	}

	public function get_extra_data( $name = '', $default = '' ) {
		if ( is_string( $name ) ) {
			// Check in data first then check in extra data
			return array_key_exists( $name, $this->_extra_data ) ? $this->_extra_data[ $name ] : $default;
		} elseif ( is_array( $name ) ) {
			$data = array();
			foreach ( $name as $key ) {
				$data[ $key ] = $this->get_extra_data( $key, $default );
			}

			return $data;
		} elseif ( true === $name ) {
			return $this->_extra_data;
		}

		return false;
	}

	/**
	 * Set object data.
	 *
	 * @param mixed $key_or_data
	 * @param mixed $value
	 * @param bool  $extra
	 */
	protected function _set_data( $key_or_data, $value = '', $extra = false ) {
		if ( is_array( $key_or_data ) ) {
			foreach ( $key_or_data as $key => $value ) {
				$this->_set_data( $key, $value, $extra );
			}
		} elseif ( $key_or_data ) {
			$data    = $extra ? $this->_extra_data : $this->_data;
			$changes = $extra ? $this->_extra_data_changes : $this->_changes;
			//if ( array_key_exists( $key_or_data, $this->_data ) ) {
//			if ( false ) {
//				if ( $value !== $data[ $key_or_data ] ) {
//					$changes[ $key_or_data ] = $value;
//				}
//			} else {
			if ( $extra ) {
				// Do not allow to add extra data with the same key in data
				if ( ! array_key_exists( $key_or_data, $this->_data ) ) {
					$this->_extra_data[ $key_or_data ] = $value;
				}
			} else {
				try {
					if ( ! is_string( $key_or_data ) && ! is_numeric( $key_or_data ) ) {
						throw new Exception( 'error' );
					}
					// Only change the data is already existed
					if ( array_key_exists( $key_or_data, $this->_data ) ) {
						$this->_data[ $key_or_data ] = $value;
					} else {
						$this->_extra_data[ $key_or_data ] = $value;
					}
				}
				catch ( Exception $ex ) {
					print_r( $key_or_data );
					print_r( $ex->getMessage() );
					die();
				}
			}
			//}


			//}
		}
	}

	/**
	 * Set extra data
	 *
	 * @param array|string $key_or_data
	 * @param string       $value
	 */
	public function set_data( $key_or_data, $value = '' ) {
		$this->_set_data( $key_or_data, $value, true );
	}

	public function set_data_date( $key, $value ) {
		if ( is_null( $key ) ) {
			$this->_set_data( $key, $value );

			return;
		} elseif ( ! $value instanceof LP_Datetime ) {
			$value = new LP_Datetime( $value );
		}

		$this->_set_data( $key, $value );
	}

	/**
	 * Set data via methods in array
	 *
	 * @param array $data - Array with key is method and value is value to set
	 *
	 * @throws Exception
	 */
	public function set_data_via_methods( $data ) {
		$errors = array_keys( $data );
		foreach ( $data as $prop => $value ) {
			$setter = "set_$prop";
			if ( ! is_null( $value ) && is_callable( array( $this, $setter ) ) ) {
				$reflection = new ReflectionMethod( $this, $setter );

				if ( $reflection->isPublic() ) {
					$this->{$setter}( $value );
					$errors = array_diff( $errors, array( $prop ) );
				}
			}
		}

		// If there is at least one method failed
		if ( $errors ) {
			$errors = array_map( array( $this, 'prefix_set_method' ), $errors );
			throw new Exception( sprintf( __( 'The following these function do not exists %s', 'learnpress' ), join( ',', $errors ) ) );
		}
	}

	/**
	 * Return the keys of data
	 *
	 * @param bool $extra - Optional. TRUE if including extra data
	 *
	 * @return array
	 */
	public function get_data_keys( $extra = true ) {
		return $extra ? array_merge( array_keys( $this->_data ), array_keys( $this->_extra_data ) ) : array_keys( $this->_data );
	}

	public function prefix_set_method( $method ) {
		return "set_{$method}";
	}

	/**
	 * Apply the changes
	 */
	public function apply_changes() {
		$this->_data    = array_replace_recursive( $this->_data, $this->_changes );
		$this->_changes = array();
	}

	/**
	 * Get the changes.
	 *
	 * @return array
	 */
	public function get_changes() {
		return $this->_changes;
	}

	/**
	 * Check if question is support feature.
	 *
	 * @param string $feature
	 * @param string $type
	 *
	 * @return bool
	 */
	public function is_support( $feature, $type = '' ) {
		$feature    = $this->_sanitize_feature_key( $feature );
		$is_support = array_key_exists( $feature, $this->_supports ) ? true : false;
		if ( $type && $is_support ) {
			return $this->_supports[ $feature ] === $type;
		}

		return $is_support;
	}

	/**
	 * Add a feature that question is supported
	 *
	 * @param        $feature
	 * @param string $type
	 */
	public function add_support( $feature, $type = 'yes' ) {
		$feature                     = $this->_sanitize_feature_key( $feature );
		$this->_supports[ $feature ] = $type === null ? 'yes' : $type;
	}

	/**
	 * @param $feature
	 *
	 * @return mixed
	 */
	protected function _sanitize_feature_key( $feature ) {
		return preg_replace( '~[_]+~', '-', $feature );
	}

	/**
	 * Get all features are supported by question.
	 *
	 * @return array
	 */
	public function get_supports() {
		return $this->_supports;
	}

	/**
	 * @param $value
	 */
	public function set_no_cache( $value ) {
		$this->_no_cache = $value;
	}

	/**
	 * @return bool
	 */
	public function get_no_cache() {
		return $this->_no_cache;
	}

	/**
	 * Read all metas and set to object
	 */
	public function read_meta() {
		if ( $meta_data = $this->_curd->read_meta( $this ) ) {

			$external_metas = array_filter( $meta_data, array(
				$this,
				'exclude_metas'
			) );//$this->internal_meta_keys = array_merge( array_map( array( $this, 'prefix_key' ), $object->get_data_keys() ), $this->internal_meta_keys );

			foreach ( $external_metas as $meta ) {
				$this->_meta_data[] = $meta;
			}
		}
	}

	/**
	 * Callback function for excluding meta keys.
	 *
	 * @param $meta
	 *
	 * @return bool
	 */
	protected function exclude_metas( $meta ) {
		$exclude_keys = array_merge( array_keys( $this->_meta_keys ), array_keys( $this->_data ) );

		return ! in_array( $meta->meta_key, $exclude_keys ) && 0 !== stripos( $meta->meta_key, 'wp_' );
	}

	/**
	 * Add new meta data to object.
	 *
	 * @param string|array $key_or_array
	 * @param string       $value
	 */
	public function add_meta( $key_or_array, $value = '' ) {
		if ( is_array( $key_or_array ) ) {
			foreach ( $key_or_array as $key => $value ) {
				$this->add_meta( $key, $value );
			}
		} else {
			$this->_meta_data[] = (object) array(
				'meta_key'   => $key_or_array,
				'meta_value' => $value
			);
		}
	}

	public function update_meta() {
		if ( $this->_meta_data ) {
			foreach ( $this->_meta_data as $meta_data ) {
				$this->_curd->update_meta( $this, $meta_data );
			}
		}
	}

	public function get_meta( $key, $single = true ) {
		return 10000;
	}

	public function get_meta_keys() {
		return $this->_meta_keys;
	}

}