<?php

/**
 * @group l10n
 * @group i18n
 * @ticket 26511
 */
class Tests_L10n_wpLocaleSwitcher extends WP_UnitTestCase {
	/**
	 * @var string
	 */
	protected $locale = '';

	/**
	 * @var string
	 */
	protected $previous_locale = '';

	public function set_up() {
		parent::set_up();

		$this->locale          = '';
		$this->previous_locale = '';

		unset( $GLOBALS['l10n'], $GLOBALS['l10n_unloaded'] );

		/** @var WP_Textdomain_Registry $wp_textdomain_registry */
		global $wp_textdomain_registry;

		$wp_textdomain_registry = new WP_Textdomain_Registry();
	}

	public function tear_down() {
		unset( $GLOBALS['l10n'], $GLOBALS['l10n_unloaded'] );

		/** @var WP_Textdomain_Registry $wp_textdomain_registry */
		global $wp_textdomain_registry;

		$wp_textdomain_registry = new WP_Textdomain_Registry();

		parent::tear_down();
	}

	/**
	 * @covers ::switch_to_locale
	 */
	public function test_switch_to_non_existent_locale_returns_false() {
		$this->assertFalse( switch_to_locale( 'foo_BAR' ) );
	}

	/**
	 * @covers ::switch_to_locale
	 */
	public function test_switch_to_non_existent_locale_does_not_change_locale() {
		switch_to_locale( 'foo_BAR' );

		$this->assertSame( 'en_US', get_locale() );
	}

	/**
	 * @covers ::switch_to_locale
	 */
	public function test_switch_to_locale_returns_true() {
		$expected = switch_to_locale( 'en_GB' );

		// Cleanup.
		restore_previous_locale();

		$this->assertTrue( $expected );
	}

	/**
	 * @covers ::switch_to_locale
	 */
	public function test_switch_to_locale_changes_the_locale() {
		switch_to_locale( 'en_GB' );

		$locale = get_locale();

		// Cleanup.
		restore_previous_locale();

		$this->assertSame( 'en_GB', $locale );
	}

	/**
	 * @covers ::switch_to_locale
	 * @covers ::translate
	 * @covers ::__
	 */
	public function test_switch_to_locale_loads_translation() {
		switch_to_locale( 'es_ES' );

		$actual = __( 'Invalid parameter.' );

		// Cleanup.
		restore_previous_locale();

		$this->assertSame( 'Parámetro no válido. ', $actual );
	}

	/**
	 * @covers ::switch_to_locale
	 */
	public function test_switch_to_locale_changes_wp_locale_global() {
		global $wp_locale;

		$expected = array(
			'thousands_sep' => '.',
			'decimal_point' => ',',
		);

		switch_to_locale( 'de_DE' );

		$wp_locale_de_de = clone $wp_locale;

		// Cleanup.
		restore_previous_locale();

		$this->assertSameSetsWithIndex( $expected, $wp_locale_de_de->number_format );
	}

	/**
	 * @covers ::switch_to_locale
	 */
	public function test_switch_to_locale_en_US() {
		switch_to_locale( 'en_GB' );
		$locale_en_gb = get_locale();
		switch_to_locale( 'en_US' );
		$locale_en_us = get_locale();

		// Cleanup.
		restore_current_locale();

		$this->assertSame( 'en_GB', $locale_en_gb );
		$this->assertSame( 'en_US', $locale_en_us );
	}

	/**
	 * @covers ::switch_to_locale
	 */
	public function test_switch_to_locale_multiple_times() {
		switch_to_locale( 'en_GB' );
		switch_to_locale( 'es_ES' );
		$locale = get_locale();

		// Cleanup.
		restore_previous_locale();
		restore_previous_locale();

		$this->assertSame( 'es_ES', $locale );
	}

	/**
	 * @covers ::switch_to_locale
	 * @covers ::__
	 * @covers ::translate
	 */
	public function test_switch_to_locale_multiple_times_loads_translation() {
		switch_to_locale( 'en_GB' );
		switch_to_locale( 'de_DE' );
		switch_to_locale( 'es_ES' );

		$actual = __( 'Invalid parameter.' );

		// Cleanup.
		restore_previous_locale();
		restore_previous_locale();
		restore_previous_locale();

		$this->assertSame( 'Parámetro no válido. ', $actual );
	}

	/**
	 * @covers ::restore_previous_locale
	 */
	public function test_restore_previous_locale_without_switching() {
		$this->assertFalse( restore_previous_locale() );
	}

	/**
	 * @covers ::restore_previous_locale
	 */
	public function test_restore_previous_locale_changes_the_locale_back() {
		switch_to_locale( 'en_GB' );

		// Cleanup.
		restore_previous_locale();

		$this->assertSame( 'en_US', get_locale() );
	}

	/**
	 * @covers ::restore_previous_locale
	 */
	public function test_restore_previous_locale_after_switching_multiple_times() {
		switch_to_locale( 'en_GB' );
		switch_to_locale( 'es_ES' );
		restore_previous_locale();

		$locale = get_locale();

		// Cleanup.
		restore_previous_locale();

		$this->assertSame( 'en_GB', $locale );
	}

