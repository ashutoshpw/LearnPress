<?php

/**
 * Class LP_Submenu_Addons
 */
class LP_Submenu_Addons extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Addons constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-addons';
		$this->menu_title = __( 'Addons', 'learnpress' );
		$this->page_title = __( 'LearnPress Addons', 'learnpress' );
		$this->priority   = 20;

		$this->tabs = apply_filters(
			'learn-press/admin/page-addons-tabs',
			array(
				'installed' => sprintf( __( 'Installed (%d)', 'learnpress' ), LP_Plugins_Helper::count_plugins( 'installed' ) ),
				'more'      => sprintf( __( 'Get more (%d)', 'learnpress' ), LP_Plugins_Helper::count_plugins() ),
				'themes'    => sprintf( __( 'Themes (%d)', 'learnpress' ), LP_Plugins_Helper::count_themes() )
			)
		);
		//$this->sections = apply_filters( 'learn-press/admin/page-addons/sections', $sections );
		parent::__construct();
	}

	public function page_content_installed() {
		$this->page_content_search_form();
		learn_press_admin_view( 'addons/html-plugins-installed' );
	}

	public function page_content_more() {
		$this->page_content_search_form();
		learn_press_admin_view( 'addons/html-plugins-more' );
	}

	public function page_content_themes() {
		$this->page_content_search_form();
		learn_press_admin_view( 'addons/html-themes' );
	}

	public function page_content_search_form() {
		?>
        <p class="search-box">
            <input type="text" class="lp-search-addon" value="" placeholder="<?php _e( 'Search...', 'learnpress' ); ?>">
        </p>
		<?php
	}
}

return new LP_Submenu_Addons();