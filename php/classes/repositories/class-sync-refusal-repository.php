<?php
/**
 * Sync refusal repository.
 *
 * @package SeriouslySimplePodcasting
 * @since 3.18.0
 */

namespace SeriouslySimplePodcasting\Repositories;

/**
 * Stores Castos sync refusals against podcast series terms.
 *
 * @package SeriouslySimplePodcasting
 */
class Sync_Refusal_Repository {

	/**
	 * Response code for a refusal that needs confirmation.
	 *
	 * @var string
	 */
	const CODE_DETAILS_DIFFER = 'guid_match_details_differ';

	/**
	 * Response code for a terminal GUID conflict.
	 *
	 * @var string
	 */
	const CODE_ALREADY_IN_USE = 'guid_already_in_use';

	/**
	 * Confirmation action that connects and overwrites the Castos podcast.
	 *
	 * @var string
	 */
	const ACTION_CONNECT = 'connect';

	/**
	 * Term meta key for storing a Castos sync refusal.
	 *
	 * @var string
	 */
	const META_KEY = 'castos_sync_refusal';

	/**
	 * Get the translated message for a refusal code.
	 *
	 * @since 3.18.0
	 *
	 * @param string $code Refusal response code.
	 *
	 * @return string Translated refusal message, or an empty string for an unknown code.
	 */
	public static function get_message( $code ): string {
		$messages = array(
			self::CODE_DETAILS_DIFFER => __( 'This GUID belongs to a podcast with different details. Connect and overwrite its Castos details, or change this podcast\'s GUID to create a separate Castos podcast.', 'seriously-simple-podcasting' ),
			self::CODE_ALREADY_IN_USE => __( 'This GUID already belongs to another podcast in this account.', 'seriously-simple-podcasting' ),
		);

		return $messages[ $code ] ?? '';
	}

	/**
	 * Check whether a refusal can be resolved by confirming the connection.
	 *
	 * @since 3.18.0
	 *
	 * @param array|null $refusal Stored refusal data.
	 *
	 * @return bool
	 */
	public static function needs_confirmation( $refusal ): bool {
		return is_array( $refusal ) && self::CODE_DETAILS_DIFFER === ( $refusal['code'] ?? '' );
	}

	/**
	 * Get the reason to show for a refusal SSP cannot offer a confirmation for.
	 *
	 * @since 3.18.0
	 *
	 * @param array|null $refusal Stored refusal data.
	 *
	 * @return string Translated SSP copy for a known code, Castos's own error otherwise.
	 */
	public static function get_refusal_reason( $refusal ): string {
		if ( ! is_array( $refusal ) ) {
			return '';
		}

		$message = self::get_message( $refusal['code'] ?? '' );

		return '' !== $message ? $message : (string) ( $refusal['error'] ?? '' );
	}

	/**
	 * Get the translated labels of the podcast fields that differ.
	 *
	 * @since 3.18.0
	 *
	 * @param array $differences Castos field keys.
	 *
	 * @return string[] Translated labels, with unknown keys passed through.
	 */
	public static function get_field_labels( array $differences ): array {
		$labels = array(
			'podcast_title'       => __( 'Podcast title', 'seriously-simple-podcasting' ),
			'podcast_description' => __( 'Podcast description', 'seriously-simple-podcasting' ),
			'website'             => __( 'Podcast URL', 'seriously-simple-podcasting' ),
			'itunes_category1'    => __( 'iTunes category 1', 'seriously-simple-podcasting' ),
			'itunes_category2'    => __( 'iTunes category 2', 'seriously-simple-podcasting' ),
			'itunes_category3'    => __( 'iTunes category 3', 'seriously-simple-podcasting' ),
		);

		$field_labels = array();
		foreach ( $differences as $difference ) {
			$key            = strtolower( trim( (string) $difference ) );
			$field_labels[] = isset( $labels[ $key ] ) ? $labels[ $key ] : (string) $difference;
		}

		return $field_labels;
	}

	/**
	 * Record a sync refusal for a series.
	 *
	 * @since 3.18.0
	 *
	 * @param int   $series_id Series term ID.
	 * @param array $refusal   Normalized refusal data, including the GUID snapshot.
	 *
	 * @return void
	 */
	public function record( $series_id, array $refusal ): void {
		if ( 0 === (int) $series_id ) {
			return;
		}

		update_term_meta( (int) $series_id, self::META_KEY, $refusal );
	}

	/**
	 * Get a sync refusal for a series.
	 *
	 * @since 3.18.0
	 *
	 * @param int $series_id Series term ID.
	 *
	 * @return array|null Normalized refusal data, or null when none is stored or its GUID changed.
	 */
	public function get( $series_id ): ?array {
		if ( 0 === (int) $series_id ) {
			return null;
		}

		$refusal = get_term_meta( (int) $series_id, self::META_KEY, true );

		if ( ! is_array( $refusal ) ) {
			return null;
		}

		if ( array_key_exists( 'guid', $refusal ) && ssp_get_podcast_guid( (int) $series_id ) !== (string) $refusal['guid'] ) {
			$this->clear( $series_id );

			return null;
		}

		return $refusal;
	}

	/**
	 * Clear a sync refusal for a series.
	 *
	 * @since 3.18.0
	 *
	 * @param int $series_id Series term ID.
	 *
	 * @return void
	 */
	public function clear( $series_id ): void {
		if ( 0 === (int) $series_id ) {
			return;
		}

		delete_term_meta( (int) $series_id, self::META_KEY );
	}
}
