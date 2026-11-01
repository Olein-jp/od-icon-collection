<?php
/**
 * Settings APIを用いたコレクション設定。
 *
 * @package OD_Icon_Collection
 */

namespace OD_Icon_Collection;

defined( 'ABSPATH' ) || exit;

/** 管理者のコレクション設定と登録状況を表示する。 */
final class Settings {
	/**
	 * 登録管理。
	 *
	 * @var Collection_Registry
	 */
	private $registry;

	/**
	 * 登録管理を受け取る。
	 *
	 * @param Collection_Registry $registry 登録管理.
	 */
	public function __construct( $registry ) {
		$this->registry = $registry;
	}

	/** 保存処理はoptions.phpの権限・nonce検証を使用する。 */
	public function register() {
		register_setting(
			'od_icon_collection',
			'od_icon_collection_enabled',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * 管理者のみ保存でき、有効なスラッグだけを許可する。
	 *
	 * @param mixed $input 入力値.
	 * @return array
	 */
	public function sanitize( $input ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->registry->enabled();
		}
		$allowed = array_keys( $this->registry->definitions() );
		return array_values(
			array_filter(
				$allowed,
				static function ( $slug ) use ( $input ) {
					return is_array( $input ) && in_array( $slug, $input, true );
				}
			)
		);
	}

	/** 設定メニューを追加する。 */
	public function menu() {
		add_options_page( 'OD Icon Collection', 'OD Icon Collection', 'manage_options', 'od-icon-collection', array( $this, 'render' ) );
	}

	/** 設定画面を表示する。 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage these settings.', 'od-icon-collection' ) );
		}
		$enabled = $this->registry->enabled();
		?>
		<div class="wrap">
			<h1>OD Icon Collection</h1>
			<p><?php esc_html_e( 'Choose the collections available in the standard WordPress icon library.', 'od-icon-collection' ); ?></p>
			<div class="notice notice-warning inline"><p id="od-icon-collection-warning"><?php esc_html_e( 'Disabling a collection may hide icons already used in posts and pages. Re-enable it to restore them. Saved content will not be changed.', 'od-icon-collection' ); ?></p></div>
			<form method="post" action="options.php">
				<?php settings_fields( 'od_icon_collection' ); ?>
				<input type="hidden" name="od_icon_collection_enabled[]" value="">
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Enabled', 'od-icon-collection' ); ?></th>
						<th><?php esc_html_e( 'Library', 'od-icon-collection' ); ?></th>
						<th><?php esc_html_e( 'Version', 'od-icon-collection' ); ?></th>
						<th><?php esc_html_e( 'Target / Registered / Failed', 'od-icon-collection' ); ?></th>
						<th><?php esc_html_e( 'License', 'od-icon-collection' ); ?></th>
					</tr></thead><tbody>
					<?php foreach ( $this->registry->definitions() as $slug => $definition ) : ?>
						<?php
						$manifest = $this->registry->loader->manifest( $definition );
						$result   = $this->registry->results[ $slug ] ?? array(
							'success' => 0,
							'failure' => 0,
							'errors'  => array(),
						);
						?>
						<tr>
							<td><input type="checkbox" name="od_icon_collection_enabled[]" value="<?php echo esc_attr( $slug ); ?>" id="<?php echo esc_attr( $slug ); ?>" aria-describedby="od-icon-collection-warning" <?php checked( in_array( $slug, $enabled, true ) ); ?>></td>
							<td><label for="<?php echo esc_attr( $slug ); ?>"><strong><?php echo esc_html( $definition['label'] ); ?></strong></label>
								<?php if ( ! empty( $definition['repository_url'] ) ) : ?>
									<br><a href="<?php echo esc_url( $definition['repository_url'], array( 'https' ) ); ?>"><?php esc_html_e( 'Official repository', 'od-icon-collection' ); ?></a>
								<?php endif; ?>
								<?php foreach ( array_unique( $result['errors'] ) as $error ) : ?>
									<p><?php echo esc_html( $error ); ?></p>
								<?php endforeach; ?>
							</td>
							<td><?php echo esc_html( false === $manifest ? '—' : $manifest['source_version'] ); ?></td>
							<td><?php echo esc_html( false === $manifest ? '—' : count( $manifest['icons'] ) ); ?> / <?php echo esc_html( $result['success'] ); ?> / <?php echo esc_html( $result['failure'] ); ?></td>
							<td><a href="<?php echo esc_url( $definition['license_url'] ?? '', array( 'https' ) ); ?>"><?php echo esc_html( $definition['license'] ?? '—' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