	/**
	 * @covers ::restore_previous_locale
	 * @covers ::__
	 * @covers ::translate
	 */
	public function test_restore_previous_locale_restores_translation() {
		switch_to_locale( 'es_ES' );
		restore_previous_locale();

		$actual = __( 'Invalid parameter.' );

		$this->assertSame( 'Invalid parameter.', $actual );
	}

	/**
	 * @covers ::restore_previous_locale
	 */
	public function test_restore_previous_locale_action_passes_previous_locale() {
		switch_to_locale( 'en_GB' );
		switch_to_locale( 'es_ES' );

		add_action( 'restore_previous_locale', array( $this, 'store_locale' ), 10, 2 );

		restore_previous_locale();

		$previous_locale = $this->previous_locale;

		// Cleanup.
		restore_previous_locale();

		$this->assertSame( 'es_ES', $previous_locale );
	}

	/**
	 * @covers ::restore_previous_locale
	 */
	public function test_restore_previous_locale_restores_wp_locale_global() {
		global $wp_locale;

		$expected = array(
			'thousands_sep' => ',',
			'decimal_point' => '.',
		);

		switch_to_locale( 'de_DE' );
		restore_previous_locale();

		$this->assertSameSetsWithIndex( $expected, $wp_locale->number_format );
	}

	/**
	 * @covers ::restore_current_locale
	 */
	public function test_restore_current_locale_without_switching() {
		$this->assertFalse( restore_current_locale() );
	}

	/**
	 * @covers ::restore_previous_locale
	 */
	public function test_restore_current_locale_after_switching_multiple_times() {
		switch_to_locale( 'en_GB' );
		switch_to_locale( 'nl_NL' );
		switch_to_locale( 'es_ES' );

		restore_current_locale();

		$this->assertSame( 'en_US', get_locale() );
	}

	public function store_locale( $locale, $previous_locale ) {
		$this->locale          = $locale;
		$this->previous_locale = $previous_locale;
	}

	/**
	 * @covers ::is_locale_switched
	 */
	public function test_is_locale_switched_if_not_switched() {
		$this->assertFalse( is_locale_switched() );
	}

	/**
	 * @covers ::is_locale_switched
	 */
	public function test_is_locale_switched_original_locale() {
		$original_locale = get_locale();

		switch_to_locale( 'en_GB' );
		switch_to_locale( $original_locale );

		$is_locale_switched = is_locale_switched();

		restore_current_locale();

		$this->assertTrue( $is_locale_switched );
	}

	/**
	 * @covers ::is_locale_switched
	 */
	public function test_is_locale_switched() {
		switch_to_locale( 'en_GB' );
		switch_to_locale( 'nl_NL' );

		$is_locale_switched = is_locale_switched();

		restore_current_locale();

		$this->assertTrue( $is_locale_switched );
	}

	/**
	 * @covers ::switch_to_locale
	 */
	public function test_switch_to_site_locale_if_user_locale_is_set() {
		global $l10n, $wp_locale_switcher;

		$site_locale = get_locale();

		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'de_DE',
			)
		);

		wp_set_current_user( $user_id );
		set_current_screen( 'dashboard' );

		$locale_switcher = clone $wp_locale_switcher;

		$wp_locale_switcher = new WP_Locale_Switcher();
		$wp_locale_switcher->init();

		$user_locale = get_user_locale();

		$this->assertSame( 'de_DE', $user_locale );

		load_default_textdomain( $user_locale );
		$language_header_before_switch = $l10n['default']->headers['Language']; // de_DE

		$locale_switched_user_locale  = switch_to_locale( $user_locale ); // False.
		$locale_switched_site_locale  = switch_to_locale( $site_locale ); // True.
		$site_locale_after_switch     = get_locale();
		$language_header_after_switch = isset( $l10n['default'] ); // en_US

		restore_current_locale();

		$language_header_after_restore = $l10n['default']->headers['Language']; // de_DE

		$wp_locale_switcher = $locale_switcher;

		$this->assertFalse( $locale_switched_user_locale );
		$this->assertTrue( $locale_switched_site_locale );
		$this->assertSame( $site_locale, $site_locale_after_switch );
		$this->assertSame( 'de_DE', $language_header_before_switch );
		$this->assertFalse( $language_header_after_switch );
		$this->assertSame( 'de_DE', $language_header_after_restore );
	}

	/**
	 * @covers ::switch_to_locale
	 */
	public function test_switch_to_different_site_locale_if_user_locale_is_set() {
		global $l10n, $wp_locale_switcher;

		// Change site locale to es_ES.
		add_filter( 'locale', array( $this, 'filter_locale' ) );

		$site_locale = get_locale();

		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'de_DE',
			)
		);

		wp_set_current_user( $user_id );
		set_current_screen( 'dashboard' );

		$locale_switcher = clone $wp_locale_switcher;

		$wp_locale_switcher = new WP_Locale_Switcher();
		$wp_locale_switcher->init();

		$user_locale = get_user_locale();

		$this->assertSame( 'de_DE', $user_locale );

		load_default_textdomain( $user_locale );
		$language_header_before_switch = $l10n['default']->headers['Language']; // de_DE

		$locale_switched_user_locale  = switch_to_locale( $user_locale ); // False.
		$locale_switched_site_locale  = switch_to_locale( $site_locale ); // True.
		$site_locale_after_switch     = get_locale();
		$language_header_after_switch = $l10n['default']->headers['Language']; // es_ES

		restore_current_locale();

		$language_header_after_restore = $l10n['default']->headers['Language']; // de_DE

		$wp_locale_switcher = $locale_switcher;

		remove_filter( 'locale', array( $this, 'filter_locale' ) );

		$this->assertFalse( $locale_switched_user_locale );
		$this->assertTrue( $locale_switched_site_locale );
		$this->assertSame( $site_locale, $site_locale_after_switch );
		$this->assertSame( 'de_DE', $language_header_before_switch );
		$this->assertSame( 'es_ES', $language_header_after_switch );
		$this->assertSame( 'de_DE', $language_header_after_restore );
	}

	/**
	 * @covers ::switch_to_locale
	 * @covers ::load_default_textdomain
	 */
	public function test_multiple_switches_to_site_locale_and_user_locale() {
		global $wp_locale_switcher;

		$site_locale = get_locale();

		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'en_GB',
			)
		);

		wp_set_current_user( $user_id );
		set_current_screen( 'dashboard' );

		$locale_switcher = clone $wp_locale_switcher;

		$wp_locale_switcher = new WP_Locale_Switcher();
		$wp_locale_switcher->init();

		$user_locale = get_user_locale();

		load_default_textdomain( $user_locale );

		require_once DIR_TESTDATA . '/plugins/internationalized-plugin.php';

		switch_to_locale( 'de_DE' );
		switch_to_locale( $site_locale );

		$actual = i18n_plugin_test();

		restore_current_locale();

		$wp_locale_switcher = $locale_switcher;

		$this->assertSame( 'en_US', get_locale() );
		$this->assertSame( 'This is a dummy plugin', $actual );
	}

	/**
	 * @ticket 39210
	 */
	public function test_switch_reloads_plugin_translations_outside_wp_lang_dir() {
		/** @var WP_Textdomain_Registry $wp_textdomain_registry */
		global $wp_locale_switcher, $wp_textdomain_registry;

		$locale_switcher = clone $wp_locale_switcher;

		$wp_locale_switcher = new WP_Locale_Switcher();
		$wp_locale_switcher->init();

		require_once DIR_TESTDATA . '/plugins/custom-internationalized-plugin/custom-internationalized-plugin.php';

		$actual = custom_i18n_plugin_test();

		switch_to_locale( 'es_ES' );

		$registry_value = $wp_textdomain_registry->get( 'custom-internationalized-plugin', determine_locale() );

		switch_to_locale( 'de_DE' );

		$actual_de_de = custom_i18n_plugin_test();

		restore_previous_locale();

		$actual_es_es = custom_i18n_plugin_test();

		restore_current_locale();

		$wp_locale_switcher = $locale_switcher;

		$this->assertSame( 'This is a dummy plugin', $actual );
		$this->assertSame( WP_PLUGIN_DIR . '/custom-internationalized-plugin/languages/', $registry_value );
		$this->assertSame( 'Das ist ein Dummy Plugin', $actual_de_de );
		$this->assertSame( 'Este es un plugin dummy', $actual_es_es );
	}

	/**
	 * @ticket 39210
	 */
	public function test_switch_reloads_theme_translations_outside_wp_lang_dir() {
		/** @var WP_Textdomain_Registry $wp_textdomain_registry */
		global $wp_locale_switcher, $wp_textdomain_registry;

		$locale_switcher = clone $wp_locale_switcher;

		$wp_locale_switcher = new WP_Locale_Switcher();
		$wp_locale_switcher->init();

		switch_theme( 'custom-internationalized-theme' );

		require_once get_stylesheet_directory() . '/functions.php';

		$actual = custom_i18n_theme_test();

		switch_to_locale( 'es_ES' );

		$registry_value = $wp_textdomain_registry->get( 'custom-internationalized-theme', determine_locale() );

		switch_to_locale( 'de_DE' );

		$actual_de_de = custom_i18n_theme_test();

		restore_previous_locale();

		$actual_es_es = custom_i18n_theme_test();

		restore_current_locale();

		$wp_locale_switcher = $locale_switcher;

		$this->assertSame( get_template_directory() . '/languages/', $registry_value );
		$this->assertSame( 'This is a dummy theme', $actual );
		$this->assertSame( 'Das ist ein Dummy Theme', $actual_de_de );
		$this->assertSame( 'Este es un tema dummy', $actual_es_es );
	}

	public function filter_locale() {
		return 'es_ES';
	}
}
